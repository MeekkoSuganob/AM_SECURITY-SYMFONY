<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
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

    /**
     * Orders matching an order number or part of a username / email / plan name
     * (every order when the search text is empty).
     *
     * @return list<Order>
     */
    public function search(?string $term): array
    {
        $qb = $this->createQueryBuilder('o')
            ->join('o.user', 'u')
            ->addSelect('u')
            ->orderBy('o.createdAt', 'DESC')
            ->addOrderBy('o.id', 'DESC');

        if ($term !== null && $term !== '') {
            $qb->andWhere('u.username LIKE :term OR u.email LIKE :term OR o.planName LIKE :term OR o.id = :id')
                ->setParameter('term', '%'.addcslashes($term, '%_\\').'%')
                ->setParameter('id', ctype_digit($term) ? (int) $term : 0);
        }

        return $qb->getQuery()->getResult();
    }

    /** Orders placed since the given moment (used for "orders today"). */
    public function countSince(\DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.createdAt >= :since')
            ->setParameter('since', $since, Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
