<?php

namespace App\Security;

use App\Entity\Resource;
use App\Entity\User;
use App\Enum\ResourceStatus;

final class ResourceAccessResolver
{
    public function canView(?User $user, Resource $resource): bool
    {
        if ($this->isStaff($user)) {
            return true;
        }

        if ($resource->getResourceStatus() === ResourceStatus::PUBLIC) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if ($resource->getUser() === $user) {
            return true;
        }

        return $resource->getResourceStatus() === ResourceStatus::SHARED;
    }

    public function canInteract(?User $user, Resource $resource): bool
    {
        return $user !== null
            && $user->getIsVerified()
            && $this->canView($user, $resource)
            && !$resource->isUnderReview();
    }

    public function canManageCatalog(?User $user): bool
    {
        return $user !== null && in_array('ROLE_ADMIN', $user->getRoles(), true);
    }

    public function canManageUsers(?User $user): bool
    {
        return $user !== null && in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
    }

    public function canModerate(?User $user): bool
    {
        return $user !== null && (in_array('ROLE_MODERATOR', $user->getRoles(), true) || in_array('ROLE_ADMIN', $user->getRoles(), true));
    }

    private function isStaff(?User $user): bool
    {
        return $user !== null && (
            in_array('ROLE_MODERATOR', $user->getRoles(), true)
            || in_array('ROLE_ADMIN', $user->getRoles(), true)
        );
    }
}
