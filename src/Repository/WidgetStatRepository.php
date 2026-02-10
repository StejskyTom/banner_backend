<?php

namespace App\Repository;

use App\Entity\WidgetStat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<WidgetStat>
 *
 * @method WidgetStat|null find($id, $lockMode = null, $lockVersion = null)
 * @method WidgetStat|null findOneBy(array $criteria, array $orderBy = null)
 * @method WidgetStat[]    findAll()
 * @method WidgetStat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WidgetStatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WidgetStat::class);
    }

    public function trackView(Uuid $widgetId, string $widgetType): void
    {
        $date = new \DateTime('today');
        
        // 1. Try to increment existing record (Atomic update)
        $q = $this->getEntityManager()->createQuery(
            'UPDATE App\Entity\WidgetStat s SET s.views = s.views + 1 WHERE s.widgetId = :id AND s.date = :date'
        );
        $q->setParameter('id', $widgetId, UuidType::NAME);
        $q->setParameter('date', $date, Types::DATE_MUTABLE);
        
        $updated = $q->execute();

        if ($updated > 0) {
            return;
        }

        // 2. If not found, create new
        $stat = new WidgetStat();
        $stat->setWidgetId($widgetId);
        $stat->setWidgetType($widgetType);
        $stat->setDate($date);
        $stat->setViews(1);

        try {
            $this->getEntityManager()->persist($stat);
            $this->getEntityManager()->flush();
        } catch (UniqueConstraintViolationException $e) {
            // Race condition: Record was created by another request in the meantime.
            // We can safely ignore.
        }
    }

    public function getViewsForWidgets(array $ids, ?\DateTimeInterface $date = null): int
    {
        if (empty($ids)) {
            return 0;
        }

        // Convert Uuid objects to binary — MySQL stores UUIDs as BINARY(16)
        $binaryIds = array_map(
            fn($id) => $id instanceof Uuid ? $id->toBinary() : $id,
            $ids
        );

        $qb = $this->createQueryBuilder('s')
            ->select('SUM(s.views)')
            ->where('s.widgetId IN (:ids)')
            ->setParameter('ids', $binaryIds);

        if ($date) {
            $qb->andWhere('s.date = :date')
               ->setParameter('date', $date, Types::DATE_MUTABLE);
        }

        $result = $qb->getQuery()->getSingleScalarResult();

        return (int) $result;
    }
}
