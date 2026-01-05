<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $resetPasswordToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetPasswordTokenExpiresAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $googleId = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $avatarUrl = null;

    // Billing Information
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'user:billing'])]
    private ?string $billingName = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['user:read', 'user:billing'])]
    private ?string $billingIco = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['user:read', 'user:billing'])]
    private ?string $billingDic = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'user:billing'])]
    private ?string $billingStreet = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'user:billing'])]
    private ?string $billingCity = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['user:read', 'user:billing'])]
    private ?string $billingZip = null;

    #[ORM\Column(length: 2, nullable: true)]
    #[Groups(['user:read', 'user:billing'])]
    private ?string $billingCountry = 'CZ';

    /**
     * @var Collection<int, Widget>
     */
    #[ORM\OneToMany(targetEntity: Widget::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $widgets;

    /**
     * @var Collection<int, HeurekaFeed>
     */
    #[ORM\OneToMany(targetEntity: HeurekaFeed::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $heurekaFeeds;

    /**
     * @var Collection<int, Subscription>
     */
    #[ORM\OneToMany(targetEntity: Subscription::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $subscriptions;

    /**
     * @var Collection<int, Payment>
     */
    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'user')]
    private Collection $payments;

    public function __construct()
    {
        $this->widgets = new ArrayCollection();
        $this->heurekaFeeds = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
        $this->payments = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getResetPasswordToken(): ?string
    {
        return $this->resetPasswordToken;
    }

    public function setResetPasswordToken(?string $resetPasswordToken): self
    {
        $this->resetPasswordToken = $resetPasswordToken;

        return $this;
    }

    public function getResetPasswordTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->resetPasswordTokenExpiresAt;
    }

    public function setResetPasswordTokenExpiresAt(?\DateTimeInterface $resetPasswordTokenExpiresAt): self
    {
        $this->resetPasswordTokenExpiresAt = $resetPasswordTokenExpiresAt;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    /**
     * @return Collection<int, Widget>
     */
    public function getWidgets(): Collection
    {
        return $this->widgets;
    }

    public function addWidget(Widget $widget): static
    {
        if (!$this->widgets->contains($widget)) {
            $this->widgets->add($widget);
            $widget->setUser($this);
        }

        return $this;
    }

    public function removeWidget(Widget $widget): static
    {
        if ($this->widgets->removeElement($widget)) {
            // set the owning side to null (unless already changed)
            if ($widget->getUser() === $this) {
                $widget->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, HeurekaFeed>
     */
    public function getHeurekaFeeds(): Collection
    {
        return $this->heurekaFeeds;
    }

    public function addHeurekaFeed(HeurekaFeed $feed): static
    {
        if (!$this->heurekaFeeds->contains($feed)) {
            $this->heurekaFeeds->add($feed);
        }

        return $this;
    }

    public function removeHeurekaFeed(HeurekaFeed $feed): static
    {
        $this->heurekaFeeds->removeElement($feed);

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): static
    {
        $this->avatarUrl = $avatarUrl;

        return $this;
    }

    public function getBillingName(): ?string
    {
        return $this->billingName;
    }

    public function setBillingName(?string $billingName): self
    {
        $this->billingName = $billingName;

        return $this;
    }

    public function getBillingIco(): ?string
    {
        return $this->billingIco;
    }

    public function setBillingIco(?string $billingIco): self
    {
        $this->billingIco = $billingIco;

        return $this;
    }

    public function getBillingDic(): ?string
    {
        return $this->billingDic;
    }

    public function setBillingDic(?string $billingDic): self
    {
        $this->billingDic = $billingDic;

        return $this;
    }

    public function getBillingStreet(): ?string
    {
        return $this->billingStreet;
    }

    public function setBillingStreet(?string $billingStreet): self
    {
        $this->billingStreet = $billingStreet;

        return $this;
    }

    public function getBillingCity(): ?string
    {
        return $this->billingCity;
    }

    public function setBillingCity(?string $billingCity): self
    {
        $this->billingCity = $billingCity;

        return $this;
    }

    public function getBillingZip(): ?string
    {
        return $this->billingZip;
    }

    public function setBillingZip(?string $billingZip): self
    {
        $this->billingZip = $billingZip;

        return $this;
    }

    public function getBillingCountry(): ?string
    {
        return $this->billingCountry;
    }

    public function setBillingCountry(?string $billingCountry): self
    {
        $this->billingCountry = $billingCountry;

        return $this;
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(Subscription $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setUser($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription)) {
            // set the owning side to null (unless already changed)
            if ($subscription->getUser() === $this) {
                $subscription->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setUser($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getUser() === $this) {
                $payment->setUser(null);
            }
        }

        return $this;
    }
}
