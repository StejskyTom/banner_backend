<?php

declare(strict_types=1);

namespace App\Heureka\Application\Action;

final readonly class SyncFeedAction
{
    public function __construct(
        public string $feedId,
    ) {}
}
