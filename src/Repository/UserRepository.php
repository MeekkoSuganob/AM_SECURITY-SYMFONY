<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used by Symfony to re-hash a password automatically over time
     * if the hashing algorithm gets stronger.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Users whose username or email contains the search text (everyone when it is empty).
     *
     * @return list<User>
     */
    public function search(?string $term): array
    {
        $qb = $this->createQueryBuilder('u')->orderBy('u.createdAt', 'DESC');

        if ($term !== null && $term !== '') {
            $qb->andWhere('u.username LIKE :term OR u.email LIKE :term')
                ->setParameter('term', '%'.addcslashes($term, '%_\\').'%');
        }

        return $qb->getQuery()->getResult();
    }
}
