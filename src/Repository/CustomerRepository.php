<?php

namespace App\Repository;

use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    /**
     * Búsqueda con filtros:
     * - $search  : nombre, apellido o email
     * - $storeId : id de tienda
     * - $country : nombre de país
     * - $city    : nombre de ciudad
     * - $status  : 'active' | 'inactive' | null
     *
     * @return Customer[]
     */
    public function findByFilters(
        ?string $search,
        ?int $storeId,
        ?string $country,
        ?string $city,
        ?string $status
    ): array {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.address', 'a')
            ->leftJoin('a.city', 'ci')
            ->leftJoin('ci.country', 'co')
            ->addSelect('a', 'ci', 'co');

        if ($search) {
            $qb->andWhere('c.firstName LIKE :term OR c.lastName LIKE :term OR c.email LIKE :term')
               ->setParameter('term', '%' . $search . '%');
        }

        if ($storeId) {
            $qb->andWhere('c.store = :store')
               ->setParameter('store', $storeId);
        }

        if ($country) {
            $qb->andWhere('co.country LIKE :country')
               ->setParameter('country', '%' . $country . '%');
        }

        if ($city) {
            $qb->andWhere('ci.city LIKE :city')
               ->setParameter('city', '%' . $city . '%');
        }

        if ($status === 'active') {
            $qb->andWhere('c.active = :active')
               ->setParameter('active', true);
        } elseif ($status === 'inactive') {
            $qb->andWhere('c.active = :inactive')
               ->setParameter('inactive', false);
        }

        return $qb
            ->orderBy('c.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
