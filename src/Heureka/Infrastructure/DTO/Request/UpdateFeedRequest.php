<?php

namespace App\Heureka\Infrastructure\DTO\Request;

use Symfony\Component\Validator\Constraints\Url;

class UpdateFeedRequest
{
    #[Url(message: 'URL feedu musí být platná')]
    public ?string $url = null;

    public ?string $name = null;

    public ?string $layout = null;

    public ?array $layoutOptions = null;
}
