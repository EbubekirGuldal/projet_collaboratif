<?php

namespace App\Entity;

use App\Repository\UserResourceStateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserResourceStateRepository::class)]
class UserResourceState
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isFavorite = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isExploited = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isSavedForLater = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastInteractionAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isFavorite(): ?bool
    {
        return $this->isFavorite;
    }

    public function setIsFavorite(?bool $isFavorite): static
    {
        $this->isFavorite = $isFavorite;

        return $this;
    }

    public function isExploited(): ?bool
    {
        return $this->isExploited;
    }

    public function setIsExploited(?bool $isExploited): static
    {
        $this->isExploited = $isExploited;

        return $this;
    }

    public function isSavedForLater(): ?bool
    {
        return $this->isSavedForLater;
    }

    public function setIsSavedForLater(?bool $isSavedForLater): static
    {
        $this->isSavedForLater = $isSavedForLater;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getLastInteractionAt(): ?\DateTimeImmutable
    {
        return $this->lastInteractionAt;
    }

    public function setLastInteractionAt(?\DateTimeImmutable $lastInteractionAt): static
    {
        $this->lastInteractionAt = $lastInteractionAt;

        return $this;
    }
}
