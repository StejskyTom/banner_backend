<?php

namespace App\Heureka\Infrastructure\DTO\Request;

use Symfony\Component\Validator\Constraints\NotBlank;

class UpdateProductRequest
{
    #[NotBlank(message: 'Název produktu nesmí být prázdný', allowNull: true)]
    public ?string $productName = null;

    public ?string $description = null;
}
