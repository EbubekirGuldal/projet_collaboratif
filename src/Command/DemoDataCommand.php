<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\RelationKind;
use App\Entity\Resource;
use App\Entity\RessourceType;
use App\Entity\Share;
use App\Entity\User;
use App\Entity\UserResourceState;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:demo-data',
    description: 'Seed or cleanup demo data for manual testing.',
)]
class DemoDataCommand extends Command
{
    private const MARKER = '[DEMO]';
    private const USER_EMAIL_PATTERN = 'demo.%@example.com';
    private const STATES_DATE_BOUNDARY = '2098-01-01 00:00:00';

    /**
     * @var list<string>
     */
    private array $availableTables = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'cleanup',
            null,
            InputOption::VALUE_NONE,
            'Delete existing demo data instead of creating it.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cleanupOnly = (bool) $input->getOption('cleanup');
        $this->availableTables = array_map(
            static fn (string $table): string => strtolower($table),
            $this->connection->createSchemaManager()->listTableNames()
        );

        $this->connection->beginTransaction();

        try {
            $this->cleanupData();
            $this->entityManager->clear();

            if (!$cleanupOnly) {
                $this->seedData();
                $this->entityManager->flush();
            }

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();

            throw $exception;
        }

        if ($cleanupOnly) {
            $io->success('Les donnees de demo ont ete supprimees.');

            return Command::SUCCESS;
        }

        $io->success('Les donnees de demo ont ete creees.');
        $io->section('Comptes de connexion');
        $io->table(
            ['Role', 'Email', 'Mot de passe'],
            [
                ['Admin', 'demo.admin@example.com', 'demo1234'],
                ['Utilisateur verifie', 'demo.sarah@example.com', 'demo1234'],
                ['Utilisateur non verifie', 'demo.leo@example.com', 'demo1234'],
            ]
        );
        $io->writeln('Commande de nettoyage: php bin/console app:demo-data --cleanup');

