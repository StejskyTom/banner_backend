<?php
namespace App\Widget\Infrastructure\DTO\Request;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class EditWidget
{
    #[NotBlank()]
    #[Email()]
    public string $title;
}
