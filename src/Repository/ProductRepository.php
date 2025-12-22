<?php

namespace App\Repository;

use App\Entity\HeurekaFeed;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findByFeed(HeurekaFeed $feed): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.feed = :feed')
            ->setParameter('feed', $feed->getId()->toBinary())
            ->orderBy('p.productName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByFeedWithFilters(
        HeurekaFeed $feed,
        ?string $search = null,
        ?string $categoryName = null,
        int $limit = 100,
        int $offset = 0,
        string $sort = 'name_asc'
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')  // Eager load category
            ->where('p.feed = :feed')
            ->setParameter('feed', $feed->getId()->toBinary());

        if ($search) {
            $qb->andWhere('p.productName LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($categoryName) {
            $qb->andWhere('c.name = :categoryName')
               ->setParameter('categoryName', $categoryName);
        }

        switch ($sort) {
            case 'name_desc':
                $qb->orderBy('p.productName', 'DESC');
                break;
            case 'price_asc':
                $qb->orderBy('p.priceVat', 'ASC');
                break;
            case 'price_desc':
                $qb->orderBy('p.priceVat', 'DESC');
                break;
            case 'name_asc':
            default:
                $qb->orderBy('p.productName', 'ASC');
                break;
        }

        return $qb->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByFeed(HeurekaFeed $feed): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.feed = :feed')
            ->setParameter('feed', $feed->getId()->toBinary())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByFeedWithFilters(
        HeurekaFeed $feed,
        ?string $search = null,
        ?string $categoryName = null
    ): int {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.feed = :feed')
            ->setParameter('feed', $feed->getId()->toBinary());

        if ($search) {
            $qb->leftJoin('p.category', 'c')
               ->andWhere('p.productName LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($categoryName) {
            if (!$search) {
                $qb->leftJoin('p.category', 'c');
            }
            $qb->andWhere('c.name = :categoryName')
               ->setParameter('categoryName', $categoryName);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findSelectedByFeed(HeurekaFeed $feed): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.feed = :feed')
            ->andWhere('p.isSelected = true')
            ->setParameter('feed', $feed->getId()->toBinary())
            ->orderBy('p.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByFeedAndItemId(HeurekaFeed $feed, string $itemId): ?Product
    {
        return $this->createQueryBuilder('p')
            ->where('p.feed = :feed')
            ->andWhere('p.itemId = :itemId')
            ->setParameter('feed', $feed->getId()->toBinary())
            ->setParameter('itemId', $itemId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function searchByUser(User $user, string $search, int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.feed', 'f')
            ->where('f.user = :user')
            ->andWhere('p.productName LIKE :search OR p.description LIKE :search')
            ->setParameter('user', $user->getId()->toBinary())
            ->setParameter('search', '%' . $search . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
