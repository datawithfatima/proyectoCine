<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FilmController extends AbstractController
{
    // ==================== LISTAR PELÍCULAS ====================
    #[Route('/film', name: 'film_index')]
public function index(Request $request): Response
{
    // Parámetros de filtro
    $search = $request->query->get('search', '');
    $year = $request->query->get('year', '');
    $rating = $request->query->get('rating', '');
    $category = $request->query->get('category', '');
    $page = max(1, (int) $request->query->get('page', 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $films = [];
    $totalFilms = 0;
    $categories = [];
    
    $mysqli = @new \mysqli('127.0.0.1', 'root', '', 'sakila');
    
    if (!$mysqli->connect_errno) {
        // Construir consulta con filtros
        $whereConditions = [];
        $params = [];
        $types = '';
        
        if ($search) {
            $whereConditions[] = "(title LIKE ? OR description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $types .= 'ss';
        }
        
        if ($year) {
            $whereConditions[] = "release_year = ?";
            $params[] = $year;
            $types .= 'i';
        }
        
        if ($rating) {
            $whereConditions[] = "rating = ?";
            $params[] = $rating;
            $types .= 's';
        }
        
        if ($category) {
            $whereConditions[] = "film_id IN (
                SELECT film_id FROM film_category 
                WHERE category_id = ?
            )";
            $params[] = $category;
            $types .= 'i';
        }
        
        $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Consulta para obtener películas
        $query = "
            SELECT 
                film_id as id, 
                title, 
                description, 
                release_year as releaseYear,
                rental_rate as rentalRate,
                length,
                rating,
                (SELECT GROUP_CONCAT(c.name SEPARATOR ', ') 
                 FROM film_category fc 
                 JOIN category c ON fc.category_id = c.category_id 
                 WHERE fc.film_id = film.film_id) as categories
            FROM film 
            $whereClause
            ORDER BY title
            LIMIT ? OFFSET ?
        ";
        
        // Contar total
        $countQuery = "SELECT COUNT(*) as total FROM film $whereClause";
        
        $stmt = $mysqli->prepare($query);
        $countStmt = $mysqli->prepare($countQuery);
        
        // Preparar parámetros
        if ($params) {
            $allParams = array_merge($params, [$limit, $offset]);
            $allTypes = $types . 'ii';
            $stmt->bind_param($allTypes, ...$allParams);
            $countStmt->bind_param($types, ...$params);
        } else {
            $stmt->bind_param('ii', $limit, $offset);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $films = $result->fetch_all(MYSQLI_ASSOC);
        
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $totalRow = $countResult->fetch_assoc();
        $totalFilms = $totalRow['total'];
        
        // Obtener categorías para el filtro
        $catsResult = $mysqli->query("
            SELECT category_id as id, name 
            FROM category 
            ORDER BY name
        ");
        if ($catsResult) {
            $categories = $catsResult->fetch_all(MYSQLI_ASSOC);
        }
        
        // Obtener años únicos para filtro
        $yearsResult = $mysqli->query("
            SELECT DISTINCT release_year as year 
            FROM film 
            WHERE release_year IS NOT NULL 
            ORDER BY release_year DESC
        ");
        $years = [];
        if ($yearsResult) {
            $years = $yearsResult->fetch_all(MYSQLI_ASSOC);
        }
        
        $mysqli->close();
    }
    
    // Calcular paginación
    $totalPages = ceil($totalFilms / $limit);
    
    return $this->render('film/index.html.twig', [
        'films' => $films,
        'search' => $search,
        'selectedYear' => $year,
        'selectedRating' => $rating,
        'selectedCategory' => $category,
        'categories' => $categories,
        'years' => $years,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalFilms' => $totalFilms,
        'limit' => $limit
    ]);
}



    // ==================== CREAR NUEVA PELÍCULA ====================
    #[Route('/film/new', name: 'film_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
        // Obtener datos del formulario
        $title = $request->request->get('title');
        $description = $request->request->get('description');
        $releaseYear = (int) $request->request->get('releaseYear');
        $length = (int) $request->request->get('length');
        $rating = $request->request->get('rating');
        $rentalRate = $request->request->get('rentalRate', '4.99');
        
        // VALIDACIÓN DEL AÑO
        if ($releaseYear < 1901 || $releaseYear > 2155) {
            $this->addFlash('error', 'El año debe estar entre 1901 y 2155');
            return $this->redirectToRoute('film_new');
        }
        
        // VALIDACIÓN DE LONGITUD
        if ($length <= 0) {
            $this->addFlash('error', 'La duración debe ser mayor a 0 minutos');
            return $this->redirectToRoute('film_new');
        }
        
        // Insertar en la base de datos
        $mysqli = @new \mysqli('127.0.0.1', 'root', '', 'sakila');
        
        if (!$mysqli->connect_errno) {
            $stmt = $mysqli->prepare('
                INSERT INTO film (title, description, release_year, length, rating, rental_rate, 
                                 rental_duration, replacement_cost, last_update, language_id)
                VALUES (?, ?, ?, ?, ?, ?, 3, 19.99, NOW(), 1)
            ');
            $stmt->bind_param('ssiiss', $title, $description, $releaseYear, $length, $rating, $rentalRate);
            
            if ($stmt->execute()) {
                $newId = $stmt->insert_id;
                $this->addFlash('success', "Película '$title' creada exitosamente (ID: $newId)");
            } else {
                $this->addFlash('error', 'Error al crear la película: ' . $stmt->error);
            }
            
            $mysqli->close();
        } else {
            $this->addFlash('error', 'Error de conexión a la base de datos');
        }
        
        return $this->redirectToRoute('film_index');
    }
    
    return $this->render('film/new.html.twig');
    }

    // ==================== EDITAR PELÍCULA ====================
    #[Route('/film/{id}/edit', name: 'film_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
         $mysqli = @new \mysqli('127.0.0.1', 'root', '', 'sakila');
    $film = null;
    
    if (!$mysqli->connect_errno) {
        // Obtener película actual
        $stmt = $mysqli->prepare('
            SELECT film_id as id, title, description, release_year as releaseYear,
                   length, rating, rental_rate as rentalRate
            FROM film WHERE film_id = ?
        ');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $film = $result->fetch_assoc();
        
        // SI ES POST (formulario enviado)
        if ($request->isMethod('POST')) {
            // Obtener datos del formulario
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $releaseYear = (int) $request->request->get('releaseYear');
            $length = (int) $request->request->get('length');
            $rating = $request->request->get('rating');
            $rentalRate = $request->request->get('rentalRate', '4.99');
            
            // Validar año (1901-2155 para MySQL YEAR)
            if ($releaseYear < 1901 || $releaseYear > 2155) {
                // Puedes añadir mensaje de error aquí
                return $this->redirectToRoute('film_edit', ['id' => $id]);
            }
            
            // ACTUALIZAR en la base de datos
            $updateStmt = $mysqli->prepare('
                UPDATE film SET 
                    title = ?, 
                    description = ?, 
                    release_year = ?, 
                    length = ?, 
                    rating = ?, 
                    rental_rate = ?,
                    last_update = NOW()
                WHERE film_id = ?
            ');
            $updateStmt->bind_param('ssiissi', 
                $title, 
                $description, 
                $releaseYear, 
                $length, 
                $rating, 
                $rentalRate, 
                $id
            );
            
            if ($updateStmt->execute()) {
                // Redirigir a la vista de la película
                return $this->redirectToRoute('film_show', ['id' => $id]);
            } else {
                // Error en la actualización
                // Puedes manejar el error aquí
            }
        }
        
        $mysqli->close();
    }
    
    if (!$film) {
        throw $this->createNotFoundException('Película no encontrada');
    }
    
    return $this->render('film/edit.html.twig', [
        'film' => $film
    ]);
    }

    // ==================== ELIMINAR PELÍCULA ====================
#[Route('/film/{id}/delete', name: 'film_delete', methods: ['POST'])]
public function delete(int $id, Request $request): Response
{
    if ($this->isCsrfTokenValid('delete'.$id, $request->request->get('_token'))) {
        $mysqli = @new \mysqli('127.0.0.1', 'root', '', 'sakila');
        
        if (!$mysqli->connect_errno) {
            // Primero obtenemos el título para el mensaje
            $stmt = $mysqli->prepare('SELECT title FROM film WHERE film_id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $film = $result->fetch_assoc();
            
            if ($film) {
                // Eliminar relaciones primero (film_actor, film_category)
                $mysqli->query("DELETE FROM film_actor WHERE film_id = $id");
                $mysqli->query("DELETE FROM film_category WHERE film_id = $id");
                
                // Luego eliminar la película
                $deleteStmt = $mysqli->prepare('DELETE FROM film WHERE film_id = ?');
                $deleteStmt->bind_param('i', $id);
                
                if ($deleteStmt->execute()) {
                    $this->addFlash('success', "Película '{$film['title']}' eliminada exitosamente");
                } else {
                    $this->addFlash('error', 'Error al eliminar la película');
                }
            } else {
                $this->addFlash('error', 'La película no existe');
            }
            
            $mysqli->close();
        }
    }
    
    return $this->redirectToRoute('film_index');
}


    // ==================== VER DETALLES ====================
    #[Route('/film/{id}', name: 'film_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $mysqli = @new \mysqli('127.0.0.1', 'root', '', 'sakila');
    $film = null;
    $actors = [];
    $categories = [];
    $language = null;
    $inventory = [];
    
    if (!$mysqli->connect_errno) {
        // 1. Obtener película + idioma
        $stmt = $mysqli->prepare('
            SELECT 
                f.film_id as id, 
                f.title, 
                f.description, 
                f.release_year as releaseYear,
                f.length,
                f.rating,
                f.rental_rate as rentalRate,
                f.replacement_cost as replacementCost,
                f.special_features as specialFeatures,
                l.name as languageName
            FROM film f
            LEFT JOIN language l ON f.language_id = l.language_id
            WHERE f.film_id = ?
        ');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $film = $result->fetch_assoc();
        
        // 2. Obtener actores
        $actorsQuery = $mysqli->query("
            SELECT 
                a.actor_id as id, 
                CONCAT(a.first_name, ' ', a.last_name) as name,
                COUNT(fa2.film_id) as filmCount
            FROM film_actor fa
            JOIN actor a ON fa.actor_id = a.actor_id
            LEFT JOIN film_actor fa2 ON a.actor_id = fa2.actor_id
            WHERE fa.film_id = $id
            GROUP BY a.actor_id
            ORDER BY filmCount DESC
        ");
        if ($actorsQuery) {
            $actors = $actorsQuery->fetch_all(MYSQLI_ASSOC);
        }
        
        // 3. Obtener categorías
        $catsQuery = $mysqli->query("
            SELECT 
                c.category_id as id, 
                c.name,
                COUNT(fc2.film_id) as filmCount
            FROM film_category fc
            JOIN category c ON fc.category_id = c.category_id
            LEFT JOIN film_category fc2 ON c.category_id = fc2.category_id
            WHERE fc.film_id = $id
            GROUP BY c.category_id
        ");
        if ($catsQuery) {
            $categories = $catsQuery->fetch_all(MYSQLI_ASSOC);
        }
        
        // 4. Obtener inventario (opcional)
        $invQuery = $mysqli->query("
            SELECT 
                inventory_id as id,
                store_id as storeId,
                last_update as lastUpdate
            FROM inventory
            WHERE film_id = $id
            LIMIT 10
        ");
        if ($invQuery) {
            $inventory = $invQuery->fetch_all(MYSQLI_ASSOC);
        }
        
        $mysqli->close();
    }
    
    if (!$film) {
        throw $this->createNotFoundException('Película no encontrada');
    }
    
    return $this->render('film/show.html.twig', [
        'film' => $film,
        'actors' => $actors,
        'categories' => $categories,
        'language' => $film['languageName'],
        'inventory' => $inventory
    ]);
    }
}