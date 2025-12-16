<?php

declare(strict_types=1);

namespace App\Heureka\Application\Action;

final readonly class UpdateFeedAction
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $url = null,
        public ?string $layout = null,
        public ?array $layoutOptions = null,
    ) {}
}
