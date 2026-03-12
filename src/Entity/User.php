<?php

namespace App\Entity;

use App\Entity\Resource;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\ManyToMany(targetEntity: Resource::class)]
    #[ORM\JoinTable(name: 'user_favorites')]
    private Collection $favorites;

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(options: ["default" => false])]
    private ?bool $isVerified = false;

    #[ORM\Column(options: ["default" => true])]
    private ?bool $isActive = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $picture = null;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     */
    #[Vich\UploadableField(mapping: 'users', fileNameProperty: 'picture')]
    private ?File $imageFile = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastConnexion = null;

    /**
     * @var Collection<int, Resource>
     */
    #[ORM\OneToMany(targetEntity: Resource::class, mappedBy: 'user')]
    private Collection $resources;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'user')]
    private Collection $comments;

    /**
     * @var Collection<int, ModerationLog>
     */
    #[ORM\OneToMany(targetEntity: ModerationLog::class, mappedBy: 'user')]
    private Collection $moderationLogs;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->resources = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->moderationLogs = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->username ?: (string) $this->email;
    }

    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'roles' => $this->roles,
            'username' => $this->username,
            'password' => $this->password ? hash('crc32c', $this->password) : null,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id = $data['id'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->roles = $data['roles'] ?? [];
        $this->username = $data['username'] ?? null;

        // on ne peut pas reconstruire le vrai hash depuis crc32c -> donc on laisse null
        $this->password = null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function isVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
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

    public function getLastConnexion(): ?\DateTimeImmutable
    {
        return $this->lastConnexion;
    }

    public function setLastConnexion(\DateTimeImmutable $lastConnexion): static
    {
        $this->lastConnexion = $lastConnexion;
        return $this;
    }

    /**
     * @return Collection<int, Resource>
     */
    public function getResources(): Collection
    {
        return $this->resources;
    }

    public function addResource(Resource $resource): static
    {
        if (!$this->resources->contains($resource)) {
            $this->resources->add($resource);
            $resource->setUser($this);
        }

        return $this;
    }

    public function removeResource(Resource $resource): static
    {
        if ($this->resources->removeElement($resource)) {
            if ($resource->getUser() === $this) {
                $resource->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /**
     * @return Collection<int, Resource>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Resource $resource): static
    {
        if (!$this->favorites->contains($resource)) {
            $this->favorites->add($resource);
        }
        return $this;
    }

    public function removeFavorite(Resource $resource): static
    {
        $this->favorites->removeElement($resource);
        return $this;
    }

    public function isFavorite(Resource $resource): bool
    {
        return $this->favorites->contains($resource);
    }

     public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): static
    {
        $this->picture = $picture;

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
            $moderationLog->setUser($this);
        }

        return $this;
    }

    public function removeModerationLog(ModerationLog $moderationLog): static
    {
        if ($this->moderationLogs->removeElement($moderationLog)) {
            // set the owning side to null (unless already changed)
            if ($moderationLog->getUser() === $this) {
                $moderationLog->setUser(null);
            }
        }

        return $this;
    }
}

