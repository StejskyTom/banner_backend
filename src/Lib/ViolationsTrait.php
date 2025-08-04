<?php

namespace App\Lib;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

trait ViolationsTrait
{
    public function createFieldValidationFailedException(string $message, string $field): never
    {
        $list = new ConstraintViolationList();
        $list->add(new ConstraintViolation($message, $message, [], null, $field,null));

        $prev = new ValidationFailedException(null, $list);
        throw new UnprocessableEntityHttpException($message, $prev);
    }
}
