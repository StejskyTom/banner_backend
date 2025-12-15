<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findByFullPath(string $fullPath): ?Category
    {
        return $this->findOneBy(['fullPath' => $fullPath]);
    }

    public function findRootCategories(): array
    {
        // Get distinct root category names with aggregated product counts
        $qb = $this->createQueryBuilder('c')
            ->select('c.name, SUM(c.productCount) as totalProducts')
            ->where('c.parent IS NULL')
            ->groupBy('c.name')
            ->orderBy('c.name', 'ASC');

        $results = $qb->getQuery()->getResult();

        // Convert to array format expected by frontend
        return array_map(function($result) {
            return [
                'name' => $result['name'],
                'productCount' => (int) $result['totalProducts']
            ];
        }, $results);
    }

    public function findCategoriesForFeed(\App\Entity\HeurekaFeed $feed): array
    {
        // Get distinct category names with product counts for specific feed
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT DISTINCT c.name, COUNT(DISTINCT p.id) as productCount
            FROM category c
            INNER JOIN product p ON p.category_id = c.id
            WHERE p.feed_id = :feedId
            GROUP BY c.name
            ORDER BY c.name ASC
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['feedId' => $feed->getId()->toBinary()]);

        return $result->fetchAllAssociative();
    }

    public function findByName(string $name): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.name = :name')
            ->andWhere('c.parent IS NULL')
            ->setParameter('name', $name)
            ->getQuery()
            ->getResult();
    }
}
