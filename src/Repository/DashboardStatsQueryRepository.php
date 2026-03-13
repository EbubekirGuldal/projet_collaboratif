<?php

namespace App\Repository;

use App\Entity\Resource;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class DashboardStatsQueryRepository
{
    public function __construct(
        private readonly ResourceRepository $resourceRepository,
        private readonly UserRepository $userRepository,
        private readonly ShareRepository $shareRepository,
        private readonly CommentRepository $commentRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    // Compte les ressources, avec filtre de debut optionnel.
    public function countResources(?\DateTimeImmutable $startAt): int
    {
        $qb = $this->resourceRepository->createQueryBuilder('r')->select('COUNT(r.id)');

        return $this->countWithStartAt($qb, $startAt, 'r.createdAt');
    }

    // Compte les utilisateurs actifs sur la periode.
    public function countActiveUsers(?\DateTimeImmutable $startAt): int
    {
        $qb = $this->userRepository
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.isActive = :active')
            ->setParameter('active', true);

        return $this->countWithStartAt($qb, $startAt, 'u.createdAt');
    }

    // Compte les partages sur la periode.
    public function countShares(?\DateTimeImmutable $startAt): int
    {
        $qb = $this->shareRepository->createQueryBuilder('s')->select('COUNT(s.id)');

        return $this->countWithStartAt($qb, $startAt, 's.createdAt');
    }

    // Compte les commentaires sur la periode.
    public function countComments(?\DateTimeImmutable $startAt): int
    {
        $qb = $this->commentRepository->createQueryBuilder('c')->select('COUNT(c.id)');

        return $this->countWithStartAt($qb, $startAt, 'c.createdAt');
    }

    /**
     * Retourne la repartition par categorie, sinon fallback sur resourceStatus.
     * @return array{items:list<array{label:string,total:int}>,source:string}
     */
    public function getCategoryDistribution(?\DateTimeImmutable $startAt): array
    {
        $resourceMetadata = $this->entityManager->getClassMetadata(Resource::class);

        foreach (['category', 'categories'] as $association) {
            if (!$resourceMetadata->hasAssociation($association)) {
                continue;
            }

            $qb = $this->resourceRepository->createQueryBuilder('r')
                ->select('COALESCE(c.name, :uncategorized) AS label')
                ->addSelect('COUNT(DISTINCT r.id) AS total')
                ->leftJoin('r.'.$association, 'c')
                ->setParameter('uncategorized', 'Non categorisee')
                ->groupBy('label')
                ->orderBy('total', 'DESC');

            $this->applyStartAtFilter($qb, $startAt, 'r.createdAt');

            return [
                'items' => $this->normalizeDistributionRows($qb->getQuery()->getArrayResult()),
                'source' => 'category',
            ];
        }

        $qb = $this->resourceRepository->createQueryBuilder('r')
            ->select('COALESCE(r.resourceStatus, :unknown) AS label')
            ->addSelect('COUNT(r.id) AS total')
            ->setParameter('unknown', 'Inconnu')
            ->groupBy('label')
            ->orderBy('total', 'DESC');

        $this->applyStartAtFilter($qb, $startAt, 'r.createdAt');

        return [
            'items' => $this->normalizeDistributionRows($qb->getQuery()->getArrayResult()),
            'source' => 'resource_status',
        ];
    }

    /**
     * Retourne les ressources les plus engageantes (partages + commentaires).
     * @return list<array{id:int,title:string,shares:int,comments:int,engagementScore:int}>
     */
    public function getTopEngagedResources(?\DateTimeImmutable $startAt, int $limit = 5): array
    {
        $qb = $this->resourceRepository
            ->createQueryBuilder('r')
            ->select('r.id AS id')
            ->addSelect('r.title AS title')
            ->addSelect('r.sharesCount AS shares')
            ->addSelect('COUNT(c.id) AS comments')
            ->addSelect('(r.sharesCount + COUNT(c.id)) AS HIDDEN engagementSort')
            ->groupBy('r.id')
            ->addGroupBy('r.title')
            ->addGroupBy('r.sharesCount')
            ->having('engagementSort > 0')
            ->addOrderBy('engagementSort', 'DESC')
            ->addOrderBy('r.sharesCount', 'DESC')
            ->addOrderBy('comments', 'DESC')
            ->setMaxResults($limit);

        if ($startAt !== null) {
            $qb
                ->leftJoin('r.comments', 'c', 'WITH', 'c.createdAt >= :commentsStart')
                ->setParameter('commentsStart', $startAt);
        } else {
            $qb->leftJoin('r.comments', 'c');
        }

        return $this->mapTopResourceRows($qb->getQuery()->getArrayResult());
    }

    // Compte les lignes dans une fenetre [start, end] incluse.
    public function countRowsInDateWindow(
        string $table,
        string $dateColumn,
        \DateTimeImmutable $windowStart,
        \DateTimeImmutable $windowEnd
    ): int {
        $connection = $this->entityManager->getConnection();
        $bounds = $this->buildSqlDateBounds($windowStart, $windowEnd);

        $sql = sprintf(
            'SELECT COUNT(*) AS total
             FROM `%s`
             WHERE `%s` IS NOT NULL
               AND `%s` >= :startAt
               AND `%s` < :endAt',
            $table,
            $dateColumn,
            $dateColumn,
            $dateColumn
        );

        return (int) $connection->fetchOne($sql, $bounds);
    }

    // Execute un COUNT avec filtre startAt commun.
    private function countWithStartAt(QueryBuilder $qb, ?\DateTimeImmutable $startAt, string $dateExpression): int
    {
        $this->applyStartAtFilter($qb, $startAt, $dateExpression);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    // Ajoute le filtre de date de debut si fourni.
    private function applyStartAtFilter(QueryBuilder $qb, ?\DateTimeImmutable $startAt, string $dateExpression): void
    {
        if ($startAt === null) {
            return;
        }

        $qb
            ->andWhere(sprintf('%s >= :startAt', $dateExpression))
            ->setParameter('startAt', $startAt);
    }

    /**
     * @param array<int,array{label:mixed,total:mixed}> $rows
     *
     * Normalise les lignes de repartition en types stables.
     * @return list<array{label:string,total:int}>
     */
    private function normalizeDistributionRows(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            $items[] = [
                'label' => (string) ($row['label'] ?? 'Inconnu'),
                'total' => (int) ($row['total'] ?? 0),
            ];
        }

        return $items;
    }

    /**
     * @param list<array{id:mixed,title:mixed,shares:mixed,comments:mixed}> $rows
     *
     * Convertit les lignes SQL du top en structure typed pour le dashboard.
     * @return list<array{id:int,title:string,shares:int,comments:int,engagementScore:int}>
     */
    private function mapTopResourceRows(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            $shares = (int) ($row['shares'] ?? 0);
            $comments = (int) ($row['comments'] ?? 0);

            $items[] = [
                'id' => (int) ($row['id'] ?? 0),
                'title' => (string) ($row['title'] ?? 'Ressource'),
                'shares' => $shares,
                'comments' => $comments,
                'engagementScore' => ($shares * 2) + $comments,
            ];
        }

        return $items;
    }

    /**
     * Prepare les bornes SQL start/end (end exclusif).
     * @return array{startAt:string,endAt:string}
     */
    private function buildSqlDateBounds(\DateTimeImmutable $startAt, \DateTimeImmutable $endAt): array
    {
        $endExclusive = $endAt->modify('+1 day');

        return [
            'startAt' => $startAt->format('Y-m-d 00:00:00'),
            'endAt' => $endExclusive->format('Y-m-d 00:00:00'),
        ];
    }
}
