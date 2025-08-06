<?php

declare(strict_types=1);

namespace App\Lib;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

final class KernelExceptionListener
{
    #[AsEventListener(ExceptionEvent::class)]
    public function onException(ExceptionEvent $event): void
    {
        $th = $event->getThrowable();
        $isHttpHandlerFailed = $th instanceof HandlerFailedException;
        $previous = $th->getPrevious();
        $isHttpHandlerFailed = $isHttpHandlerFailed && $previous instanceof HttpExceptionInterface;
        if ($isHttpHandlerFailed) {
            $event->setThrowable($previous);
        }
    }
}
