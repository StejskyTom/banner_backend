<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception\CustomTranslatableExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException as SymfonyUnauthorizedHttpException;
use Throwable;

class UnauthorizedHttpException extends SymfonyUnauthorizedHttpException implements CustomTranslatableExceptionInterface
{
    /** @param array<string, string> $headers */
    public function __construct(
        string $message = 'Přístup zamítnut',
        ?Throwable $previous = null,
        int $code = 401,
        array $headers = [],
    ) {
        parent::__construct('Bluetero', $message, $previous, $code, $headers);
    }
}
