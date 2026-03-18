<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Resource;
use App\Entity\Share;
use App\Entity\User;
use App\Entity\UserResourceState;
use App\Enum\ResourceStatus;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:presentation-data:reset',
    description: 'Replace old resources with a clean presentation dataset.',
)]
class PresentationDataResetCommand extends Command
{
    /**
     * @var array<string, string>
     */
    private const CATEGORY_DEFINITIONS = [
        'sante-mentale' => 'Sante mentale',
        'vie-etudiante' => 'Vie etudiante',
        'parentalite' => 'Parentalite',
        'demarches' => 'Demarches',
        'engagement' => 'Engagement local',
    ];

    /**
     * @var array<string, array{
     *     email:string,
     *     username:string,
     *     firstName:string,
     *     lastName:string,
     *     roles:list<string>,
     *     createdAt:string,
     *     lastConnexion:string
     * }>
     */
    private const USER_DEFINITIONS = [
        'admin' => [
            'email' => 'presentation.admin@example.com',
            'username' => 'admin_presentation',
            'firstName' => 'Claire',
            'lastName' => 'Dupont',
            'roles' => ['ROLE_ADMIN'],
            'createdAt' => '-45 days',
            'lastConnexion' => '-20 minutes',
        ],
        'camille' => [
            'email' => 'presentation.camille@example.com',
            'username' => 'camille_relations',
            'firstName' => 'Camille',
            'lastName' => 'Moreau',
            'roles' => ['ROLE_USER'],
            'createdAt' => '-25 days',
            'lastConnexion' => '-3 hours',
        ],
        'nora' => [
            'email' => 'presentation.nora@example.com',
            'username' => 'nora_solidaire',
            'firstName' => 'Nora',
            'lastName' => 'Petit',
            'roles' => ['ROLE_USER'],
            'createdAt' => '-18 days',
            'lastConnexion' => '-7 hours',
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Connection $connection,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->connection->beginTransaction();

        try {
            $this->cleanupExistingData();
            $this->entityManager->clear();

            $users = $this->createPresentationUsers();
            $categories = $this->createCategories();
            $resources = $this->createResources($users, $categories);
            $this->createComments($users, $resources);
            $this->createFavorites($users, $resources);
            $this->createShares();
            $this->createUserResourceStates();

            $this->entityManager->flush();
            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }

        $io->success('Les donnees de presentation ont ete reinitialisees.');
        $io->table(
            ['Type', 'Valeur'],
            [
                ['Categories', (string) count(self::CATEGORY_DEFINITIONS)],
                ['Ressources', '6'],
                ['Utilisateurs presentation', (string) count(self::USER_DEFINITIONS)],
                ['Mot de passe comptes presentation', 'presentation123'],
            ]
        );

        return Command::SUCCESS;
    }

    private function cleanupExistingData(): void
    {
        $this->connection->executeStatement('DELETE FROM moderation_log');
        $this->connection->executeStatement('DELETE FROM comment');
        $this->connection->executeStatement('DELETE FROM user_favorites');
        $this->connection->executeStatement('DELETE FROM resource');
        $this->connection->executeStatement('DELETE FROM share');
        $this->connection->executeStatement('DELETE FROM user_resource_state');
        $this->connection->executeStatement('DELETE FROM category');
        $this->connection->executeStatement(
            'DELETE FROM `user` WHERE email LIKE :emailPattern',
            ['emailPattern' => 'presentation.%@example.com']
        );
    }

    /**
     * @return array<string, User>
     */
    private function createPresentationUsers(): array
    {
        $users = [];

        foreach (self::USER_DEFINITIONS as $key => $definition) {
            $user = new User();
            $user->setEmail($definition['email']);
            $user->setUsername($definition['username']);
            $user->setFirstName($definition['firstName']);
            $user->setLastName($definition['lastName']);
            $user->setRoles($definition['roles']);
            $user->setIsVerified(true);
            $user->setIsActive(true);
            $user->setCreatedAt(new \DateTimeImmutable($definition['createdAt']));
            $user->setLastConnexion(new \DateTimeImmutable($definition['lastConnexion']));
            $user->setPassword($this->passwordHasher->hashPassword($user, 'presentation123'));

            $this->entityManager->persist($user);
            $users[$key] = $user;
        }

        return $users;
    }

    /**
     * @return array<string, Category>
     */
    private function createCategories(): array
    {
        $categories = [];

        foreach (self::CATEGORY_DEFINITIONS as $key => $name) {
            $category = new Category();
            $category->setName($name);

            $this->entityManager->persist($category);
            $categories[$key] = $category;
        }

        return $categories;
    }

    /**
     * @param array<string, User> $users
     * @param array<string, Category> $categories
     *
     * @return array<string, Resource>
     */
    private function createResources(array $users, array $categories): array
    {
        $resources = [];

        $definitions = [
            'sante-psy-etudiant' => [
                'title' => 'Sante Psy Etudiant : consulter un psychologue gratuitement',
                'content' => 'Un repere utile pour orienter rapidement un etudiant vers un accompagnement psychologique. Cette ressource explique le dispositif, a qui il s adresse et comment enclencher une prise de rendez-vous.',
                'url' => 'https://www.etudiant.gouv.fr/',
                'category' => $categories['vie-etudiante'],
                'user' => $users['admin'],
                'createdAt' => new \DateTimeImmutable('-9 days'),
                'publishedAt' => new \DateTimeImmutable('-9 days'),
                'likes' => 14,
                'shares' => 7,
            ],
            '3114' => [
                'title' => '3114 : ligne nationale de prevention du suicide',
                'content' => 'Une ressource de reference pour afficher un contact d urgence clair, disponible a toute heure, avec un libelle rassurant et facilement partageable.',
                'url' => 'https://3114.fr/',
                'category' => $categories['sante-mentale'],
                'user' => $users['camille'],
                'createdAt' => new \DateTimeImmutable('-7 days'),
                'publishedAt' => new \DateTimeImmutable('-7 days'),
                'likes' => 11,
                'shares' => 6,
            ],
            'france-services' => [
                'title' => 'France Services : se faire accompagner pour ses demarches',
                'content' => 'Ideal pour aider une personne a retrouver un point d accueil proche de chez elle et avancer sur des demarches comme la CAF, la CPAM, les impots ou France Travail.',
                'url' => 'https://www.france-services.gouv.fr/',
                'category' => $categories['demarches'],
                'user' => $users['admin'],
                'createdAt' => new \DateTimeImmutable('-6 days'),
                'publishedAt' => new \DateTimeImmutable('-6 days'),
                'likes' => 8,
                'shares' => 5,
            ],
            'caf-parentalite' => [
                'title' => 'CAF : ressources utiles pour la parentalite',
                'content' => 'Une porte d entree claire pour les parents qui cherchent des informations sur l accompagnement familial, le soutien a la parentalite et les interlocuteurs de proximite.',
                'url' => 'https://www.caf.fr/',
                'category' => $categories['parentalite'],
                'user' => $users['nora'],
                'createdAt' => new \DateTimeImmutable('-5 days'),
                'publishedAt' => new \DateTimeImmutable('-5 days'),
                'likes' => 6,
                'shares' => 3,
            ],
            'fil-sante-jeunes' => [
                'title' => 'Fil Sante Jeunes : ecoute anonyme pour les 12-25 ans',
                'content' => 'Cette fiche permet de proposer un point de contact simple a des jeunes qui ont besoin de parler, de poser une question ou d etre orientes sans jugement.',
                'url' => 'https://www.filsantejeunes.com/',
                'category' => $categories['sante-mentale'],
                'user' => $users['camille'],
                'createdAt' => new \DateTimeImmutable('-3 days'),
                'publishedAt' => new \DateTimeImmutable('-3 days'),
                'likes' => 10,
                'shares' => 4,
            ],
            'jeveuxaider' => [
                'title' => 'JeVeuxAider.gouv.fr : trouver une mission de benevolat locale',
                'content' => 'Une ressource actionnable pour transformer l envie d aider en mission concrete. Elle est utile pour presenter une piste d engagement simple et credible.',
                'url' => 'https://www.jeveuxaider.gouv.fr/',
                'category' => $categories['engagement'],
                'user' => $users['nora'],
                'createdAt' => new \DateTimeImmutable('-2 days'),
                'publishedAt' => new \DateTimeImmutable('-2 days'),
                'likes' => 9,
                'shares' => 5,
            ],
        ];

        foreach ($definitions as $key => $definition) {
            $resource = new Resource();
            $resource->setTitle($definition['title']);
            $resource->setContent($definition['content']);
            $resource->setExternalUrl($definition['url']);
            $resource->setCategory($definition['category']);
            $resource->setUser($definition['user']);
            $resource->setCreatedAt($definition['createdAt']);
            $resource->setPublishedAt($definition['publishedAt']);
            $resource->setLikesCount($definition['likes']);
            $resource->setSharesCount($definition['shares']);
            $resource->setResourceStatus(ResourceStatus::PUBLIC);

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
            [$resources['sante-psy-etudiant'], $users['camille'], 'Tres utile pour une presentation orientee vie etudiante, le message est clair et rassurant.', '-8 days'],
            [$resources['sante-psy-etudiant'], $users['nora'], 'Je la garde car elle donne une piste concrete sans noyer la personne dans trop d informations.', '-7 days'],
            [$resources['3114'], $users['admin'], 'Cette ressource fait partie des reperes essentiels a mettre en avant sur la plateforme.', '-6 days'],
            [$resources['france-services'], $users['camille'], 'Bonne fiche pour montrer la dimension pratique du site et l acces aux droits.', '-5 days'],
            [$resources['caf-parentalite'], $users['admin'], 'Le contenu parle a un public familial et complete bien les ressources d entraide.', '-4 days'],
            [$resources['fil-sante-jeunes'], $users['nora'], 'Le ton est adapte et le lien est facile a partager rapidement.', '-2 days'],
            [$resources['jeveuxaider'], $users['camille'], 'Parfait pour illustrer l engagement local et les ressources de mobilisation citoyenne.', '-1 day'],
        ];

        foreach ($definitions as [$resource, $user, $content, $createdAt]) {
            $comment = new Comment();
            $comment->setResource($resource);
            $comment->setUser($user);
            $comment->setContent($content);
            $comment->setCreatedAt(new \DateTimeImmutable($createdAt));

            $this->entityManager->persist($comment);
        }
    }

    /**
     * @param array<string, User> $users
     * @param array<string, Resource> $resources
     */
    private function createFavorites(array $users, array $resources): void
    {
        $users['admin']->addFavorite($resources['sante-psy-etudiant']);
        $users['admin']->addFavorite($resources['france-services']);
        $users['camille']->addFavorite($resources['3114']);
        $users['camille']->addFavorite($resources['fil-sante-jeunes']);
        $users['nora']->addFavorite($resources['caf-parentalite']);
        $users['nora']->addFavorite($resources['jeveuxaider']);
    }

    private function createShares(): void
    {
        $definitions = [
            ['email', '-8 days'],
            ['linkedin', '-7 days'],
            ['whatsapp', '-7 days'],
            ['copy_link', '-6 days'],
            ['email', '-5 days'],
            ['teams', '-4 days'],
            ['linkedin', '-3 days'],
            ['whatsapp', '-2 days'],
            ['copy_link', '-1 day'],
        ];

        foreach ($definitions as [$channel, $createdAt]) {
            $share = new Share();
            $share->setChannel($channel);
            $share->setCreatedAt(new \DateTimeImmutable($createdAt));

            $this->entityManager->persist($share);
        }
    }

    private function createUserResourceStates(): void
    {
        $definitions = [
            [true, true, false, '-8 days', '-7 days', '-7 days'],
            [true, false, true, '-7 days', null, '-6 days'],
            [false, true, false, '-6 days', '-5 days', '-5 days'],
            [true, true, false, '-5 days', '-4 days', '-4 days'],
            [false, false, true, '-4 days', null, '-3 days'],
            [true, false, false, '-3 days', null, '-2 days'],
            [false, true, false, '-2 days', '-1 day', '-1 day'],
        ];

        foreach ($definitions as [$favorite, $exploited, $savedForLater, $startedAt, $completedAt, $lastInteractionAt]) {
            $state = new UserResourceState();
            $state->setIsFavorite($favorite);
            $state->setIsExploited($exploited);
            $state->setIsSavedForLater($savedForLater);
            $state->setStartedAt(new \DateTimeImmutable($startedAt));
            $state->setCompletedAt($completedAt !== null ? new \DateTimeImmutable($completedAt) : null);
            $state->setLastInteractionAt(new \DateTimeImmutable($lastInteractionAt));

            $this->entityManager->persist($state);
        }
    }
}
