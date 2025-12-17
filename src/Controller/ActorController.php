<?php

namespace App\Controller;

use App\Entity\Actor;
use App\Entity\Film;
use App\Entity\FilmActor;
use App\Repository\ActorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\Tools\Pagination\Paginator;

#[Route('/actor')]
class ActorController extends AbstractController
{
    // ===================== LISTAR + BUSCAR + ORDENAR + PAGINACIÓN =====================
    #[Route('', name: 'actor_index', methods: ['GET'])]
    public function index(
        ActorRepository $actorRepository,
        Request $request
    ): Response
    {
        $q     = $request->query->get('q');
        $order = $request->query->get('order', 'asc');
        $page  = max(1, $request->query->getInt('page', 1));
        $limit = 10; // Número de actores por página

        $qb = $actorRepository->createQueryBuilder('a');

        if ($q) {
            $qb->where('a.firstName LIKE :q OR a.lastName LIKE :q')
               ->setParameter('q', "%$q%");
        }

        $qb->orderBy('a.lastName', $order === 'desc' ? 'DESC' : 'ASC');

        // Aplicar paginación
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $paginator = new Paginator($qb->getQuery());
        $totalItems = count($paginator);
        $totalPages = ceil($totalItems / $limit);

        return $this->render('actor/index.html.twig', [
            'actors' => $paginator,
            'query'  => $q,
            'order'  => $order,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'limit' => $limit,
        ]);
    }

    // ===================== CREAR NUEVO ACTOR =====================
    #[Route('/new', name: 'actor_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $firstName = $request->request->get('first_name');
            $lastName = $request->request->get('last_name');

            // Validar que los campos no estén vacíos
            if (empty($firstName) || empty($lastName)) {
                $this->addFlash('error', 'El nombre y apellido son obligatorios');
                return $this->render('actor/new.html.twig');
            }

            $actor = new Actor();
            $actor->setFirstName($firstName);
            $actor->setLastName($lastName);

            $em->persist($actor);
            $em->flush();

            $this->addFlash('success', 'Actor creado exitosamente!');
            return $this->redirectToRoute('actor_show', [
                'id' => $actor->getActorId()
            ]);
        }

        return $this->render('actor/new.html.twig');
    }

    // ===================== VER ACTOR + FILMOGRAFÍA + ESTADÍSTICAS =====================
    #[Route('/{id}', name: 'actor_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        EntityManagerInterface $em
    ): Response
    {
        $actor = $em->getRepository(Actor::class)->find($id);

        if (!$actor) {
            throw $this->createNotFoundException('Actor no encontrado');
        }

        $conn = $em->getConnection();

        // 🎬 Filmografía
        $films = $conn->fetchAllAssociative(
            'SELECT 
                f.film_id      AS id,
                f.title        AS title,
                f.release_year AS releaseYear
            FROM film f
            JOIN film_actor fa ON f.film_id = fa.film_id
            WHERE fa.actor_id = :id
            ORDER BY f.release_year DESC',
            ['id' => $actor->getActorId()]
        );

        // 📊 ESTADÍSTICAS
        
        // Total de películas
        $totalFilms = count($films);

        // Categorías más frecuentes
        $categoriesStats = $conn->fetchAllAssociative(
            'SELECT 
                c.name AS categoryName,
                COUNT(*) AS filmCount
            FROM film f
            JOIN film_actor fa ON f.film_id = fa.film_id
            JOIN film_category fc ON f.film_id = fc.film_id
            JOIN category c ON fc.category_id = c.category_id
            WHERE fa.actor_id = :id
            GROUP BY c.category_id, c.name
            ORDER BY filmCount DESC
            LIMIT 5',
            ['id' => $actor->getActorId()]
        );

        // Año con más películas
        $yearStats = $conn->fetchAllAssociative(
            'SELECT 
                f.release_year AS year,
                COUNT(*) AS filmCount
            FROM film f
            JOIN film_actor fa ON f.film_id = fa.film_id
            WHERE fa.actor_id = :id AND f.release_year IS NOT NULL
            GROUP BY f.release_year
            ORDER BY filmCount DESC
            LIMIT 1',
            ['id' => $actor->getActorId()]
        );

        $mostProductiveYear = $yearStats[0] ?? null;

        // Promedio de duración de películas
        $durationAvg = $conn->fetchAssociative(
            'SELECT 
                AVG(f.length) AS avgDuration,
                MIN(f.length) AS minDuration,
                MAX(f.length) AS maxDuration
            FROM film f
            JOIN film_actor fa ON f.film_id = fa.film_id
            WHERE fa.actor_id = :id AND f.length IS NOT NULL',
            ['id' => $actor->getActorId()]
        );

        // Rating más común
        $ratingStats = $conn->fetchAllAssociative(
            'SELECT 
                f.rating,
                COUNT(*) AS count
            FROM film f
            JOIN film_actor fa ON f.film_id = fa.film_id
            WHERE fa.actor_id = :id AND f.rating IS NOT NULL
            GROUP BY f.rating
            ORDER BY count DESC
            LIMIT 1',
            ['id' => $actor->getActorId()]
        );

        $mostCommonRating = $ratingStats[0] ?? null;

        // Todas las películas disponibles para asignar
        $allFilms = $conn->fetchAllAssociative(
            'SELECT film_id AS id, title FROM film ORDER BY title ASC'
        );

        // IDs de películas ya asignadas
        $assignedFilmIds = array_column($films, 'id');

        return $this->render('actor/show.html.twig', [
            'actor' => $actor,
            'films' => $films,
            'totalFilms' => $totalFilms,
            'allFilms' => $allFilms,
            'assignedFilmIds' => $assignedFilmIds,
            // Estadísticas
            'stats' => [
                'totalFilms' => $totalFilms,
                'categories' => $categoriesStats,
                'mostProductiveYear' => $mostProductiveYear,
                'avgDuration' => $durationAvg ? round($durationAvg['avgDuration']) : 0,
                'minDuration' => $durationAvg['minDuration'] ?? 0,
                'maxDuration' => $durationAvg['maxDuration'] ?? 0,
                'mostCommonRating' => $mostCommonRating,
            ]
        ]);
    }

    // ===================== EDITAR ACTOR =====================
    #[Route('/{id}/edit', name: 'actor_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        $actor = $em->getRepository(Actor::class)->find($id);

        if (!$actor) {
            throw $this->createNotFoundException('Actor no encontrado');
        }

        if ($request->isMethod('POST')) {
            $firstName = $request->request->get('firstName');
            $lastName = $request->request->get('lastName');

            // Validar que los campos no estén vacíos
            if (empty($firstName) || empty($lastName)) {
                $this->addFlash('error', 'El nombre y apellido son obligatorios');
                return $this->render('actor/edit.html.twig', [
                    'actor' => $actor
                ]);
            }

            $actor->setFirstName($firstName);
            $actor->setLastName($lastName);

            $em->flush();

            $this->addFlash('success', 'Actor actualizado correctamente');

            return $this->redirectToRoute('actor_show', [
                'id' => $actor->getActorId()
            ]);
        }

        return $this->render('actor/edit.html.twig', [
            'actor' => $actor
        ]);
    }

   // ===================== ELIMINAR ACTOR =====================
