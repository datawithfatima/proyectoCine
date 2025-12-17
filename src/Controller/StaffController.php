<?php

namespace App\Controller;

use App\Entity\Staff;
use App\Form\StaffType;
use App\Repository\StaffRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/staff')]
class StaffController extends AbstractController
{
    // 1. LISTAR (INDEX)
    #[Route('/', name: 'app_staff_index', methods: ['GET'])]
    public function index(StaffRepository $staffRepository): Response
    {
        return $this->render('staff/index.html.twig', [
            'staff_members' => $staffRepository->findAll(),
        ]);
    }

    // 2. CREAR NUEVO (NEW)
    #[Route('/new', name: 'app_staff_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $staff = new Staff();
        $staff->setLastUpdate(new \DateTime()); // Fecha automática
        $staff->setActive(true); // Activo por defecto

        $form = $this->createForm(StaffType::class, $staff);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($staff);
            $entityManager->flush();

            $this->addFlash('success', 'Empleado creado exitosamente');
            return $this->redirectToRoute('app_staff_index');
        }

        return $this->render('staff/new.html.twig', [
            'staff' => $staff,
            'form' => $form->createView(),
        ]);
    }

    // 3. EDITAR (EDIT)
    #[Route('/{staffId}/edit', name: 'app_staff_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $staffId, StaffRepository $staffRepository, EntityManagerInterface $entityManager): Response
    {
        $staff = $staffRepository->find($staffId);
        if (!$staff) throw $this->createNotFoundException('Empleado no encontrado');

        $form = $this->createForm(StaffType::class, $staff);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $staff->setLastUpdate(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Empleado actualizado');
            return $this->redirectToRoute('app_staff_index');
        }

        return $this->render('staff/edit.html.twig', [
            'staff' => $staff,
            'form' => $form->createView(),
        ]);
    }

    // 4. ELIMINAR (DELETE)
    #[Route('/{staffId}', name: 'app_staff_delete', methods: ['POST'])]
    public function delete(Request $request, int $staffId, StaffRepository $staffRepository, EntityManagerInterface $entityManager): Response
    {
        $staff = $staffRepository->find($staffId);

        if ($staff && $this->isCsrfTokenValid('delete'.$staff->getStaffId(), $request->request->get('_token'))) {
            $entityManager->remove($staff);
            $entityManager->flush();
            $this->addFlash('success', 'Empleado eliminado');
        }

        return $this->redirectToRoute('app_staff_index');
    }
}