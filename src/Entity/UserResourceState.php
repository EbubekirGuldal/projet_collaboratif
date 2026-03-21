<?php

namespace App\Entity;

use App\Repository\UserResourceStateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserResourceStateRepository::class)]
#[ORM\Table(name: 'user_resource_state')]
#[ORM\UniqueConstraint(name: 'uniq_user_resource', columns: ['user_id', 'resource_id'])]
class UserResourceState
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Resource $resource = null;

    #[ORM\Column]
    private bool $isLiked = false;

    #[ORM\Column]
    private bool $isSaved = false;

    #[ORM\Column]
    private bool $isExploited = false;

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getResource(): ?Resource { return $this->resource; }
    public function setResource(?Resource $resource): static { $this->resource = $resource; return $this; }

    public function isLiked(): bool { return $this->isLiked; }
    public function setIsLiked(bool $v): static { $this->isLiked = $v; return $this; }

    public function isSaved(): bool { return $this->isSaved; }
    public function setIsSaved(bool $v): static { $this->isSaved = $v; return $this; }

    public function isExploited(): bool { return $this->isExploited; }
    public function setIsExploited(bool $v): static { $this->isExploited = $v; return $this; }
}