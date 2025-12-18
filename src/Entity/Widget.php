<?php

namespace App\Entity;

use App\Repository\WidgetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Collection;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WidgetRepository::class)]
#[ORM\HasLifecycleCallbacks]
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

    #[ORM\Column(nullable: true)]
    #[Groups(['widget:read'])]
    private ?int $imageSize = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['widget:read'])]
    private ?int $speed = null;

    #[ORM\ManyToOne(inversedBy: 'widgets')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\OneToMany(targetEntity: Attachment::class, mappedBy: 'widget', cascade: ['persist','remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Groups(['widget:read'])]
    private $attachments;

    public function __construct(
        User $user,
        ?string $title = null,
    )
    {
        $this->user = $user;
        $this->title = $title;
        $this->attachments = new ArrayCollection();
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

    /** @return Attachment[] */
    public function getAttachments(): array
    {
        return $this->attachments->toArray();
    }

    public function addAttachment(Attachment $a): void
    {
        if (!$this->attachments->contains($a)) {
            $this->attachments->add($a);
            $a->setWidget($this);
        }
    }

    public function removeAttachment(Attachment $a): void
    {
        $this->attachments->removeElement($a);
    }

    public function getImageSize(): ?int
    {
        return $this->imageSize;
    }

    public function setImageSize(?int $imageSize): void
    {
        $this->imageSize = $imageSize;
    }

    public function getSpeed(): ?int
    {
        return $this->speed;
    }

    public function setSpeed(?int $speed): void
    {
        $this->speed = $speed;
    }
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['widget:read'])]
    private bool $pauseOnHover = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['widget:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function getPauseOnHover(): bool
    {
        return $this->pauseOnHover;
    }

    public function setPauseOnHover(bool $pauseOnHover): void
    {
        $this->pauseOnHover = $pauseOnHover;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['widget:read'])]
    private ?int $gap = null;

    public function getGap(): ?int
    {
        return $this->gap;
    }

    public function setGap(?int $gap): void
    {
        $this->gap = $gap;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
