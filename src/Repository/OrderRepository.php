<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /** Everything customers paid for plans, in whole pesos (the database adds it up for us). */
    public function totalRevenue(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.pricePaid), 0)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
