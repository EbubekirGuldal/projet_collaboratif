<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:admin-stats:test-data',
    description: 'Seed or cleanup test data used by the admin statistics dashboard.',
)]
class AdminStatsTestDataCommand extends Command
{
    private const MARKER = '[[STATS_TEST]]';
    private const STATES_DATE_BOUNDARY = '2099-01-01 00:00:00';

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'cleanup',
            null,
            InputOption::VALUE_NONE,
            'Delete test data instead of seeding it.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cleanupOnly = (bool) $input->getOption('cleanup');

        $this->connection->beginTransaction();
        try {
            $this->cleanupData();

            if (!$cleanupOnly) {
                $this->seedData();
            }

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }

        $counts = $this->fetchMarkerCounts();

        $io->table(
            ['marker_resources', 'marker_comments', 'marker_shares', 'marker_users', 'marker_states'],
            [[
                (string) $counts['marker_resources'],
                (string) $counts['marker_comments'],
                (string) $counts['marker_shares'],
                (string) $counts['marker_users'],
                (string) $counts['marker_states'],
            ]]
        );

        if ($cleanupOnly) {
            $io->success('Test data removed.');

            return Command::SUCCESS;
        }

        $io->success('Test data seeded.');
        $io->writeln('Expected deltas in dashboard:');
        $io->writeln('- Resources: +4');
        $io->writeln('- Active users: +2');
        $io->writeln('- Exploited resources (current logic): +3');
        $io->writeln('- Exploitation rate impact with 4 resources: 75.0%');
        $io->writeln('- Favorites: +2');
        $io->writeln('- Shares: +5');
        $io->writeln('- Comments: +4');

