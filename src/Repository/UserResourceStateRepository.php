<?php

namespace App\Repository;

use App\Entity\Resource;
use App\Entity\User;
use App\Entity\UserResourceState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserResourceStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserResourceState::class);
    }

    public function findOne(User $user, Resource $resource): ?UserResourceState
    {
        return $this->findOneBy([
            'user' => $user,
            'resource' => $resource
        ]);
    }
}