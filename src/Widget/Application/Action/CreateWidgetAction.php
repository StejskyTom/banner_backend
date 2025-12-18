<?php

declare(strict_types=1);

namespace App\Widget\Application\Action;

final readonly class CreateWidgetAction
{
    public function __construct(
        public string $title = '',
        public array $attachmentsOrder = [],
        public ?int $imageSize = null,
        public ?int $speed = null,
        public bool $pauseOnHover = false,
        public ?int $gap = null,
    ) {
    }
}
