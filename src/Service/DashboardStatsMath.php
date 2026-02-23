<?php

namespace App\Service;

class DashboardStatsMath
{
    // Calcule l'evolution (%) entre une période courante et précédente.
    public function calculateGrowth(int $currentCount, int $previousCount): float
    {
        if ($previousCount === 0) {
            return $currentCount > 0 ? 100.0 : 0.0;
        }

        return round((($currentCount - $previousCount) / $previousCount) * 100, 1);
    }

    // Formate un pourcentage d'evolution avec signe.
    public function formatGrowth(float $growth): string
    {
        $sign = $growth > 0 ? '+' : '';

        return sprintf('%s%.1f%%', $sign, $growth);
    }

    // Donne une tonalité UI en fonction de la croissance.
    public function resolveTone(float $growth): string
    {
        if ($growth > 0) {
            return 'positive';
        }

        if ($growth < 0) {
            return 'negative';
        }

        return 'neutral';
    }
}
