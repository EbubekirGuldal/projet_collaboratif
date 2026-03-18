<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Resource;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class ResourcePostProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Resource) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedException('Authentication is required to create a resource.');
        }

        $data->setUser($user);
        $data->setPublishedAt($data->getPublishedAt() ?? new \DateTimeImmutable());
        $data->setSharesCount($data->getSharesCount() ?? 0);
        $data->setLikesCount($data->getLikesCount() ?? 0);

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
