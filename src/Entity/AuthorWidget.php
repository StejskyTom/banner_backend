<?php

namespace App\Entity;

use App\Repository\AuthorWidgetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AuthorWidgetRepository::class)]
class AuthorWidget
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['author_widget:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['author_widget:read', 'author_widget:write'])]
    private ?string $name = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['author_widget:read', 'author_widget:write'])]
    private ?string $authorName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['author_widget:read', 'author_widget:write'])]
    private ?string $authorTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['author_widget:read', 'author_widget:write'])]
    private ?string $authorBio = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['author_widget:read', 'author_widget:write'])]
    private ?string $authorPhotoUrl = null;

    #[ORM\Column(length: 50, options: ['default' => 'centered'])]
    #[Groups(['author_widget:read', 'author_widget:write'])]
    private string $layout = 'centered';

    #[ORM\Column]
    #[Groups(['author_widget:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['author_widget:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['author_widget:read', 'author_widget:write'])]
    private ?string $backgroundColor = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['default' => 0])]
    #[Groups(['author_widget:read', 'author_widget:write'])]
    private ?int $borderRadius = 0;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['author_widget:read', 'author_widget:write'])]
    private ?string $nameColor = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['author_widget:read', 'author_widget:write'])]
    private ?string $bioColor = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['author_widget:read', 'author_widget:write'])]
    private ?string $titleColor = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
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

    public function getAuthorName(): ?string
    {
        return $this->authorName;
    }

    public function setAuthorName(?string $authorName): static
    {
        $this->authorName = $authorName;

        return $this;
    }

    public function getAuthorTitle(): ?string
    {
        return $this->authorTitle;
    }

    public function setAuthorTitle(?string $authorTitle): static
    {
        $this->authorTitle = $authorTitle;

        return $this;
    }

    public function getAuthorBio(): ?string
    {
        return $this->authorBio;
    }

    public function setAuthorBio(?string $authorBio): static
    {
        $this->authorBio = $authorBio;

        return $this;
    }

    public function getAuthorPhotoUrl(): ?string
    {
        return $this->authorPhotoUrl;
    }

    public function setAuthorPhotoUrl(?string $authorPhotoUrl): static
    {
        $this->authorPhotoUrl = $authorPhotoUrl;

        return $this;
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    public function setLayout(string $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function setBackgroundColor(?string $backgroundColor): static
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    public function getBorderRadius(): ?int
    {
        return $this->borderRadius;
    }

    public function setBorderRadius(?int $borderRadius): static
    {
        $this->borderRadius = $borderRadius;

        return $this;
    }

    public function getNameColor(): ?string
    {
        return $this->nameColor;
    }

    public function setNameColor(?string $nameColor): static
    {
        $this->nameColor = $nameColor;

        return $this;
    }

    public function getBioColor(): ?string
    {
        return $this->bioColor;
    }

    public function setBioColor(?string $bioColor): static
    {
        $this->bioColor = $bioColor;

        return $this;
    }

    public function getTitleColor(): ?string
    {
        return $this->titleColor;
    }

    public function setTitleColor(?string $titleColor): static
    {
        $this->titleColor = $titleColor;

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

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
