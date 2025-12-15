<?php

namespace App\Repository;

use App\Entity\HeurekaFeed;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HeurekaFeed>
 */
class HeurekaFeedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HeurekaFeed::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.user = :user')
            ->setParameter('user', $user->getId(), 'uuid')
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByUserAndId(User $user, string $id): ?HeurekaFeed
    {
        return $this->createQueryBuilder('f')
            ->where('f.user = :user')
            ->andWhere('f.id = :id')
            ->setParameter('user', $user->getId(), 'uuid')
            ->setParameter('id', $id, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
