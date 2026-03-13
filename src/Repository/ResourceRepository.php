<?php

namespace App\Repository;

use App\Entity\Resource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ResourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Resource::class);
    }

    public function findFeed(
        ?string $q,
        string $sort = 'new',
        int $limit = 20,
        ?int $resourceTypeId = null,
        ?int $relationKindId = null
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.comments', 'c')
            ->leftJoin('r.ressourceType', 'rt')
            ->leftJoin('r.relationKind', 'rk')
            ->addSelect('COUNT(c.id) AS commentsCount')
            ->groupBy('r.id, rt.id, rk.id')
            ->setMaxResults($limit);

        if ($q) {
            $qb->andWhere('LOWER(r.title) LIKE :q OR LOWER(r.content) LIKE :q OR LOWER(r.externalUrl) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        if ($resourceTypeId) {
            $qb->andWhere('rt.id = :resourceTypeId')
                ->setParameter('resourceTypeId', $resourceTypeId);
        }

        if ($relationKindId) {
            $qb->andWhere('rk.id = :relationKindId')
                ->setParameter('relationKindId', $relationKindId);
        }

        if ($sort === 'top') {
            $qb->orderBy('r.likesCount', 'DESC')
                ->addOrderBy('commentsCount', 'DESC')
                ->addOrderBy('r.createdAt', 'DESC');
        } else {
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