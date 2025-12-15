<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Index(columns: ['item_id'], name: 'idx_item_id')]
class Product
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['product:read', 'feed:read:detailed', 'widget:embed'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private HeurekaFeed $feed;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read', 'widget:embed'])]
    private string $itemId;

    #[ORM\Column(length: 500)]
    #[Groups(['product:read', 'widget:embed'])]
    private string $productName;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['product:read', 'widget:embed'])]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['product:read', 'widget:embed'])]
    private string $priceVat;

    #[ORM\Column(length: 1000)]
    #[Groups(['product:read', 'widget:embed'])]
    private string $url;

    #[ORM\Column(length: 1000, nullable: true)]
    #[Groups(['product:read', 'widget:embed'])]
    private ?string $imgUrl = null;

    #[ORM\Column(length: 1000, nullable: true)]
    #[Groups(['product:read'])]
    private ?string $imgUrlAlternative = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product:read'])]
    private ?string $manufacturer = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['product:read'])]
    private ?string $ean = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['product:read'])]
    private ?string $productNo = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['product:read'])]
    private ?Category $category = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['product:read', 'widget:embed'])]
    private bool $isSelected = false;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['product:read', 'widget:embed'])]
    private int $position = 0;

    public function __construct(
        HeurekaFeed $feed,
        string $itemId,
        string $productName,
        string $priceVat,
        string $url
    ) {
        $this->feed = $feed;
        $this->itemId = $itemId;
        $this->productName = $productName;
        $this->priceVat = $priceVat;
        $this->url = $url;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getFeed(): HeurekaFeed
    {
        return $this->feed;
    }

    public function setFeed(HeurekaFeed $feed): void
    {
        $this->feed = $feed;
    }

    public function getItemId(): string
    {
        return $this->itemId;
    }

    public function setItemId(string $itemId): void
    {
        $this->itemId = $itemId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): void
    {
        $this->productName = $productName;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getPriceVat(): string
    {
        return $this->priceVat;
    }

    public function setPriceVat(string $priceVat): void
    {
        $this->priceVat = $priceVat;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getImgUrl(): ?string
    {
        return $this->imgUrl;
    }

    public function setImgUrl(?string $imgUrl): void
    {
        $this->imgUrl = $imgUrl;
    }

    public function getImgUrlAlternative(): ?string
    {
        return $this->imgUrlAlternative;
    }

    public function setImgUrlAlternative(?string $imgUrlAlternative): void
    {
        $this->imgUrlAlternative = $imgUrlAlternative;
    }

    public function getManufacturer(): ?string
    {
        return $this->manufacturer;
    }

    public function setManufacturer(?string $manufacturer): void
    {
        $this->manufacturer = $manufacturer;
    }

    public function getEan(): ?string
    {
        return $this->ean;
    }

    public function setEan(?string $ean): void
    {
        $this->ean = $ean;
    }

    public function getProductNo(): ?string
    {
        return $this->productNo;
    }

    public function setProductNo(?string $productNo): void
    {
        $this->productNo = $productNo;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): void
    {
        $this->category = $category;
    }

    public function isSelected(): bool
    {
        return $this->isSelected;
    }

    public function setIsSelected(bool $isSelected): void
    {
        $this->isSelected = $isSelected;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }
}
