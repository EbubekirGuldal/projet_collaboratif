<?php

namespace App\Repository;

use App\Entity\Resource;
use App\Entity\ResourceLike;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ResourceLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResourceLike::class);
    }

    public function findOneByUserAndResource(User $user, Resource $resource): ?ResourceLike
    {
        return $this->findOneBy([
            'user' => $user,
            'resource' => $resource,
        ]);
    }

    public function hasUserLiked(User $user, Resource $resource): bool
    {
        return null !== $this->findOneByUserAndResource($user, $resource);
    }

    public function countForResource(Resource $resource): int
    {
        return (int) $this->createQueryBuilder('rl')
            ->select('COUNT(rl.id)')
            ->andWhere('rl.resource = :resource')
            ->setParameter('resource', $resource)
            ->getQuery()
            ->getSingleScalarResult();
    }
}