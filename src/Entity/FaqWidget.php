<?php

namespace App\Entity;

use App\Repository\FaqWidgetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: FaqWidgetRepository::class)]
class FaqWidget
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['faq_widget:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $name = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private array $questions = [];

    #[ORM\Column]
    #[Groups(['faq_widget:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['faq_widget:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $font = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $questionColor = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $answerColor = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $hoverColor = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $backgroundColor = null;

    // Question styling properties
    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $questionTag = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $questionSize = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $questionFont = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?bool $questionBold = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?bool $questionItalic = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $questionAlign = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?int $questionMarginBottom = null;

    // Answer styling properties
    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $answerTag = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $answerSize = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $answerFont = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?bool $answerBold = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?bool $answerItalic = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $answerAlign = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?int $answerMarginBottom = null;

    // Arrow settings
    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $arrowPosition = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $arrowColor = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?int $arrowSize = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->questions = [];
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

    public function getQuestions(): array
    {
        return $this->questions;
    }

    public function setQuestions(array $questions): static
    {
        $this->questions = $questions;

        return $this;
    }

    public function getFont(): ?string
    {
        return $this->font;
    }

    public function setFont(?string $font): static
    {
        $this->font = $font;

        return $this;
    }

    public function getQuestionColor(): ?string
    {
        return $this->questionColor;
    }

    public function setQuestionColor(?string $questionColor): static
    {
        $this->questionColor = $questionColor;

        return $this;
    }

    public function getAnswerColor(): ?string
    {
        return $this->answerColor;
    }

    public function setAnswerColor(?string $answerColor): static
    {
        $this->answerColor = $answerColor;

        return $this;
    }

    public function getHoverColor(): ?string
    {
        return $this->hoverColor;
    }

    public function setHoverColor(?string $hoverColor): static
    {
        $this->hoverColor = $hoverColor;

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

    // Question styling getters/setters
    public function getQuestionTag(): ?string
    {
        return $this->questionTag;
    }

    public function setQuestionTag(?string $questionTag): static
    {
        $this->questionTag = $questionTag;
        return $this;
    }

    public function getQuestionSize(): ?string
    {
        return $this->questionSize;
    }

    public function setQuestionSize(?string $questionSize): static
    {
        $this->questionSize = $questionSize;
        return $this;
    }

    public function getQuestionFont(): ?string
    {
        return $this->questionFont;
    }

    public function setQuestionFont(?string $questionFont): static
    {
        $this->questionFont = $questionFont;
        return $this;
    }

    public function isQuestionBold(): ?bool
    {
        return $this->questionBold;
    }

    public function setQuestionBold(?bool $questionBold): static
    {
        $this->questionBold = $questionBold;
        return $this;
    }

    public function isQuestionItalic(): ?bool
    {
        return $this->questionItalic;
    }

    public function setQuestionItalic(?bool $questionItalic): static
    {
        $this->questionItalic = $questionItalic;
        return $this;
    }

    public function getQuestionAlign(): ?string
    {
        return $this->questionAlign;
    }

    public function setQuestionAlign(?string $questionAlign): static
    {
        $this->questionAlign = $questionAlign;
        return $this;
    }

    public function getQuestionMarginBottom(): ?int
    {
        return $this->questionMarginBottom;
    }

    public function setQuestionMarginBottom(?int $questionMarginBottom): static
    {
        $this->questionMarginBottom = $questionMarginBottom;
        return $this;
    }

    // Answer styling getters/setters
    public function getAnswerTag(): ?string
    {
        return $this->answerTag;
    }

    public function setAnswerTag(?string $answerTag): static
    {
        $this->answerTag = $answerTag;
        return $this;
    }

    public function getAnswerSize(): ?string
    {
        return $this->answerSize;
    }

    public function setAnswerSize(?string $answerSize): static
    {
        $this->answerSize = $answerSize;
        return $this;
    }

    public function getAnswerFont(): ?string
    {
        return $this->answerFont;
    }

    public function setAnswerFont(?string $answerFont): static
    {
        $this->answerFont = $answerFont;
        return $this;
    }

    public function isAnswerBold(): ?bool
    {
        return $this->answerBold;
    }

    public function setAnswerBold(?bool $answerBold): static
    {
        $this->answerBold = $answerBold;
        return $this;
    }

    public function isAnswerItalic(): ?bool
    {
        return $this->answerItalic;
    }

    public function setAnswerItalic(?bool $answerItalic): static
    {
        $this->answerItalic = $answerItalic;
        return $this;
    }

    public function getAnswerAlign(): ?string
    {
        return $this->answerAlign;
    }

    public function setAnswerAlign(?string $answerAlign): static
    {
        $this->answerAlign = $answerAlign;
        return $this;
    }

    public function getAnswerMarginBottom(): ?int
    {
        return $this->answerMarginBottom;
    }

    public function setAnswerMarginBottom(?int $answerMarginBottom): static
    {
        $this->answerMarginBottom = $answerMarginBottom;
        return $this;
    }

    // Arrow getters/setters
    public function getArrowPosition(): ?string
    {
        return $this->arrowPosition;
    }

    public function setArrowPosition(?string $arrowPosition): static
    {
        $this->arrowPosition = $arrowPosition;
        return $this;
    }

    public function getArrowColor(): ?string
    {
        return $this->arrowColor;
    }

    public function setArrowColor(?string $arrowColor): static
    {
        $this->arrowColor = $arrowColor;
        return $this;
    }

    public function getArrowSize(): ?int
    {
        return $this->arrowSize;
    }

    public function setArrowSize(?int $arrowSize): static
    {
        $this->arrowSize = $arrowSize;
        return $this;
    }
}
