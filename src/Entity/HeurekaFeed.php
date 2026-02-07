<?php

namespace App\Entity;

use App\Repository\HeurekaFeedRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: HeurekaFeedRepository::class)]
class HeurekaFeed
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['feed:read'])]
    private ?Uuid $id = null;

    public function __clone()
    {
        $this->id = null;
        $this->products = new ArrayCollection();
        $this->lastSyncedAt = null;
        $this->productCount = 0;
    }

    #[ORM\Column(length: 255)]
    #[Groups(['feed:read'])]
    private string $name;

    #[ORM\Column(length: 500)]
    #[Groups(['feed:read'])]
    private string $url;

    #[ORM\ManyToOne(inversedBy: 'heurekaFeeds')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'feed', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['feed:read:detailed'])]
    private Collection $products;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['feed:read'])]
    private ?\DateTimeInterface $lastSyncedAt = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['feed:read'])]
    private int $productCount = 0;

    #[ORM\Column(length: 50, options: ['default' => 'carousel'])]
    #[Groups(['feed:read'])]
    private string $layout = 'carousel';

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['feed:read'])]
    private ?array $layoutOptions = null;

    public function __construct(User $user, string $name, string $url)
    {
        $this->user = $user;
        $this->name = $name;
        $this->url = $url;
        $this->products = new ArrayCollection();
        $this->layout = 'carousel';
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getProducts(): array
    {
        return $this->products->toArray();
    }

    public function addProduct(Product $product): void
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setFeed($this);
        }
    }

    public function removeProduct(Product $product): void
    {
        $this->products->removeElement($product);
    }

    public function getLastSyncedAt(): ?\DateTimeInterface
    {
        return $this->lastSyncedAt;
    }

    public function setLastSyncedAt(?\DateTimeInterface $lastSyncedAt): void
    {
        $this->lastSyncedAt = $lastSyncedAt;
    }

    public function getProductCount(): int
    {
        return $this->productCount;
    }

    public function setProductCount(int $count): void
    {
        $this->productCount = $count;
    }

    public function incrementProductCount(): void
    {
        $this->productCount++;
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    public function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    public function getLayoutOptions(): ?array
    {
        return $this->layoutOptions;
    }

    public function setLayoutOptions(?array $layoutOptions): void
    {
        $this->layoutOptions = $layoutOptions;
    }
}
