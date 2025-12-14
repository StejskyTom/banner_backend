<?php

declare(strict_types=1);

namespace App\Widget\Application\Action;

use App\Entity\Widget;

final readonly class UpdateWidgetAction
{
    public function __construct(
        public string $id,
        public string $title,
        public array $attachmentsOrder,
        public ?array $attachmentsLinks = null,
        public ?int $imageSize = null,
        public ?int $speed = null,
    ) {
    }
}