        return Command::SUCCESS;
    }

    private function cleanupData(): void
    {
        $this->connection->executeStatement(
            'DELETE FROM comment WHERE content LIKE :marker',
            ['marker' => self::MARKER.'%']
        );

        $this->connection->executeStatement(
            'DELETE FROM resource WHERE title LIKE :marker',
            ['marker' => self::MARKER.'%']
        );

        $this->connection->executeStatement(
            'DELETE FROM `user` WHERE email LIKE :emailPattern',
            ['emailPattern' => 'stats.test.%@example.com']
        );

        $this->connection->executeStatement(
            'DELETE FROM share WHERE channel LIKE :marker',
            ['marker' => self::MARKER.'%']
        );

        $this->connection->executeStatement(
            'DELETE FROM user_resource_state WHERE started_at >= :boundary',
            ['boundary' => self::STATES_DATE_BOUNDARY]
        );
    }

    private function seedData(): void
    {
        $resources = [
            ['Ressource A', 'Contenu test A', 'https://example.com/a', 'Publiee', 'Public'],
            ['Ressource B', 'Contenu test B', 'https://example.com/b', 'Publiee', 'Public'],
            ['Ressource C', 'Contenu test C', 'https://example.com/c', 'Brouillon', 'Prive'],
            ['Ressource D', 'Contenu test D', 'https://example.com/d', 'Archivee', 'Prive'],
        ];

        foreach ($resources as [$name, $content, $url, $resourceStatus, $visibilityStatus]) {
            $this->connection->executeStatement(
                'INSERT INTO resource (title, content, external_url, published_at, created_at, updated_at, image, video, resource_status, visibility_status, shares_count)
                 VALUES (:title, :content, :externalUrl, NOW(), NOW(), NOW(), NULL, NULL, :resourceStatus, :visibilityStatus, 0)',
                [
                    'title' => self::MARKER.' '.$name,
                    'content' => $content,
                    'externalUrl' => $url,
                    'resourceStatus' => $resourceStatus,
                    'visibilityStatus' => $visibilityStatus,
                ]
            );
        }

        $resourceAId = $this->fetchResourceId(self::MARKER.' Ressource A');
        $resourceBId = $this->fetchResourceId(self::MARKER.' Ressource B');
        $resourceCId = $this->fetchResourceId(self::MARKER.' Ressource C');

        $comments = [
            ['Comment 1', 'Publie', $resourceAId],
            ['Comment 2', 'Publie', $resourceAId],
            ['Comment 3', 'Publie', $resourceBId],
            ['Comment 4', 'En attente', $resourceCId],
        ];

        foreach ($comments as [$commentName, $status, $resourceId]) {
            $this->connection->executeStatement(
                'INSERT INTO comment (content, created_at, status, updated_at, resource_id)
                 VALUES (:content, NOW(), :status, NULL, :resourceId)',
                [
                    'content' => self::MARKER.' '.$commentName,
                    'status' => $status,
                    'resourceId' => $resourceId,
                ]
            );
        }

        foreach (['whatsapp', 'email', 'slack', 'teams', 'linkedin'] as $channel) {
            $this->connection->executeStatement(
                'INSERT INTO share (channel, created_at) VALUES (:channel, NOW())',
                ['channel' => self::MARKER.' '.$channel]
            );
        }

        $users = [
            ['stats.test.active1@example.com', 'stats_active_1', 'Active1', 1],
            ['stats.test.active2@example.com', 'stats_active_2', 'Active2', 1],
            ['stats.test.inactive1@example.com', 'stats_inactive_1', 'Inactive1', 0],
        ];

        foreach ($users as [$email, $username, $firstName, $isActive]) {
            $this->connection->executeStatement(
                'INSERT INTO `user` (email, roles, password, username, last_name, first_name, is_verified, is_active, created_at, updated_at)
                 VALUES (:email, :roles, :password, :username, :lastName, :firstName, 1, :isActive, NOW(), NOW())',
                [
                    'email' => $email,
                    'roles' => '["ROLE_USER"]',
                    'password' => 'test-password-hash',
                    'username' => $username,
                    'lastName' => 'Test',
                    'firstName' => $firstName,
                    'isActive' => $isActive,
                ]
            );
        }

        $states = [
            [1, 1, 0, '2099-01-01 10:00:00', '2099-01-02 10:00:00', '2099-01-02 10:00:00'],
            [1, 1, 0, '2099-01-01 11:00:00', '2099-01-03 10:00:00', '2099-01-03 10:00:00'],
            [0, 1, 0, '2099-01-01 12:00:00', null, '2099-01-04 10:00:00'],
            [0, 0, 1, '2099-01-01 13:00:00', null, '2099-01-05 10:00:00'],
        ];

        foreach ($states as [$favorite, $exploited, $savedForLater, $startedAt, $completedAt, $lastInteractionAt]) {
            $this->connection->executeStatement(
                'INSERT INTO user_resource_state (is_favorite, is_exploited, is_saved_for_later, started_at, completed_at, last_interaction_at)
                 VALUES (:favorite, :exploited, :savedForLater, :startedAt, :completedAt, :lastInteractionAt)',
                [
                    'favorite' => $favorite,
                    'exploited' => $exploited,
                    'savedForLater' => $savedForLater,
                    'startedAt' => $startedAt,
                    'completedAt' => $completedAt,
                    'lastInteractionAt' => $lastInteractionAt,
                ]
            );
        }
    }

    private function fetchResourceId(string $title): int
    {
        $id = $this->connection->fetchOne(
            'SELECT id FROM resource WHERE title = :title LIMIT 1',
            ['title' => $title]
        );

        if ($id === false) {
            throw new \RuntimeException(sprintf('Resource not found for title "%s".', $title));
        }

        return (int) $id;
    }

    /**
     * @return array{
     *     marker_resources:int|string,
     *     marker_comments:int|string,
     *     marker_shares:int|string,
     *     marker_users:int|string,
     *     marker_states:int|string
     * }
     */
    private function fetchMarkerCounts(): array
    {
        $counts = $this->connection->fetchAssociative(
            'SELECT
                (SELECT COUNT(*) FROM resource WHERE title LIKE :marker) AS marker_resources,
                (SELECT COUNT(*) FROM comment WHERE content LIKE :marker) AS marker_comments,
                (SELECT COUNT(*) FROM share WHERE channel LIKE :marker) AS marker_shares,
                (SELECT COUNT(*) FROM `user` WHERE email LIKE :emailPattern) AS marker_users,
                (SELECT COUNT(*) FROM user_resource_state WHERE started_at >= :boundary) AS marker_states',
            [
                'marker' => self::MARKER.'%',
                'emailPattern' => 'stats.test.%@example.com',
                'boundary' => self::STATES_DATE_BOUNDARY,
            ]
        );

        if ($counts === false) {
            throw new \RuntimeException('Unable to fetch marker counts.');
        }

        return $counts;
    }
}
