<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Resource;
use App\Entity\ResourceLike;
use App\Entity\User;
use App\Entity\UserResourceState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DashboardStatsQueryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Resource::class);
    }

    public function countResources(?\DateTimeInterface $since = null): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(Resource::class, 'r');

        if ($since) {
            $qb->andWhere('r.createdAt >= :since')
                ->setParameter('since', $since);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countActiveUsers(?\DateTimeInterface $since = null): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u');

        if ($since) {
            $qb->andWhere('u.createdAt >= :since')
                ->setParameter('since', $since);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countComments(?\DateTimeInterface $since = null): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(Comment::class, 'c');

        if ($since) {
            $qb->andWhere('c.createdAt >= :since')
                ->setParameter('since', $since);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countFavorites(?\DateTimeInterface $since = null): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(s.id)')
            ->from(UserResourceState::class, 's')
            ->andWhere('s.isSaved = :saved')
            ->setParameter('saved', true);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countShares(?\DateTimeInterface $since = null): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('COALESCE(SUM(r.sharesCount), 0)')
            ->from(Resource::class, 'r');

        if ($since) {
            $qb->andWhere('r.createdAt >= :since')
                ->setParameter('since', $since);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countLikes(?\DateTimeInterface $since = null): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(l.id)')
            ->from(ResourceLike::class, 'l');

        if ($since) {
            $qb->andWhere('l.createdAt >= :since')
                ->setParameter('since', $since);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countExploitedResources(?\DateTimeInterface $since = null): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(s.id)')
            ->from(UserResourceState::class, 's')
            ->andWhere('s.isExploited = :exploited')
            ->setParameter('exploited', true);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getCategoryDistribution(?\DateTimeInterface $since = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT
                COALESCE(c.name, \'Sans catégorie\') AS label,
                COUNT(r.id) AS total
            FROM resource r
            LEFT JOIN category c ON c.id = r.category_id
        ';

        $params = [];

        if ($since) {
            $sql .= ' WHERE r.created_at >= :since';
            $params['since'] = $since->format('Y-m-d H:i:s');
        }

        $sql .= '
            GROUP BY c.id, c.name
            ORDER BY total DESC, label ASC
        ';

        try {
            $rows = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            return [
                'items' => array_map(static function (array $row): array {
                    return [
                        'label' => (string) $row['label'],
                        'total' => (int) $row['total'],
                    ];
                }, $rows),
                'source' => 'category',
            ];
        } catch (\Throwable) {
            return [
                'items' => [],
                'source' => 'category',
            ];
        }
    }

    public function getTopEngagedResources(?\DateTimeInterface $since = null, int $limit = 5): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('r.id, r.title, r.likesCount, r.sharesCount, COUNT(c.id) AS commentsCount')
            ->addSelect('(COALESCE(r.likesCount, 0) + COALESCE(r.sharesCount, 0) + COUNT(c.id)) AS HIDDEN engagementScore')
            ->from(Resource::class, 'r')
            ->leftJoin(Comment::class, 'c', 'WITH', 'c.resource = r')
            ->groupBy('r.id, r.title, r.likesCount, r.sharesCount')
            ->setMaxResults($limit);

        if ($since) {
            $qb->andWhere('r.createdAt >= :since')
                ->setParameter('since', $since);
        }

        $qb->orderBy('engagementScore', 'DESC')
            ->addOrderBy('r.createdAt', 'DESC');

        $rows = $qb->getQuery()->getArrayResult();

        return array_map(static function (array $row): array {
            $likes = (int) ($row['likesCount'] ?? 0);
            $shares = (int) ($row['sharesCount'] ?? 0);
            $comments = (int) ($row['commentsCount'] ?? 0);

            return [
                'id' => (int) $row['id'],
                'title' => (string) $row['title'],
                'likes' => $likes,
                'shares' => $shares,
                'comments' => $comments,
                'engagementScore' => $likes + $shares + $comments,
            ];
        }, $rows);
    }

    public function countRowsInDateWindow(
        string $table,
        string $dateColumn,
        \DateTimeInterface $start,
        \DateTimeInterface $end
    ): int {
        $allowedTables = ['share', 'comment'];
        $allowedColumns = ['created_at'];

        if (!in_array($table, $allowedTables, true) || !in_array($dateColumn, $allowedColumns, true)) {
            throw new \InvalidArgumentException('Table ou colonne non autorisée pour les statistiques.');
        }

        $conn = $this->getEntityManager()->getConnection();

        $sql = sprintf(
            'SELECT COUNT(*) AS total FROM %s WHERE %s >= :start AND %s <= :end',
            $table,
            $dateColumn,
            $dateColumn
        );

        return (int) $conn->executeQuery($sql, [
            'start' => $start->format('Y-m-d 00:00:00'),
            'end' => $end->format('Y-m-d 23:59:59'),
        ])->fetchOne();
    }
}