<?php

namespace App\Repository;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * The user's subscription that has not expired yet (the one that lasts longest
     * if they have several), or null if they have none.
     */
    public function findActiveFor(User $user): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->andWhere('s.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable(), Types::DATETIME_IMMUTABLE)
            ->orderBy('s.expiresAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** Subscriptions that have not expired yet. */
    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.expiresAt > :now')
            ->setParameter('now', new \DateTimeImmutable(), Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Subscriptions still active today that run out within the next $days days. */
    public function countExpiringWithin(int $days): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.expiresAt > :now')
            ->andWhere('s.expiresAt <= :limit')
            ->setParameter('now', new \DateTimeImmutable(), Types::DATETIME_IMMUTABLE)
            ->setParameter('limit', new \DateTimeImmutable("+{$days} days"), Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Subscriptions matching part of a username / email / plan name, soonest-to-expire first
     * (every subscription when the search text is empty).
     *
     * @return list<Subscription>
     */
    public function search(?string $term): array
    {
        $qb = $this->createQueryBuilder('s')
            ->join('s.user', 'u')
            ->addSelect('u')
            ->orderBy('s.expiresAt', 'ASC');

        if ($term !== null && $term !== '') {
            $qb->andWhere('u.username LIKE :term OR u.email LIKE :term OR s.planName LIKE :term')
                ->setParameter('term', '%'.addcslashes($term, '%_\\').'%');
        }

        return $qb->getQuery()->getResult();
    }
}
