<?php

namespace App\Repository;

use App\Service\DashboardPeriodResolver;
use App\Service\DashboardStatsMath;

class DashboardStatsRepository
{
    // Orchestrateur: assemble les donnees dashboard depuis queries + services de calcul.
    public function __construct(
        private readonly DashboardStatsQueryRepository $queryRepository,
        private readonly DashboardPeriodResolver $periodResolver,
        private readonly DashboardStatsMath $math,
    ) {
    }

    // Construit toutes les stats affichees sur le dashboard admin.
    public function getStats(string $requestedPeriod = '30d'): array
    {
        // Resolve la periode demandee et ses bornes.
        $period = $this->periodResolver->resolvePeriod($requestedPeriod);
        $periodStart = $period['days'] !== null
            ? (new \DateTimeImmutable('today'))->modify(sprintf('-%d days', $period['days'] - 1))
            : null;

        $trendDays = $period['days'] ?? 30;
        $trendStart = (new \DateTimeImmutable('today'))->modify(sprintf('-%d days', $trendDays - 1));
        $trendEnd = new \DateTimeImmutable('today');
        $previousStart = $trendStart->modify(sprintf('-%d days', $trendDays));
        $previousEnd = $trendStart->modify('-1 day');

        // Charge les compteurs principaux.
        $totalResources = $this->queryRepository->countResources($periodStart);
        $activeUsers = $this->queryRepository->countActiveUsers($periodStart);
        $exploitedResources = $this->queryRepository->countExploitedResources($periodStart);
        $favoritesCount = $this->queryRepository->countFavorites($periodStart);
        $sharesCount = $this->queryRepository->countShares($periodStart);
        $commentsCount = $this->queryRepository->countComments($periodStart);

        // Calcule les ratios derivees.
        $exploitationRate = $totalResources > 0
            ? round(($exploitedResources / $totalResources) * 100, 1)
            : 0.0;

        // Recupere la distribution et le top engagement.
        $distribution = $this->queryRepository->getCategoryDistribution($periodStart);
        $distributionTotal = max(array_sum(array_column($distribution['items'], 'total')), 1);
        $topResources = $this->queryRepository->getTopEngagedResources($periodStart);

        // Mesure un insight simple sur le top.
        $highEngagementCount = count(array_filter(
            $topResources,
            static fn (array $resource): bool => $resource['engagementScore'] >= 5
        ));

        // Compare la fenetre courante vs precedente.
        $sharesGrowth = $this->math->calculateGrowth(
            $this->queryRepository->countRowsInDateWindow('share', 'created_at', $trendStart, $trendEnd),
            $this->queryRepository->countRowsInDateWindow('share', 'created_at', $previousStart, $previousEnd)
        );

        $commentsGrowth = $this->math->calculateGrowth(
            $this->queryRepository->countRowsInDateWindow('comment', 'created_at', $trendStart, $trendEnd),
            $this->queryRepository->countRowsInDateWindow('comment', 'created_at', $previousStart, $previousEnd)
        );

        // Formate la structure finale consommee par Twig.
        return [
            'totalResources' => $totalResources,
            'activeUsers' => $activeUsers,
            'exploitedResources' => $exploitedResources,
            'exploitationRate' => $exploitationRate,
            'favoritesCount' => $favoritesCount,
            'sharesCount' => $sharesCount,
            'commentsCount' => $commentsCount,
            'categoryDistribution' => $distribution['items'],
            'categoryDistributionSource' => $distribution['source'],
            'selectedPeriod' => $period['key'],
            'selectedPeriodLabel' => $period['label'],
            'periodOptions' => $this->periodResolver->getPeriodOptions(),
            'quickInsights' => [
                [
                    'label' => 'Evolution des partages',
                    'value' => sprintf('%s vs periode precedente', $this->math->formatGrowth($sharesGrowth)),
                    'tone' => $this->math->resolveTone($sharesGrowth),
                ],
                [
                    'label' => 'Ressources a fort engagement',
                    'value' => sprintf('%d ressource(s) avec score >= 5', $highEngagementCount),
                    'tone' => $highEngagementCount > 0 ? 'positive' : 'neutral',
                ],
                [
                    'label' => 'Evolution des commentaires',
                    'value' => sprintf('%s vs periode precedente', $this->math->formatGrowth($commentsGrowth)),
                    'tone' => $this->math->resolveTone($commentsGrowth),
                ],
            ],
            'distributionTotal' => $distributionTotal,
            'topResources' => $topResources,
        ];
    }
}
