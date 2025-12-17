<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Form\CustomerType;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/customer')]
class CustomerController extends AbstractController
{
    #[Route('/', name: 'customer_index')]
    public function index(Request $request, CustomerRepository $repo): Response
    {
        $search  = $request->query->get('q');
        $store   = $request->query->get('store');
        $country = $request->query->get('country');
        $city    = $request->query->get('city');
        $status  = $request->query->get('status');

        $storeId = $store ? (int) $store : null;

        $customers = $repo->findByFilters(
            $search,
            $storeId,
            $country ?: null,
            $city ?: null,
            $status ?: null
        );

        return $this->render('customer/index.html.twig', [
            'customers'   => $customers,
            'query'       => $search,
            'store'       => $store,
            'country'     => $country,
            'city'        => $city,
            'status'      => $status,
            'totalItems'  => count($customers),
            'currentPage' => 1,
            'totalPages'  => 1,
        ]);
    }

    #[Route('/new', name: 'customer_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $customer = new Customer();

        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customer->setLastUpdate(new \DateTime());
            $em->persist($customer);
            $em->flush();

            $this->addFlash('success', 'Cliente creado correctamente.');
            return $this->redirectToRoute('customer_index');
        }

        return $this->render('customer/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'customer_show', requirements: ['id' => '\d+'])]
    public function show(Customer $customer): Response
    {
        return $this->render('customer/show.html.twig', [
            'customer' => $customer,
        ]);
    }

    #[Route('/{id}/edit', name: 'customer_edit', requirements: ['id' => '\d+'])]
    public function edit(
        Request $request,
        Customer $customer,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customer->setLastUpdate(new \DateTime());
            $em->flush();

            $this->addFlash('success', 'Cliente actualizado correctamente.');
            return $this->redirectToRoute('customer_show', ['id' => $customer->getId()]);
        }

        return $this->render('customer/edit.html.twig', [
            'form'     => $form->createView(),
            'customer' => $customer,
        ]);
    }

    #[Route('/{id}/deactivate', name: 'customer_deactivate', methods: ['POST'])]
    public function deactivate(
        Request $request,
        Customer $customer,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('deactivate_customer_' . $customer->getId(), $request->request->get('_token'))) {
            $customer->setActive(false);
            $customer->setLastUpdate(new \DateTime());
            $em->flush();

            $this->addFlash('info', 'Cliente marcado como inactivo.');
        }

        return $this->redirectToRoute('customer_index');
    }
}
