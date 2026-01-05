<?php

namespace App\Entity;

use App\Repository\AttachmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AttachmentRepository::class)]
class Attachment
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['widget:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'attachments')]
    #[ORM\JoinColumn(nullable: false)]
    private Widget $widget;

    #[ORM\Column(length: 255)]
    #[Groups(['widget:read'])]
    private string $url;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isExternal = false;

    #[ORM\Column(type: 'integer')]
    #[Groups(['widget:read'])]
    private int $position = 0;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['widget:read'])]
    private ?string $link = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['widget:read'])]
    private ?string $alt = null;

    public function __construct(
        Widget $widget,
        string $url = '',
    )
    {
        $this->widget = $widget;
        $this->url = $url;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getWidget(): ?Widget
    {
        return $this->widget;
    }

    public function setWidget(Widget $widget): void
    {
        $this->widget = $widget;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function isExternal(): bool
    {
        return $this->isExternal;
    }

    public function setIsExternal(bool $isExternal): void
    {
        $this->isExternal = $isExternal;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): void
    {
        $this->link = $link;
    }

    public function getAlt(): ?string
    {
        return $this->alt;
    }

    public function setAlt(?string $alt): void
    {
        $this->alt = $alt;
    }
}
