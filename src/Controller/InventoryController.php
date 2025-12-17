<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InventoryController extends AbstractController
{
    // ==================== INVENTARIO - LISTADO ====================
    #[Route('/inventory', name: 'inventory_index')]
    public function index(Request $request): Response
    {
        $storeId = $request->query->get('store', '');
        $filmId = $request->query->get('film', '');
        $search = $request->query->get('search', '');
        
        $inventory = [];
        $stores = [];
        
        $mysqli = @new \mysqli('127.0.0.1', 'root', '', 'sakila');
        
        if (!$mysqli->connect_errno) {
            // Obtener tiendas
            $storesResult = $mysqli->query("SELECT store_id as id, store_id FROM store");
            $stores = $storesResult->fetch_all(MYSQLI_ASSOC);
            
            // Construir query con filtros
            $whereConditions = [];
            $params = [];
            $types = '';
            
            if ($storeId) {
                $whereConditions[] = "i.store_id = ?";
                $params[] = $storeId;
                $types .= 'i';
            }
            
            if ($filmId) {
                $whereConditions[] = "i.film_id = ?";
                $params[] = $filmId;
                $types .= 'i';
            }
            
            if ($search) {
                $whereConditions[] = "f.title LIKE ?";
                $params[] = "%$search%";
                $types .= 's';
            }
            
            $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Obtener inventario agrupado
            $query = "
                SELECT 
                    f.film_id,
                    f.title,
                    i.store_id,
                    COUNT(i.inventory_id) as total_copies,
                    SUM(CASE WHEN r.return_date IS NULL THEN 1 ELSE 0 END) as rented_copies,
                    COUNT(i.inventory_id) - SUM(CASE WHEN r.return_date IS NULL THEN 1 ELSE 0 END) as available_copies,
                    f.rental_rate
                FROM inventory i
                JOIN film f ON i.film_id = f.film_id
                LEFT JOIN rental r ON i.inventory_id = r.inventory_id AND r.return_date IS NULL
                $whereClause
                GROUP BY f.film_id, i.store_id
                ORDER BY f.title, i.store_id
            ";
            
            if ($params) {
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
                $inventory = $result->fetch_all(MYSQLI_ASSOC);
            } else {
                $result = $mysqli->query($query);
                $inventory = $result->fetch_all(MYSQLI_ASSOC);
            }
            
            $mysqli->close();
        }
        
        return $this->render('inventory/index.html.twig', [
            'inventory' => $inventory,
            'stores' => $stores,
            'selectedStore' => $storeId,
            'selectedFilm' => $filmId,
            'search' => $search
        ]);
    }

    // ==================== AGREGAR COPIAS ====================
    #[Route('/inventory/add-copies', name: 'inventory_add_copies', methods: ['GET', 'POST'])]
    public function addCopies(Request $request): Response
    {
        $mysqli = @new \mysqli('127.0.0.1', 'root', '', 'sakila');
        $films = [];
        $stores = [];
        
        if (!$mysqli->connect_errno) {
            // Obtener películas
            $filmsResult = $mysqli->query("SELECT film_id as id, title FROM film ORDER BY title");
            $films = $filmsResult->fetch_all(MYSQLI_ASSOC);
            
            // Obtener tiendas
            $storesResult = $mysqli->query("SELECT store_id as id, store_id FROM store");
            $stores = $storesResult->fetch_all(MYSQLI_ASSOC);
            
            if ($request->isMethod('POST')) {
                $filmId = (int) $request->request->get('film_id');
                $storeId = (int) $request->request->get('store_id');
                $copies = (int) $request->request->get('copies');
                
                if ($filmId && $storeId && $copies > 0) {
                    $stmt = $mysqli->prepare("
                        INSERT INTO inventory (film_id, store_id, last_update)
                        VALUES (?, ?, NOW())
                    ");
                    
                    $success = true;
                    for ($i = 0; $i < $copies; $i++) {
                        $stmt->bind_param('ii', $filmId, $storeId);
                        if (!$stmt->execute()) {
                            $success = false;
                            break;
                        }
                    }
                    
                    if ($success) {
                        $this->addFlash('success', "Se agregaron $copies copias exitosamente");
                    } else {
                        $this->addFlash('error', 'Error al agregar copias');
                    }
                    
                    return $this->redirectToRoute('inventory_index');
                }
            }
            
            $mysqli->close();
        }
        
        return $this->render('inventory/add_copies.html.twig', [
            'films' => $films,
            'stores' => $stores
        ]);
    }

    // ==================== TRANSFERIR ENTRE TIENDAS ====================
    #[Route('/inventory/transfer', name: 'inventory_transfer', methods: ['GET', 'POST'])]
    public function transfer(Request $request): Response
    {
        $mysqli = @new \mysqli('127.0.0.1', 'root', '', 'sakila');
        $films = [];
        $stores = [];
        
        if (!$mysqli->connect_errno) {
            // Obtener películas con disponibilidad
            $filmsResult = $mysqli->query("
                SELECT DISTINCT f.film_id as id, f.title 
                FROM film f
                JOIN inventory i ON f.film_id = i.film_id
                ORDER BY f.title
            ");
            $films = $filmsResult->fetch_all(MYSQLI_ASSOC);
            
            // Obtener tiendas
            $storesResult = $mysqli->query("SELECT store_id as id, store_id FROM store");
            $stores = $storesResult->fetch_all(MYSQLI_ASSOC);
            
            if ($request->isMethod('POST')) {
                $filmId = (int) $request->request->get('film_id');
                $fromStore = (int) $request->request->get('from_store');
                $toStore = (int) $request->request->get('to_store');
                $copies = (int) $request->request->get('copies');
                
                if ($filmId && $fromStore && $toStore && $copies > 0 && $fromStore != $toStore) {
                    // Verificar disponibilidad
                    $checkStmt = $mysqli->prepare("
                        SELECT COUNT(*) as available
                        FROM inventory i
                        LEFT JOIN rental r ON i.inventory_id = r.inventory_id AND r.return_date IS NULL
                        WHERE i.film_id = ? AND i.store_id = ? AND r.rental_id IS NULL
                    ");
                    $checkStmt->bind_param('ii', $filmId, $fromStore);
                    $checkStmt->execute();
                    $available = $checkStmt->get_result()->fetch_assoc()['available'];
                    
                    if ($available >= $copies) {
                        // Obtener IDs de inventario disponibles
                        $getInventoryStmt = $mysqli->prepare("
                            SELECT i.inventory_id
                            FROM inventory i
                            LEFT JOIN rental r ON i.inventory_id = r.inventory_id AND r.return_date IS NULL
                            WHERE i.film_id = ? AND i.store_id = ? AND r.rental_id IS NULL
                            LIMIT ?
                        ");
                        $getInventoryStmt->bind_param('iii', $filmId, $fromStore, $copies);
                        $getInventoryStmt->execute();
                        $inventoryIds = $getInventoryStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        
                        // Actualizar tienda
                        $updateStmt = $mysqli->prepare("
                            UPDATE inventory SET store_id = ?, last_update = NOW() WHERE inventory_id = ?
                        ");
                        
                        $transferred = 0;
                        foreach ($inventoryIds as $inv) {
                            $updateStmt->bind_param('ii', $toStore, $inv['inventory_id']);
                            if ($updateStmt->execute()) {
                                $transferred++;
                            }
                        }
                        
                        $this->addFlash('success', "Se transfirieron $transferred copias de tienda $fromStore a tienda $toStore");
                    } else {
                        $this->addFlash('error', "Solo hay $available copias disponibles para transferir");
                    }
                    
                    return $this->redirectToRoute('inventory_index');
                }
            }
            
            $mysqli->close();
        }
        
        return $this->render('inventory/transfer.html.twig', [
            'films' => $films,
            'stores' => $stores
        ]);
    }

    // ==================== DAR DE BAJA COPIAS ====================
    #[Route('/inventory/remove/{inventoryId}', name: 'inventory_remove', methods: ['POST'])]
    public function remove(int $inventoryId, Request $request): Response
    {
        if ($this->isCsrfTokenValid('remove'.$inventoryId, $request->request->get('_token'))) {
            $mysqli = @new \mysqli('127.0.0.1', 'root', '', 'sakila');
            
            if (!$mysqli->connect_errno) {
                // Verificar que no esté alquilada
                $checkStmt = $mysqli->prepare("
                    SELECT COUNT(*) as rented
                    FROM rental
                    WHERE inventory_id = ? AND return_date IS NULL
                ");
                $checkStmt->bind_param('i', $inventoryId);
                $checkStmt->execute();
                $rented = $checkStmt->get_result()->fetch_assoc()['rented'];
                
                if ($rented == 0) {
                    // Eliminar rentas históricas primero
                    $mysqli->query("DELETE FROM rental WHERE inventory_id = $inventoryId");
                    
                    // Eliminar inventario
                    $deleteStmt = $mysqli->prepare("DELETE FROM inventory WHERE inventory_id = ?");
                    $deleteStmt->bind_param('i', $inventoryId);
                    
                    if ($deleteStmt->execute()) {
                        $this->addFlash('success', 'Copia dada de baja exitosamente');
                    } else {
                        $this->addFlash('error', 'Error al dar de baja la copia');
                    }
                } else {
                    $this->addFlash('error', 'No se puede dar de baja una copia que está alquilada');
                }
                
                $mysqli->close();
            }
        }
        
        return $this->redirectToRoute('inventory_index');
    }

    // ==================== DETALLE DE DISPONIBILIDAD ====================
    #[Route('/inventory/availability/{filmId}', name: 'inventory_availability')]
    public function availability(int $filmId): Response
    {
        $mysqli = @new \mysqli('127.0.0.1', 'root', '', 'sakila');
        $film = null;
        $availability = [];
        
        if (!$mysqli->connect_errno) {
            // Obtener película
            $filmStmt = $mysqli->prepare("SELECT film_id as id, title, rental_rate FROM film WHERE film_id = ?");
            $filmStmt->bind_param('i', $filmId);
            $filmStmt->execute();
            $film = $filmStmt->get_result()->fetch_assoc();
            
            // Obtener disponibilidad detallada por tienda
            $availStmt = $mysqli->prepare("
                SELECT 
                    i.store_id,
                    i.inventory_id,
                    CASE 
                        WHEN r.return_date IS NULL THEN 'Alquilada'
                        ELSE 'Disponible'
                    END as status,
                    r.rental_date,
                    r.return_date,
                    CONCAT(c.first_name, ' ', c.last_name) as customer_name
                FROM inventory i
                LEFT JOIN rental r ON i.inventory_id = r.inventory_id 
                    AND r.rental_id = (
                        SELECT rental_id FROM rental 
                        WHERE inventory_id = i.inventory_id 
                        ORDER BY rental_date DESC LIMIT 1
                    )
                LEFT JOIN customer c ON r.customer_id = c.customer_id
                WHERE i.film_id = ?
                ORDER BY i.store_id, status
            ");
            $availStmt->bind_param('i', $filmId);
            $availStmt->execute();
            $availability = $availStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $mysqli->close();
        }
        
        if (!$film) {
            throw $this->createNotFoundException('Película no encontrada');
        }
        
        return $this->render('inventory/availability.html.twig', [
            'film' => $film,
            'availability' => $availability
        ]);
    }
}