#[Route('/{id}/delete', name: 'actor_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
public function delete(
    Request $request,
    int $id,
    EntityManagerInterface $em
): Response
{
    $actor = $em->getRepository(Actor::class)->find($id);

    if (!$actor) {
        throw $this->createNotFoundException('Actor no encontrado');
    }

    if (!$this->isCsrfTokenValid(
        'delete_actor_'.$actor->getActorId(),
        $request->request->get('_token')
    )) {
        $this->addFlash('error', 'Token inválido');
        return $this->redirectToRoute('actor_index');
    }

    // 🚨 AQUÍ VA EL MENSAJE
    if ($actor->getFilmCount() > 0) {
        $this->addFlash(
            'warning',
            'No se puede eliminar el actor porque está asociado a una o más películas.'
        );

        return $this->redirectToRoute('actor_index');
    }

    // ✅ SOLO SI NO TIENE PELÍCULAS
    $em->remove($actor);
    $em->flush();

    $this->addFlash('success', 'Actor eliminado correctamente');
    return $this->redirectToRoute('actor_index');
}


// ===================== ASIGNAR PELÍCULA A ACTOR =====================
    #[Route('/{id}/assign-film', name: 'actor_assign_film', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function assignFilm(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response 
    {
        $actor = $em->getRepository(Actor::class)->find($id);
        $filmId = $request->request->get('film_id');

        if ($actor && $filmId) {
            $film = $em->getRepository(Film::class)->find($filmId);
            
            if ($film) {
                // Verificar que no exista ya la relación
                $existing = $em->getRepository(FilmActor::class)->findOneBy([
                    'actor' => $actor,
                    'film' => $film
                ]);

                if (!$existing) {
                    $filmActor = new FilmActor();
                    $filmActor->setActor($actor);
                    $filmActor->setFilm($film);

                    $em->persist($filmActor);
                    $em->flush();

                    $this->addFlash('success', 'Película asignada correctamente');
                } else {
                    $this->addFlash('warning', 'Esta película ya está asignada al actor');
                }
            }
        }

        return $this->redirectToRoute('actor_show', ['id' => $id]);
    }

    // ===================== REMOVER PELÍCULA DE ACTOR =====================
    #[Route('/{actorId}/remove-film/{filmId}', name: 'actor_remove_film', methods: ['POST'])]
    public function removeFilm(
        int $actorId,
        int $filmId,
        EntityManagerInterface $em
    ): Response 
    {
        $actor = $em->getRepository(Actor::class)->find($actorId);
        $film = $em->getRepository(Film::class)->find($filmId);

        if ($actor && $film) {
            $filmActor = $em->getRepository(FilmActor::class)->findOneBy([
                'actor' => $actor,
                'film' => $film
            ]);

            if ($filmActor) {
                $em->remove($filmActor);
                $em->flush();
                $this->addFlash('success', 'Película removida correctamente');
            }
        }

        return $this->redirectToRoute('actor_show', ['id' => $actorId]);
    }
}