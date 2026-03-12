<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use App\Enum\ResourceStatus;
use App\Repository\ResourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Attribute\Groups;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ApiResource(
    paginationItemsPerPage: 10,
    paginationPartial: true,
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['resource:read']],
            security: "true"
        ),
        new Get(
            normalizationContext: ['groups' => ['resource:read']],
            security: "true"
        ),
        new Patch(
            normalizationContext: ['groups' => ['resource:read']],
            denormalizationContext: ['groups' => ['resource:update']],
            security: "object.getUser() == user"
        ),
    ]
)]
#[ORM\Entity(repositoryClass: ResourceRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class Resource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['resource:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['resource:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['resource:read'])]
    private ?string $content = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['resource:read'])]
    private ?string $externalUrl = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['resource:read'])]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['resource:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['resource:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['resource:read'])]
    private ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['resource:read'])]
    private ?string $video = null;

    #[ORM\Column(options: ["default" => 0])]
    #[Groups(['resource:read'])]
    private ?int $sharesCount = 0;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'resource', orphanRemoval: true)]
    private Collection $comments;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     */
    #[Vich\UploadableField(mapping: 'products', fileNameProperty: 'image')]
    private ?File $imageFile = null;

    #[ORM\ManyToOne(inversedBy: 'resources')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['resource:read'])]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?int $likesCount = 0;

    /**
     * @var Collection<int, ModerationLog>
     */
    #[ORM\OneToMany(targetEntity: ModerationLog::class, mappedBy: 'resource')]
    private Collection $moderationLogs;

    #[ORM\Column(enumType: ResourceStatus::class)]
    private ResourceStatus $resourceStatus = ResourceStatus::PUBLIC;

    public function __toString()
    {
        return $this->id." - ".$this->title;
    }

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->comments = new ArrayCollection();
        $this->moderationLogs = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function onUpdated(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    } 

    public function getLikesCount(): ?int
    {
        return $this->likesCount;
    }

    public function setLikesCount(?int $likesCount): static
    {
        $this->likesCount = $likesCount;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getExternalUrl(): ?string
    {
        return $this->externalUrl;
    }

    public function setExternalUrl(?string $externalUrl): static
    {
        $this->externalUrl = $externalUrl;

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getVideo(): ?string
    {
        return $this->video;
    }

    public function setVideo(?string $video): static
    {
        $this->video = $video;

        return $this;
    }

    public function getSharesCount(): ?int
    {
        return $this->sharesCount;
    }

    public function setSharesCount(int $sharesCount): static
    {
        $this->sharesCount = $sharesCount;

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setResource($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getResource() === $this) {
                $comment->setResource(null);
            }
        }

        return $this;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // Important : force update pour déclencher l’upload
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, ModerationLog>
     */
    public function getModerationLogs(): Collection
    {
        return $this->moderationLogs;
    }

    public function addModerationLog(ModerationLog $moderationLog): static
    {
        if (!$this->moderationLogs->contains($moderationLog)) {
            $this->moderationLogs->add($moderationLog);
            $moderationLog->setResource($this);
        }

        return $this;
    }

    public function removeModerationLog(ModerationLog $moderationLog): static
    {
        if ($this->moderationLogs->removeElement($moderationLog)) {
            // set the owning side to null (unless already changed)
            if ($moderationLog->getResource() === $this) {
                $moderationLog->setResource(null);
            }
        }

        return $this;
    }

    public function getResourceStatus(): ?ResourceStatus
    {
        return $this->resourceStatus;
    }

    public function setResourceStatus(ResourceStatus $resourceStatus): static
    {
        $this->resourceStatus = $resourceStatus;

        return $this;
    }

}
