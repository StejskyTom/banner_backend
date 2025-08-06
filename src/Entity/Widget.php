<?php

namespace App\Entity;

use App\Repository\WidgetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WidgetRepository::class)]
class Widget
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['widget:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['widget:read'])]
    private ?string $title;

    #[ORM\ManyToOne(inversedBy: 'widgets')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['widget:read'])]
    private array $logos = [];

    public function __construct(
        User $user,
        ?string $title = null,
    )
    {
        $this->user = $user;
        $this->title = $title;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getLogos(): array
    {
        return $this->logos;
    }

    public function setLogos(array $logos): static
    {
        $this->logos = $logos;
        return $this;
    }

    public function addLogo(string $logoUrl): static
    {
        if (!in_array($logoUrl, $this->logos)) {
            $this->logos[] = $logoUrl;
        }
        return $this;
    }

    public function removeLogo(string $logoUrl): static
    {
        $key = array_search($logoUrl, $this->logos);
        if ($key !== false) {
            unset($this->logos[$key]);
            $this->logos = array_values($this->logos); // reindexování
        }
        return $this;
    }
}
