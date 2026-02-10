<?php

namespace App\Entity;

use App\Repository\WidgetStatRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WidgetStatRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_widget_date', columns: ['widget_id', 'date'])]
class WidgetStat
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: UuidType::NAME)]
    private ?Uuid $widgetId = null;

    #[ORM\Column(length: 50)]
    private ?string $widgetType = null; // 'author', 'faq', 'carousel', etc.

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column]
    private ?int $views = 0;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getWidgetId(): ?Uuid
    {
        return $this->widgetId;
    }

    public function setWidgetId(Uuid $widgetId): static
    {
        $this->widgetId = $widgetId;

        return $this;
    }

    public function getWidgetType(): ?string
    {
        return $this->widgetType;
    }

    public function setWidgetType(string $widgetType): static
    {
        $this->widgetType = $widgetType;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getViews(): ?int
    {
        return $this->views;
    }

    public function setViews(int $views): static
    {
        $this->views = $views;

        return $this;
    }

    public function incrementViews(int $amount = 1): static
    {
        $this->views += $amount;
        return $this;
    }
}
