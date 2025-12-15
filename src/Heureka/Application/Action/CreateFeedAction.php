<?php

declare(strict_types=1);

namespace App\Heureka\Application\Action;

final readonly class CreateFeedAction
{
    public function __construct(
        public string $url,
        public ?string $name = null,
    ) {}
}
