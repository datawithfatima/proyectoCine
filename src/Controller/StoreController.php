<?php

namespace App\Controller;

// --- 1. AQUÍ ESTABAN FALTANDO LOS IMPORTS ---
use App\Entity\Store;
use App\Repository\CustomerRepository;
use App\Repository\StaffRepository;
use App\Repository\StoreRepository;
use App\Repository\InventoryRepository; // <--- ¡ESTA ES LA CRÍTICA!
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StoreController extends AbstractController
{
    #[Route('/store', name: 'app_store_index')]
    public function index(StoreRepository $storeRepository): Response
    {
        return $this->render('store/index.html.twig', [
            'stores' => $storeRepository->findAll(),
        ]);
    }

    #[Route('/store/{storeId}', name: 'app_store_show', methods: ['GET'])]
    public function show(
        int $storeId, 
        StoreRepository $storeRepository, 
        StaffRepository $staffRepo, 
        CustomerRepository $customerRepo,
        EntityManagerInterface $entityManager // Inyección segura
    ): Response
    {
        // 1. Buscar la Tienda
        $store = $storeRepository->find($storeId);
        if (!$store) throw $this->createNotFoundException();

        // 2. Conexión segura
        $conn = $entityManager->getConnection();

        // --- CÁLCULOS (Protegidos con try-catch para que no explote) ---

        // A. Ingresos
        try {
            $sqlIncome = 'SELECT SUM(p.amount) FROM payment p JOIN staff s ON p.staff_id = s.staff_id WHERE s.store_id = :storeId';
            $totalIncome = $conn->executeQuery($sqlIncome, ['storeId' => $storeId])->fetchOne();
        } catch (\Exception $e) {
            $totalIncome = 0;
        }

        // B. Alquileres
        try {
            $sqlRentals = 'SELECT COUNT(*) FROM rental r JOIN staff s ON r.staff_id = s.staff_id WHERE s.store_id = :storeId';
            $totalRentals = $conn->executeQuery($sqlRentals, ['storeId' => $storeId])->fetchOne();
        } catch (\Exception $e) {
            $totalRentals = 0;
        }

        // C. Inventario Disponible (La consulta compleja)
        // Diagrama de lo que estamos consultando:
        // [Store] --(tiene)--> [Inventory] --(se alquila en)--> [Rental]
        try {
            $sqlAvailable = '
                SELECT 
                    (SELECT COUNT(*) FROM inventory WHERE store_id = :storeId) - 
                    (SELECT COUNT(*) FROM rental r JOIN inventory i ON r.inventory_id = i.inventory_id WHERE i.store_id = :storeId AND r.return_date IS NULL) 
                as stock';
            $availableStock = $conn->executeQuery($sqlAvailable, ['storeId' => $storeId])->fetchOne();
        } catch (\Exception $e) {
            // Si falla porque la tabla inventory no existe o algo así, ponemos 0
            $availableStock = 0; 
        }

        // --- DATOS VISUALES ---
        $staffMembers = $staffRepo->findBy(['store' => $store]);
        $customers = $customerRepo->findBy(['store' => $store], ['createDate' => 'DESC'], 10);

        return $this->render('store/show.html.twig', [
            'store' => $store,
            'staff_members' => $staffMembers,
            'customers' => $customers,
            'stats' => [
                'income' => $totalIncome ?? 0,
                'total_rentals' => $totalRentals ?? 0,
                'available_stock' => $availableStock ?? 0
            ]
        ]);
    }
}