        return Command::SUCCESS;
    }

    private function cleanupData(): void
    {
        if ($this->hasTable('user_favorites')) {
            $this->connection->executeStatement(
                'DELETE FROM user_favorites
                 WHERE user_id IN (SELECT id FROM `user` WHERE email LIKE :emailPattern)
                    OR resource_id IN (SELECT id FROM resource WHERE title LIKE :resourceMarker)',
                [
                    'emailPattern' => self::USER_EMAIL_PATTERN,
                    'resourceMarker' => self::MARKER.'%',
                ]
            );
        }

        if ($this->hasTable('comment')) {
            $this->connection->executeStatement(
                'DELETE FROM comment
                 WHERE content LIKE :commentMarker
                    OR user_id IN (SELECT id FROM `user` WHERE email LIKE :emailPattern)
                    OR resource_id IN (SELECT id FROM resource WHERE title LIKE :resourceMarker)',
                [
                    'commentMarker' => self::MARKER.'%',
                    'emailPattern' => self::USER_EMAIL_PATTERN,
                    'resourceMarker' => self::MARKER.'%',
                ]
            );
        }

        if ($this->hasTable('resource')) {
            $this->connection->executeStatement(
                'DELETE FROM resource WHERE title LIKE :resourceMarker',
                ['resourceMarker' => self::MARKER.'%']
            );
        }

        if ($this->hasTable('share')) {
            $this->connection->executeStatement(
                'DELETE FROM share WHERE channel LIKE :shareMarker',
                ['shareMarker' => self::MARKER.'%']
            );
        }

        if ($this->hasTable('user_resource_state')) {
            $this->connection->executeStatement(
                'DELETE FROM user_resource_state WHERE started_at >= :boundary',
                ['boundary' => self::STATES_DATE_BOUNDARY]
            );
        }

        if ($this->hasTable('category')) {
            $this->connection->executeStatement(
                'DELETE FROM category WHERE name LIKE :categoryMarker',
                ['categoryMarker' => self::MARKER.'%']
            );
        }

        if ($this->hasTable('ressource_type')) {
            $this->connection->executeStatement(
                'DELETE FROM ressource_type WHERE label LIKE :typeMarker',
                ['typeMarker' => self::MARKER.'%']
            );
        }

        if ($this->hasTable('relation_kind')) {
            $this->connection->executeStatement(
                'DELETE FROM relation_kind WHERE name LIKE :relationMarker',
                ['relationMarker' => self::MARKER.'%']
            );
        }

        if ($this->hasTable('user')) {
            $this->connection->executeStatement(
                'DELETE FROM `user` WHERE email LIKE :emailPattern',
                ['emailPattern' => self::USER_EMAIL_PATTERN]
            );
        }
    }

    /**
     * @return array<string, User>
     */
    private function seedData(): array
    {
        $users = $this->createUsers();
        $resources = $this->createResources($users);
        $this->createComments($users, $resources);
        $this->createFavorites($users, $resources);
        $this->createShares();
        $this->createUserResourceStates();
        $this->createAuxiliaryData();

        return $users;
    }

    /**
     * @return array<string, User>
     */
    private function createUsers(): array
    {
        $users = [];

        $definitions = [
            'admin' => [
                'email' => 'demo.admin@example.com',
                'username' => 'demo_admin',
                'firstName' => 'Alice',
                'lastName' => 'Martin',
                'roles' => ['ROLE_ADMIN'],
                'verified' => true,
                'active' => true,
                'createdAt' => new \DateTimeImmutable('-40 days'),
                'lastConnexion' => new \DateTimeImmutable('-1 hour'),
            ],
            'sarah' => [
                'email' => 'demo.sarah@example.com',
                'username' => 'sarah_demo',
                'firstName' => 'Sarah',
                'lastName' => 'Bernard',
                'roles' => ['ROLE_USER'],
                'verified' => true,
                'active' => true,
                'createdAt' => new \DateTimeImmutable('-15 days'),
                'lastConnexion' => new \DateTimeImmutable('-5 hours'),
            ],
            'leo' => [
                'email' => 'demo.leo@example.com',
                'username' => 'leo_demo',
                'firstName' => 'Leo',
                'lastName' => 'Roux',
                'roles' => ['ROLE_USER'],
                'verified' => false,
                'active' => true,
                'createdAt' => new \DateTimeImmutable('-6 days'),
                'lastConnexion' => new \DateTimeImmutable('-2 days'),
            ],
        ];

        foreach ($definitions as $key => $definition) {
            $user = new User();
            $user->setEmail($definition['email']);
            $user->setUsername($definition['username']);
            $user->setFirstName($definition['firstName']);
            $user->setLastName($definition['lastName']);
            $user->setRoles($definition['roles']);
            $user->setIsVerified($definition['verified']);
            $user->setIsActive($definition['active']);
            $user->setCreatedAt($definition['createdAt']);
            $user->setLastConnexion($definition['lastConnexion']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'demo1234'));

            $this->entityManager->persist($user);
            $users[$key] = $user;
        }

        return $users;
    }

    /**
     * @param array<string, User> $users
     *
     * @return array<string, Resource>
     */
    private function createResources(array $users): array
    {
        $resources = [];

        $definitions = [
            'guide-entraide' => [
                'title' => self::MARKER.' Guide des lieux d entraide a Paris',
                'content' => 'Annuaire pratique des accueils de jour, permanences sociales et associations a contacter rapidement.',
                'url' => 'https://www.paris.fr/',
                'publishedAt' => new \DateTimeImmutable('-8 days'),
                'createdAt' => new \DateTimeImmutable('-9 days'),
                'status' => 'Publiee',
                'visibility' => 'Public',
                'likes' => 8,
                'shares' => 3,
                'user' => $users['admin'],
            ],
            'soutien-parents' => [
                'title' => self::MARKER.' Atelier de soutien pour les parents isoles',
                'content' => 'Ressource locale avec horaires, contact et conseils pour rejoindre un groupe de parole parental.',
                'url' => 'https://www.caf.fr/',
                'publishedAt' => new \DateTimeImmutable('-5 days'),
                'createdAt' => new \DateTimeImmutable('-5 days'),
                'status' => 'Publiee',
                'visibility' => 'Public',
                'likes' => 5,
                'shares' => 2,
                'user' => $users['sarah'],
            ],
            'benevolat' => [
                'title' => self::MARKER.' Checklist pour lancer une action benevole',
                'content' => 'Etapes concretes pour recruter des volontaires, definir un cadre et suivre les besoins du terrain.',
                'url' => 'https://www.service-public.fr/',
                'publishedAt' => new \DateTimeImmutable('-3 days'),
                'createdAt' => new \DateTimeImmutable('-3 days'),
                'status' => 'Publiee',
                'visibility' => 'Public',
                'likes' => 11,
                'shares' => 6,
                'user' => $users['admin'],
            ],
            'brochure-sante' => [
                'title' => self::MARKER.' Brochure sante mentale pour les aidants',
                'content' => 'Resume clair des signaux d epuisement, contacts utiles et solutions de relais pour les proches aidants.',
                'url' => 'https://www.ameli.fr/',
                'publishedAt' => new \DateTimeImmutable('-2 days'),
                'createdAt' => new \DateTimeImmutable('-2 days'),
                'status' => 'Publiee',
                'visibility' => 'Public',
                'likes' => 2,
                'shares' => 1,
                'user' => $users['leo'],
            ],
            'brouillon' => [
                'title' => self::MARKER.' Ressource en cours pour test moderation',
                'content' => 'Brouillon interne pour verifier les differents statuts de ressources dans le back-office.',
                'url' => null,
                'publishedAt' => null,
                'createdAt' => new \DateTimeImmutable('-1 day'),
                'status' => 'Brouillon',
                'visibility' => 'Prive',
                'likes' => 0,
                'shares' => 0,
                'user' => $users['sarah'],
            ],
        ];

        foreach ($definitions as $key => $definition) {
            $resource = new Resource();
            $resource->setTitle($definition['title']);
            $resource->setContent($definition['content']);
            $resource->setExternalUrl($definition['url']);
            $resource->setPublishedAt($definition['publishedAt']);
            $resource->setCreatedAt($definition['createdAt']);
            $resource->setResourceStatus($definition['status']);
            $resource->setVisibilityStatus($definition['visibility']);
            $resource->setLikesCount($definition['likes']);
            $resource->setSharesCount($definition['shares']);
            $resource->setUser($definition['user']);

            $this->entityManager->persist($resource);
            $resources[$key] = $resource;
        }

        return $resources;
    }

    /**
     * @param array<string, User> $users
     * @param array<string, Resource> $resources
     */
    private function createComments(array $users, array $resources): void
    {
        $definitions = [
            [
                'content' => self::MARKER.' Cette liste ma aide a orienter une personne des le premier rendez-vous.',
                'resource' => $resources['guide-entraide'],
                'user' => $users['sarah'],
                'createdAt' => new \DateTimeImmutable('-7 days'),
            ],
            [
                'content' => self::MARKER.' Le format est clair, il manque juste une version imprimable.',
                'resource' => $resources['guide-entraide'],
                'user' => $users['leo'],
                'createdAt' => new \DateTimeImmutable('-6 days'),
            ],
            [
                'content' => self::MARKER.' Teste sur un atelier local, les contacts sont a jour.',
                'resource' => $resources['soutien-parents'],
                'user' => $users['admin'],
                'createdAt' => new \DateTimeImmutable('-4 days'),
            ],
            [
                'content' => self::MARKER.' Bonne base pour construire une page de conseils plus detaillee.',
                'resource' => $resources['benevolat'],
                'user' => $users['sarah'],
                'createdAt' => new \DateTimeImmutable('-2 days'),
            ],
            [
                'content' => self::MARKER.' Je garde cette ressource de cote pour la partager a mon association.',
                'resource' => $resources['brochure-sante'],
                'user' => $users['leo'],
                'createdAt' => new \DateTimeImmutable('-1 day'),
            ],
        ];

        foreach ($definitions as $definition) {
            $comment = new Comment();
            $comment->setContent($definition['content']);
            $comment->setResource($definition['resource']);
            $comment->setUser($definition['user']);
            $comment->setCreatedAt($definition['createdAt']);

            $this->entityManager->persist($comment);
        }
    }

    /**
     * @param array<string, User> $users
     * @param array<string, Resource> $resources
     */
    private function createFavorites(array $users, array $resources): void
    {
        $users['admin']->addFavorite($resources['guide-entraide']);
        $users['admin']->addFavorite($resources['benevolat']);
        $users['sarah']->addFavorite($resources['benevolat']);
        $users['sarah']->addFavorite($resources['brochure-sante']);
        $users['leo']->addFavorite($resources['soutien-parents']);
    }

    private function createShares(): void
    {
        foreach (['email', 'whatsapp', 'linkedin', 'teams', 'copy_link'] as $channel) {
            $share = new Share();
            $share->setChannel(self::MARKER.' '.$channel);
            $share->setCreatedAt(new \DateTimeImmutable('-1 day'));

            $this->entityManager->persist($share);
        }
    }

    private function createUserResourceStates(): void
    {
        if (!$this->hasTable('user_resource_state')) {
            return;
        }

        $definitions = [
            [true, true, false, '2098-01-03 09:00:00', '2098-01-04 10:00:00', '2098-01-04 10:00:00'],
            [true, false, true, '2098-01-05 14:00:00', null, '2098-01-06 08:30:00'],
            [false, true, false, '2098-01-06 11:00:00', '2098-01-07 12:00:00', '2098-01-07 12:00:00'],
        ];

        foreach ($definitions as [$favorite, $exploited, $savedForLater, $startedAt, $completedAt, $lastInteractionAt]) {
            $state = new UserResourceState();
            $state->setIsFavorite($favorite);
            $state->setIsExploited($exploited);
            $state->setIsSavedForLater($savedForLater);
            $state->setStartedAt(new \DateTimeImmutable($startedAt));
            $state->setCompletedAt($completedAt ? new \DateTimeImmutable($completedAt) : null);
            $state->setLastInteractionAt(new \DateTimeImmutable($lastInteractionAt));

            $this->entityManager->persist($state);
        }
    }

    private function createAuxiliaryData(): void
    {
        if ($this->hasTable('category')) {
            foreach (['Entraide locale', 'Famille', 'Sante', 'Insertion'] as $name) {
                $category = new Category();
                $category->setName(self::MARKER.' '.$name);

                $this->entityManager->persist($category);
            }
        }

        if ($this->hasTable('ressource_type')) {
            foreach (['Guide', 'Atelier', 'Fiche pratique'] as $label) {
                $type = new RessourceType();
                $type->setLabel(self::MARKER.' '.$label);

                $this->entityManager->persist($type);
            }
        }

        if ($this->hasTable('relation_kind')) {
            foreach (['Accompagnement', 'Orientation', 'Pair-aidance'] as $name) {
                $relationKind = new RelationKind();
                $relationKind->setName(self::MARKER.' '.$name);

                $this->entityManager->persist($relationKind);
            }
        }
    }

    private function hasTable(string $table): bool
    {
        return in_array(strtolower($table), $this->availableTables, true);
    }
}
