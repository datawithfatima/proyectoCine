<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // Conectar a la base de datos usando MySQLi
        $mysqli = @new \mysqli('127.0.0.1', 'root', '', 'sakila');
        
        $stats = [
            'total_peliculas' => 0,
            'total_clientes'  => 0,
            'rentas_activas'  => 0,
            'tiendas'         => 0,
            'monthly_income' => 0,
            'top_customers' => [],
            'most_rented' => [],
            'popular_categories' => [],
            'monthly_rentals' => []
        ];
        
        if (!$mysqli->connect_errno) {
            // KPIs Básicos
            // Contar Películas
            $result = $mysqli->query("SELECT COUNT(*) as total FROM film");
            if ($result) {
                $stats['total_peliculas'] = $result->fetch_assoc()['total'];
            }
            
            // Contar Clientes Activos
            $result = $mysqli->query("SELECT COUNT(*) as total FROM customer WHERE active = 1");
            if ($result) {
                $stats['total_clientes'] = $result->fetch_assoc()['total'];
            }
            
            // Contar Rentas Activas (sin fecha de devolución)
            $result = $mysqli->query("SELECT COUNT(*) as total FROM rental WHERE return_date IS NULL");
            if ($result) {
                $stats['rentas_activas'] = $result->fetch_assoc()['total'];
            }
            
            // Contar Tiendas
            $result = $mysqli->query("SELECT COUNT(*) as total FROM store");
            if ($result) {
                $stats['tiendas'] = $result->fetch_assoc()['total'];
            }
            
            // Ingresos del mes actual
            $incomeResult = $mysqli->query("
                SELECT COALESCE(SUM(amount), 0) as total 
                FROM payment 
                WHERE MONTH(payment_date) = MONTH(CURDATE()) 
                AND YEAR(payment_date) = YEAR(CURDATE())
            ");
            if ($incomeResult) {
                $stats['monthly_income'] = $incomeResult->fetch_assoc()['total'];
            }
            
            // Top 5 clientes (más alquileres)
            $topCustomers = $mysqli->query("
                SELECT 
                    c.customer_id as id,
                    CONCAT(c.first_name, ' ', c.last_name) as name,
                    COUNT(r.rental_id) as rental_count,
                    COALESCE(SUM(p.amount), 0) as total_spent
                FROM customer c
                LEFT JOIN rental r ON c.customer_id = r.customer_id
                LEFT JOIN payment p ON r.rental_id = p.rental_id
                GROUP BY c.customer_id, c.first_name, c.last_name
                ORDER BY rental_count DESC
                LIMIT 5
            ");
            if ($topCustomers) {
                $stats['top_customers'] = $topCustomers->fetch_all(MYSQLI_ASSOC);
            }
            
            // Top 5 películas más alquiladas
            $mostRented = $mysqli->query("
                SELECT 
                    f.film_id as id,
                    f.title,
                    COUNT(DISTINCT r.rental_id) as rental_count,
                    f.rental_rate,
                    COUNT(DISTINCT i.inventory_id) as copies
                FROM film f
                LEFT JOIN inventory i ON f.film_id = i.film_id
                LEFT JOIN rental r ON i.inventory_id = r.inventory_id
                GROUP BY f.film_id, f.title, f.rental_rate
                ORDER BY rental_count DESC
                LIMIT 5
            ");
            if ($mostRented) {
                $stats['most_rented'] = $mostRented->fetch_all(MYSQLI_ASSOC);
            }
            
            // Categorías más populares
            $popularCats = $mysqli->query("
                SELECT 
                    c.name,
                    COUNT(DISTINCT r.rental_id) as rental_count
                FROM category c
                JOIN film_category fc ON c.category_id = fc.category_id
                JOIN film f ON fc.film_id = f.film_id
                JOIN inventory i ON f.film_id = i.film_id
                JOIN rental r ON i.inventory_id = r.inventory_id
                GROUP BY c.category_id, c.name
                ORDER BY rental_count DESC
                LIMIT 6
            ");
            if ($popularCats) {
                $stats['popular_categories'] = $popularCats->fetch_all(MYSQLI_ASSOC);
            }
            
            // Alquileres por mes (últimos 6 meses)
            $monthlyRentals = $mysqli->query("
                SELECT 
                    DATE_FORMAT(rental_date, '%Y-%m') as month,
                    COUNT(*) as count,
                    COALESCE(SUM(p.amount), 0) as income
                FROM rental r
                LEFT JOIN payment p ON r.rental_id = p.rental_id
                WHERE rental_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(rental_date, '%Y-%m')
                ORDER BY month
            ");
            if ($monthlyRentals) {
                $stats['monthly_rentals'] = $monthlyRentals->fetch_all(MYSQLI_ASSOC);
            }
            
            $mysqli->close();
        }

        return $this->render('home/index.html.twig', [
            'stats' => $stats,
        ]);
    }
}