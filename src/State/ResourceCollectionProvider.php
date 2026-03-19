<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Resource;
use App\Entity\User;
use App\Repository\ResourceLikeRepository;
use App\Security\ResourceAccessResolver;
use Symfony\Bundle\SecurityBundle\Security;

final class ResourceCollectionProvider implements ProviderInterface
{
    public function __construct(
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')]
        private ProviderInterface $collectionProvider,
        private Security $security,
        private ResourceLikeRepository $resourceLikeRepository,
        private ResourceAccessResolver $resourceAccessResolver,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $resources = $this->collectionProvider->provide($operation, $uriVariables, $context);

        $user = $this->security->getUser();
        $resourceArray = [];

        foreach ($resources as $resource) {
            if ($resource instanceof Resource && $this->resourceAccessResolver->canView($user instanceof User ? $user : null, $resource)) {
                $resourceArray[] = $resource;
            }
        }

        if (!$user instanceof User) {
            foreach ($resourceArray as $resource) {
                $resource->setIsLiked(false);
            }

            return $resourceArray;
        }

        $likedIds = array_map(
            static fn (Resource $resource): int => $resource->getId(),
            array_filter(
                $resourceArray,
                fn (Resource $resource): bool => $this->resourceLikeRepository->hasUserLiked($user, $resource)
            )
        );
        $likedIdsMap = array_flip($likedIds);

        foreach ($resourceArray as $resource) {
            $resource->setIsLiked(isset($likedIdsMap[$resource->getId()]));
        }

        return $resourceArray;
    }
}
