<?php

declare(strict_types=1);

namespace App\Widget\Application\Action;

use App\Entity\Widget;

final readonly class UpdateWidgetAction
{
    public function __construct(
        public string $id,
        public string $title,
    ) {
    }
}
