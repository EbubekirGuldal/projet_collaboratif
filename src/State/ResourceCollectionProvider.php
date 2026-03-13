<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Resource;
use App\Entity\User;
use App\Repository\UserLikedRepository;
use Symfony\Bundle\SecurityBundle\Security;

final class ResourceCollectionProvider implements ProviderInterface
{
    public function __construct(
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')]
        private ProviderInterface $collectionProvider,
        private Security $security,
        private UserLikedRepository $userLikedRepository,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $resources = $this->collectionProvider->provide($operation, $uriVariables, $context);

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            foreach ($resources as $resource) {
                if ($resource instanceof Resource) {
                    $resource->setIsLiked(false);
                }
            }

            return $resources;
        }

        $resourceArray = [];
        foreach ($resources as $resource) {
            if ($resource instanceof Resource) {
                $resourceArray[] = $resource;
            }
        }

        $likedIds = $this->userLikedRepository->findLikedResourceIdsByUserAndResources($user, $resourceArray);
        $likedIdsMap = array_flip($likedIds);

        foreach ($resourceArray as $resource) {
            $resource->setIsLiked(isset($likedIdsMap[$resource->getId()]));
        }

        return $resources;
    }
}
