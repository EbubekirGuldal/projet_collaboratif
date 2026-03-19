<?php

namespace App\Repository;

use App\Entity\Resource;
use App\Entity\User;
use App\Enum\CommentStatus;
use App\Enum\ResourceStatus;
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
        ?int $relationKindId = null,
        ?int $categoryId = null,
        ?User $user = null
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.comments', 'c', 'WITH', 'c.status = :approvedCommentStatus')
            ->leftJoin('r.ressourceType', 'rt')
            ->leftJoin('r.relationKind', 'rk')
            ->leftJoin('r.category', 'cat')
            ->addSelect('COUNT(c.id) AS commentsCount')
            ->groupBy('r.id, rt.id, rk.id, cat.id')
            ->setMaxResults($limit)
            ->setParameter('approvedCommentStatus', CommentStatus::APPROVED);

        if ($user && (in_array('ROLE_MODERATOR', $user->getRoles(), true) || in_array('ROLE_ADMIN', $user->getRoles(), true))) {
            // Staff can inspect the whole catalog from the feed.
        } elseif ($user) {
            $qb->andWhere('r.resourceStatus IN (:publicStatuses) OR r.user = :currentUser')
                ->setParameter('publicStatuses', [ResourceStatus::PUBLIC, ResourceStatus::SHARED])
                ->setParameter('currentUser', $user);
        } else {
            $qb->andWhere('r.resourceStatus = :publicStatus')
                ->setParameter('publicStatus', ResourceStatus::PUBLIC);
        }

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

        if ($categoryId) {
            $qb->andWhere('cat.id = :categoryId')
                ->setParameter('categoryId', $categoryId);
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
