<?php

namespace App\Service;

class DashboardPeriodResolver
{
    /**
     * @var array<string,int|null>
     */
    private const PERIODS = [
        '7d' => 7,
        '30d' => 30,
        '90d' => 90,
        'all' => null,
    ];

    /**
     * @var array<string,string>
     */
    private const PERIOD_LABELS = [
        '7d' => '7 jours',
        '30d' => '30 jours',
        '90d' => '90 jours',
        'all' => 'Tout',
    ];

    /**
     * @return array{key:string,label:string,days:int|null}
     */
    // Valide la période demandée et retourne sa configuration canonique.
    public function resolvePeriod(string $requestedPeriod): array
    {
        $key = array_key_exists($requestedPeriod, self::PERIODS) ? $requestedPeriod : '30d';

        return [
            'key' => $key,
            'label' => self::PERIOD_LABELS[$key],
            'days' => self::PERIODS[$key],
        ];
    }

    /**
     * @return list<array{key:string,label:string}>
     */
    // Retourne les options à afficher dans le filtre de période.
    public function getPeriodOptions(): array
    {
        return [
            ['key' => '7d', 'label' => self::PERIOD_LABELS['7d']],
            ['key' => '30d', 'label' => self::PERIOD_LABELS['30d']],
            ['key' => '90d', 'label' => self::PERIOD_LABELS['90d']],
            ['key' => 'all', 'label' => self::PERIOD_LABELS['all']],
        ];
    }
}
