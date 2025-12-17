<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Repository\PaymentRepository;
use App\Repository\CustomerRepository;
use App\Repository\RentalRepository;
use App\Repository\StaffRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Form\PaymentType;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/payment')]
class PaymentController extends AbstractController
{
    #[Route('/', name: 'app_payment_index', methods: ['GET'])]
    public function index(PaymentRepository $paymentRepository, CustomerRepository $customerRepository, StaffRepository $staffRepository, Request $request): Response
    {
        // 1. Recoger filtros y página
    $customerId = $request->query->get('cliente');
    $staffId = $request->query->get('staff');
    $startDate = $request->query->get('fecha_inicio');
    $endDate = $request->query->get('fecha_fin');
    $sortBy = $request->query->get('ordenar', 'date_desc');
    
    $currentPage = (int) $request->query->get('page', 1);
    $limit = 10;

    // 2. Llamar a TU función del repositorio (que ahora devuelve una Query)
    $query = $paymentRepository->findByFilters($customerId, $staffId, $startDate, $endDate, $sortBy);

    // 3. Configurar el Paginator con esa Query
    $paginator = new Paginator($query);
    
    // Configurar límites de paginación
    $paginator->getQuery()
        ->setFirstResult($limit * ($currentPage - 1))
        ->setMaxResults($limit);

    // 4. Calcular totales
    $totalPayments = count($paginator);
    $maxPages = ceil($totalPayments / $limit);

    // 5. Renderizar
    return $this->render('payment/index.html.twig', [
        'payments' => $paginator,
        'maxPages' => $maxPages,
        'thisPage' => $currentPage,
        'customers' => $customerRepository->findAll(), // Para llenar el select
        'staffMembers' => $staffRepository->findAll(), // Para llenar el select
        'filters' => [
            'cliente' => $customerId,
            'staff' => $staffId,
            'fecha_inicio' => $startDate,
            'fecha_fin' => $endDate,
            'ordenar' => $sortBy
        ]
    ]);
    }


    #[Route('/payment/new', name: 'app_payment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, RentalRepository $rentalRepository): Response
    {
        $payment = new Payment();
        // Ponemos la fecha de actualización automática
        $payment->setLastUpdate(new \DateTime()); 

        // Creamos el formulario usando la clase del Paso 1
        $form = $this->createForm(PaymentType::class, $payment);
        $form->handleRequest($request);

        // Si el usuario envió el formulario y es válido...
        if ($form->isSubmitted() && $form->isValid()) {
            
            // 1. Obtenemos el NÚMERO que escribiste en el formulario
            $rentalId = $form->get('rental')->getData();

            // 2. Si escribiste algo, buscamos el OBJETO real en la base de datos
            if ($rentalId) {
                $rental = $rentalRepository->find($rentalId);
                
                // 3. Si el alquiler existe, se lo asignamos al pago
                if ($rental) {
                    $payment->setRental($rental);
                }
            }

            // Guardar en Base de Datos
            $entityManager->persist($payment);
            $entityManager->flush();

            // Mensaje de éxito (opcional) y redirigir
            $this->addFlash('success', 'Pago registrado exitosamente');
            return $this->redirectToRoute('app_payment_index');
        }

        return $this->render('payment/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/payment/reports', name: 'app_payment_reports', methods: ['GET'])]
    public function reports(PaymentRepository $paymentRepository): Response
    {
        // 1. Obtener los datos crudos del repositorio
        $rawData = $paymentRepository->getMonthlyIncome();

        // 2. Preparar los datos para el Gráfico (Chart.js necesita arrays separados)
        $labels = []; // Ejes X (Meses)
        $data = [];   // Ejes Y (Dinero)

        // Recorremos los datos (invertimos para que el gráfico vaya de izq a derecha cronológicamente)
        foreach (array_reverse($rawData) as $row) {
            $labels[] = $row['period'];
            $data[] = $row['total'];
        }

        return $this->render('payment/reports.html.twig', [
            'reportData' => $rawData,     // Para la tabla
            'chartLabels' => json_encode($labels), // Para el gráfico (JSON)
            'chartData'   => json_encode($data),   // Para el gráfico (JSON)
        ]);
    }



    #[Route('/payment/{paymentId}', name: 'app_payment_show', methods: ['GET'])]
    public function show(PaymentRepository $paymentRepository, int $paymentId): Response
    {
        // 1. Buscamos el pago manualmente por su ID
        $payment = $paymentRepository->find($paymentId);

        // 2. Si no existe (ej. usuario pone ID 99999), lanzamos error 404
        if (!$payment) {
            throw $this->createNotFoundException('El pago no existe');
        }
        // 3. Renderizamos la plantilla y le pasamos el pago
        return $this->render('payment/show.html.twig', [
            'payment' => $payment,
        ]);
    }
}