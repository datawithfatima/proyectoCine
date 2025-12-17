<?php

namespace App\Repository;

use App\Entity\Rental;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RentalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rental::class);
    }

    /**
     * Búsqueda con filtros:
     * - $customerName : nombre / apellido cliente
     * - $filmTitle    : título de película
     * - $status       : 'active' | 'returned' | null
     * - $from         : fecha inicio (rental_date)
     * - $to           : fecha fin (rental_date)
     *
     * @return Rental[]
     */
    public function search(
        ?string $customerName,
        ?string $filmTitle,
        ?string $status,
        ?\DateTimeInterface $from,
        ?\DateTimeInterface $to
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->join('r.customer', 'c')
            ->join('r.inventory', 'i')
            ->join('i.film', 'f')
            ->join('i.store', 's')
            ->addSelect('c', 'i', 'f', 's');

        if ($customerName) {
            $qb->andWhere('c.firstName LIKE :cname OR c.lastName LIKE :cname')
               ->setParameter('cname', '%' . $customerName . '%');
        }

        if ($filmTitle) {
            $qb->andWhere('f.title LIKE :ftitle')
               ->setParameter('ftitle', '%' . $filmTitle . '%');
        }

        if ($status === 'active') {
            $qb->andWhere('r.returnDate IS NULL');
        } elseif ($status === 'returned') {
            $qb->andWhere('r.returnDate IS NOT NULL');
        }

        if ($from) {
            $qb->andWhere('r.rentalDate >= :from')
               ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('r.rentalDate <= :to')
               ->setParameter('to', $to);
        }

        return $qb
            ->orderBy('r.rentalDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
