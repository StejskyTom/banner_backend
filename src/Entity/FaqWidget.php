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

    public function __clone()
    {
        $this->id = null;
    }

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

    // Border settings
    #[ORM\Column(nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?bool $borderEnabled = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $borderColor = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?int $borderWidth = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?int $borderRadius = null;

    // Divider settings
    #[ORM\Column(nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?bool $dividerEnabled = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $dividerColor = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?int $dividerWidth = null; // in percent

    #[ORM\Column(nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?int $dividerHeight = null; // in px

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $dividerStyle = null; // solid, dashed, dotted

    #[ORM\Column(nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?int $dividerMargin = null; // vertical margin in px

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

    // Border getters/setters
    public function isBorderEnabled(): ?bool
    {
        return $this->borderEnabled;
    }

    public function setBorderEnabled(?bool $borderEnabled): static
    {
        $this->borderEnabled = $borderEnabled;
        return $this;
    }

    public function getBorderColor(): ?string
    {
        return $this->borderColor;
    }

    public function setBorderColor(?string $borderColor): static
    {
        $this->borderColor = $borderColor;
        return $this;
    }

    public function getBorderWidth(): ?int
    {
        return $this->borderWidth;
    }

    public function setBorderWidth(?int $borderWidth): static
    {
        $this->borderWidth = $borderWidth;
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

    // Divider methods
    public function isDividerEnabled(): ?bool
    {
        return $this->dividerEnabled;
    }

    public function setDividerEnabled(?bool $dividerEnabled): static
    {
        $this->dividerEnabled = $dividerEnabled;

        return $this;
    }

    public function getDividerColor(): ?string
    {
        return $this->dividerColor;
    }

    public function setDividerColor(?string $dividerColor): static
    {
        $this->dividerColor = $dividerColor;

        return $this;
    }

    public function getDividerWidth(): ?int
    {
        return $this->dividerWidth;
    }

    public function setDividerWidth(?int $dividerWidth): static
    {
        $this->dividerWidth = $dividerWidth;

        return $this;
    }

    public function getDividerHeight(): ?int
    {
        return $this->dividerHeight;
    }

    public function setDividerHeight(?int $dividerHeight): static
    {
        $this->dividerHeight = $dividerHeight;

        return $this;
    }

    public function getDividerStyle(): ?string
    {
        return $this->dividerStyle;
    }

    public function setDividerStyle(?string $dividerStyle): static
    {
        $this->dividerStyle = $dividerStyle;

        return $this;
    }

    public function getDividerMargin(): ?int
    {
        return $this->dividerMargin;
    }

    public function setDividerMargin(?int $dividerMargin): static
    {
        $this->dividerMargin = $dividerMargin;

        return $this;
    }

    // Title styling properties
    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $titleTag = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $titleColor = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $titleSize = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $titleFont = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?bool $titleBold = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?bool $titleItalic = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $titleAlign = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?int $titleMarginBottom = null;

    // Subtitle styling properties
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $subtitleText = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $subtitleTag = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $subtitleColor = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $subtitleSize = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $subtitleFont = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?bool $subtitleBold = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?bool $subtitleItalic = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?string $subtitleAlign = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['faq_widget:read', 'faq_widget:write'])]
    private ?int $subtitleMarginBottom = null;

    // Title Getters/Setters
    public function getTitleTag(): ?string
    {
        return $this->titleTag;
    }

    public function setTitleTag(?string $titleTag): static
    {
        $this->titleTag = $titleTag;
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

    public function getTitleSize(): ?string
    {
        return $this->titleSize;
    }

    public function setTitleSize(?string $titleSize): static
    {
        $this->titleSize = $titleSize;
        return $this;
    }

    public function getTitleFont(): ?string
    {
        return $this->titleFont;
    }

    public function setTitleFont(?string $titleFont): static
    {
        $this->titleFont = $titleFont;
        return $this;
    }

    public function isTitleBold(): ?bool
    {
        return $this->titleBold;
    }

    public function setTitleBold(?bool $titleBold): static
    {
        $this->titleBold = $titleBold;
        return $this;
    }

    public function isTitleItalic(): ?bool
    {
        return $this->titleItalic;
    }

    public function setTitleItalic(?bool $titleItalic): static
    {
        $this->titleItalic = $titleItalic;
        return $this;
    }

    public function getTitleAlign(): ?string
    {
        return $this->titleAlign;
    }

    public function setTitleAlign(?string $titleAlign): static
    {
        $this->titleAlign = $titleAlign;
        return $this;
    }

    public function getTitleMarginBottom(): ?int
    {
        return $this->titleMarginBottom;
    }

    public function setTitleMarginBottom(?int $titleMarginBottom): static
    {
        $this->titleMarginBottom = $titleMarginBottom;
        return $this;
    }

    // Subtitle Getters/Setters
    public function getSubtitleText(): ?string
    {
        return $this->subtitleText;
    }

    public function setSubtitleText(?string $subtitleText): static
    {
        $this->subtitleText = $subtitleText;
        return $this;
    }

    public function getSubtitleTag(): ?string
    {
        return $this->subtitleTag;
    }

    public function setSubtitleTag(?string $subtitleTag): static
    {
        $this->subtitleTag = $subtitleTag;
        return $this;
    }

    public function getSubtitleColor(): ?string
    {
        return $this->subtitleColor;
    }

    public function setSubtitleColor(?string $subtitleColor): static
    {
        $this->subtitleColor = $subtitleColor;
        return $this;
    }

    public function getSubtitleSize(): ?string
    {
        return $this->subtitleSize;
    }

    public function setSubtitleSize(?string $subtitleSize): static
    {
        $this->subtitleSize = $subtitleSize;
        return $this;
    }

    public function getSubtitleFont(): ?string
    {
        return $this->subtitleFont;
    }

    public function setSubtitleFont(?string $subtitleFont): static
    {
        $this->subtitleFont = $subtitleFont;
        return $this;
    }

    public function isSubtitleBold(): ?bool
    {
        return $this->subtitleBold;
    }

    public function setSubtitleBold(?bool $subtitleBold): static
    {
        $this->subtitleBold = $subtitleBold;
        return $this;
    }

    public function isSubtitleItalic(): ?bool
    {
        return $this->subtitleItalic;
    }

    public function setSubtitleItalic(?bool $subtitleItalic): static
    {
        $this->subtitleItalic = $subtitleItalic;
        return $this;
    }

    public function getSubtitleAlign(): ?string
    {
        return $this->subtitleAlign;
    }

    public function setSubtitleAlign(?string $subtitleAlign): static
    {
        $this->subtitleAlign = $subtitleAlign;
        return $this;
    }

    public function getSubtitleMarginBottom(): ?int
    {
        return $this->subtitleMarginBottom;
    }

    public function setSubtitleMarginBottom(?int $subtitleMarginBottom): static
    {
        $this->subtitleMarginBottom = $subtitleMarginBottom;
        return $this;
    }
}
