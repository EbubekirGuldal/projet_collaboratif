<?php

namespace App\Tests\Security;

use App\Entity\Resource;
use App\Entity\User;
use App\Enum\ResourceStatus;
use App\Security\ResourceAccessResolver;
use PHPUnit\Framework\TestCase;

final class ResourceAccessResolverTest extends TestCase
{
    private ResourceAccessResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ResourceAccessResolver();
    }

    public function testAnonymousUserCanOnlyViewPublicResources(): void
    {
        $public = (new Resource())->setResourceStatus(ResourceStatus::PUBLIC);
        $shared = (new Resource())->setResourceStatus(ResourceStatus::SHARED);

        self::assertTrue($this->resolver->canView(null, $public));
        self::assertFalse($this->resolver->canView(null, $shared));
    }

    public function testOwnerCanViewPrivateResources(): void
    {
        $owner = (new User())->setEmail('owner@example.com')->setUsername('owner');
        $resource = (new Resource())
            ->setUser($owner)
            ->setResourceStatus(ResourceStatus::PRIVATE);

        self::assertTrue($this->resolver->canView($owner, $resource));
    }

    public function testVerifiedUserCanInteractWithSharedResource(): void
    {
        $user = (new User())
            ->setEmail('verified@example.com')
            ->setUsername('verified')
            ->setIsVerified(true);

        $resource = (new Resource())->setResourceStatus(ResourceStatus::SHARED);

        self::assertTrue($this->resolver->canInteract($user, $resource));
    }

    public function testUnverifiedUserCannotInteract(): void
    {
        $user = (new User())
            ->setEmail('pending@example.com')
            ->setUsername('pending')
            ->setIsVerified(false);

        $resource = (new Resource())->setResourceStatus(ResourceStatus::PUBLIC);

        self::assertFalse($this->resolver->canInteract($user, $resource));
    }
}
