<?php

namespace App\Repository;

use App\Entity\Resource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Resource>
 */
class ResourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Resource::class);
    }

    public function findFeed(?string $q, string $sort = 'new', int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.comments', 'c')
            ->addSelect('COUNT(c.id) AS commentsCount')
            ->groupBy('r.id')
            ->setMaxResults($limit);

        if ($q) {
            $qb->andWhere('LOWER(r.title) LIKE :q OR LOWER(r.content) LIKE :q OR LOWER(r.externalUrl) LIKE :q')
            ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        if ($sort === 'top') {
            // "Top" = + de likes + de commentaires
            $qb->orderBy('r.likesCount', 'DESC')
            ->addOrderBy('commentsCount', 'DESC')
            ->addOrderBy('r.createdAt', 'DESC');
        } else {
            // "New" = plus récent
            $qb->orderBy('r.createdAt', 'DESC');
        }

        $rows = $qb->getQuery()->getResult();

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'resource' => $row[0],
                'commentsCount' => (int) ($row['commentsCount'] ?? 0),
            ];
        }

        return $items;
    }
}
