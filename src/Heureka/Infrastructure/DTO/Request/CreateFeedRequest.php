<?php

namespace App\Heureka\Infrastructure\DTO\Request;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

class CreateFeedRequest
{
    #[NotBlank(message: 'URL feedu je povinná')]
    #[Url(message: 'URL feedu musí být platná')]
    public string $url;

    public ?string $name = null;
}
