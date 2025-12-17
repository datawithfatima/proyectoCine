<?php

namespace App\Controller;

use App\Entity\Rental;
use App\Form\RentalType;
use App\Form\RentalReturnType;
use App\Repository\RentalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/rental')]
class RentalController extends AbstractController
{
    #[Route('/', name: 'rental_index')]
    public function index(Request $request, RentalRepository $repo): Response
    {
        $customer = $request->query->get('customer');
        $film     = $request->query->get('film');
        $status   = $request->query->get('status'); // active | returned | null
        $fromStr  = $request->query->get('from');
        $toStr    = $request->query->get('to');

        $from = $fromStr ? new \DateTime($fromStr) : null;
        $to   = $toStr   ? new \DateTime($toStr)   : null;

        $rentals = $repo->search($customer, $film, $status, $from, $to);

        return $this->render('rental/index.html.twig', [
            'rentals'  => $rentals,
            'customer' => $customer,
            'film'     => $film,
            'status'   => $status,
            'from'     => $fromStr,
            'to'       => $toStr,
        ]);
    }

    #[Route('/new', name: 'rental_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $rental = new Rental();

        $form = $this->createForm(RentalType::class, $rental);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$rental->getRentalDate()) {
                $rental->setRentalDate(new \DateTime());
            }
            $rental->setLastUpdate(new \DateTime());

            $em->persist($rental);
            $em->flush();

            $this->addFlash('success', 'Alquiler registrado correctamente.');
            return $this->redirectToRoute('rental_index');
        }

        return $this->render('rental/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/return', name: 'rental_return', requirements: ['id' => '\d+'])]
    public function registerReturn(
        Request $request,
        Rental $rental,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(RentalReturnType::class, $rental);
        $form->handleRequest($request);

        $lateDays = null;
        $allowedDays = null;

        // Para mostrar datos de retraso cuando ya se devolvió
        if ($rental->getReturnDate()) {
            [$lateDays, $allowedDays] = $this->calculateDelay($rental);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $rental->setLastUpdate(new \DateTime());

            [$lateDays, $allowedDays] = $this->calculateDelay($rental);

            $em->flush();

            $msg = 'Devolución registrada.';
            if ($lateDays !== null && $lateDays > 0) {
                $msg .= " El cliente se retrasó {$lateDays} día(s) (plazo: {$allowedDays} días).";
            }

            $this->addFlash('info', $msg);

            return $this->redirectToRoute('rental_show', ['id' => $rental->getId()]);
        }

        return $this->render('rental/return.html.twig', [
            'rental'      => $rental,
            'form'        => $form->createView(),
            'lateDays'    => $lateDays,
            'allowedDays' => $allowedDays,
        ]);
    }

    #[Route('/{id}', name: 'rental_show', requirements: ['id' => '\d+'])]
    public function show(Rental $rental): Response
    {
        [$lateDays, $allowedDays] = $this->calculateDelay($rental);

        return $this->render('rental/show.html.twig', [
            'rental'      => $rental,
            'lateDays'    => $lateDays,
            'allowedDays' => $allowedDays,
        ]);
    }

    /**
     * Devuelve [díasRetraso, díasPermitidos] o [null, null] si no aplica.
     */
    private function calculateDelay(Rental $rental): array
    {
        $returnDate = $rental->getReturnDate();
        if (!$returnDate) {
            return [null, null];
        }

        $rentalDate = $rental->getRentalDate();
        $film       = $rental->getInventory()->getFilm();
        $allowed    = $film->getRentalDuration() ?? 0;

        $diffDays = $rentalDate->diff($returnDate)->days;
        $lateDays = max(0, $diffDays - $allowed);

        return [$lateDays, $allowed];
    }
}
