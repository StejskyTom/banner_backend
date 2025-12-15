<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Index(columns: ['full_path'], name: 'idx_full_path')]
class Category
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['category:read', 'product:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['category:read', 'product:read'])]
    private string $name;

    #[ORM\Column(length: 1000)]
    #[Groups(['category:read'])]
    private string $fullPath;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Category $parent = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $children;

    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'category')]
    private Collection $products;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['category:read'])]
    private int $productCount = 0;

    public function __construct(string $name, string $fullPath, ?Category $parent = null)
    {
        $this->name = $name;
        $this->fullPath = $fullPath;
        $this->parent = $parent;
        $this->children = new ArrayCollection();
        $this->products = new ArrayCollection();
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

    public function getFullPath(): string
    {
        return $this->fullPath;
    }

    public function setFullPath(string $fullPath): void
    {
        $this->fullPath = $fullPath;
    }

    public function getParent(): ?Category
    {
        return $this->parent;
    }

    public function setParent(?Category $parent): void
    {
        $this->parent = $parent;
    }

    public function getChildren(): array
    {
        return $this->children->toArray();
    }

    public function getProducts(): array
    {
        return $this->products->toArray();
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
}
