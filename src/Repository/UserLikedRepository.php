<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserLiked;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserLiked>
 */
class UserLikedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserLiked::class);
    }

    public function findLikedResourceIdsByUserAndResources(User $user, array $resources): array
    {
        if (empty($resources)) {
            return [];
        }

        $resourceIds = array_map(fn($resource) => $resource->getId(), $resources);

        $rows = $this->createQueryBuilder('ul')
            ->select('IDENTITY(ul.resource) as resourceId')
            ->andWhere('ul.user = :user')
            ->andWhere('ul.resource IN (:resourceIds)')
            ->setParameter('user', $user)
            ->setParameter('resourceIds', $resourceIds)
            ->getQuery()
            ->getArrayResult();

        return array_map(fn($row) => (int) $row['resourceId'], $rows);
    }
}
