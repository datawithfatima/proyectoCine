<?php

namespace App\Repository;

use App\Entity\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /**
     * Busca pagos con filtros dinámicos
     */
    public function findByFilters($customerId, $staffId, $startDate, $endDate, $sortBy)
    {
        // 1. Crear el QueryBuilder (el constructor de consultas)
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.customer', 'c') // Unimos para poder mostrar datos del cliente si queremos
            ->addSelect('c')
            ->leftJoin('p.staff', 's')
            ->addSelect('s');

        // 2. Filtro por Cliente
        if ($customerId) {
            $qb->andWhere('p.customer = :customer')
            ->setParameter('customer', $customerId);
        }

        // 3. Filtro por Staff
        if ($staffId) {
            $qb->andWhere('p.staff = :staff')
            ->setParameter('staff', $staffId);
        }

        // 4. Filtro por Fechas (Desde - Hasta)
        if ($startDate) {
            $qb->andWhere('p.paymentDate >= :start')
            ->setParameter('start', $startDate . ' 00:00:00');
        }
        if ($endDate) {
            $qb->andWhere('p.paymentDate <= :end')
            ->setParameter('end', $endDate . ' 23:59:59');
        }

        // 5. Ordenamiento
        switch ($sortBy) {
            case 'amount_desc':
                $qb->orderBy('p.amount', 'DESC');
                break;
            case 'amount_asc':
                $qb->orderBy('p.amount', 'ASC');
                break;
            case 'date_asc':
                $qb->orderBy('p.paymentDate', 'ASC');
                break;
            default: // Por defecto: Fecha más reciente primero
                $qb->orderBy('p.paymentDate', 'DESC');
        }

        return $qb->getQuery();
    }


    /**
     * Obtiene la suma de ingresos agrupada por mes (Año-Mes)
     */
    public function getMonthlyIncome(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // SQL directo para agrupar por Año-Mes (YYYY-MM)
        $sql = '
            SELECT DATE_FORMAT(payment_date, "%Y-%m") as period, SUM(amount) as total
            FROM payment
            GROUP BY period
            ORDER BY period DESC
            LIMIT 12
        ';

        $resultSet = $conn->executeQuery($sql);

        // Devuelve un array asociativo: [['period' => '2005-05', 'total' => '400.00'], ...]
        return $resultSet->fetchAllAssociative();
    }

    //    /**
    //     * @return Actor[] Returns an array of Actor objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Actor
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
