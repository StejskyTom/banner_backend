<?php

declare(strict_types=1);

namespace App\Lib;

use App\Exception\CustomTranslatableExceptionInterface;
use LogicException;
use Override;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ProblemNormalizer as SymfonyProblemNormalizer;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * @phpstan-type ViolationArray array{'propertyPath': string, 'title': string, 'template': string, 'parameters': array<string, string>, 'type': string}
 * @phpstan-type NormalizedArray array{'title': string, 'status': int, 'detail': string, 'violations': array<array<ViolationArray>>, 'type'?: string}
 */
#[AsTaggedItem('serializer.normalizer')]
final class ProblemNormalizer extends SymfonyProblemNormalizer implements NormalizerInterface
{
    /** @param array{} $defaultContext */
    public function __construct(
        bool $debug = false,
        array $defaultContext = [],
        ?TranslatorInterface $translator = null,
    ) {
        parent::__construct($debug, $defaultContext, $translator);
    }

    #[Override]
    public function getSupportedTypes(?string $format): array
    {
        return [
            FlattenException::class => true,
        ];
    }

    /**
     * Normalizes an object into an array of scalars, arrays, or other objects.
     *
     * @param FlattenException               $object  the object to normalize
     * @param string|null                    $format  the format of the normalization
     * @param array{"exception"?: Throwable} $context options that influence the normalization process
     *
     * @return array<string, mixed> the normalized data array
     *
     * @throws LogicException     if required exception metadata is not set or improperly configured
     * @throws ExceptionInterface
     */
    #[Override]
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        if (HandlerFailedException::class === $object->getClass()) {
            $exc = $context['exception'] ?? throw new LogicException('Exception must be set in context');
            $exc = $exc->getPrevious();
            if ($exc instanceof HttpExceptionInterface) {
                $context['exception'] = $exc;
                $orig = $object;
                $object = $object->getPrevious()
                    ?? throw new LogicException('Previous exception of expected HttpExceptionInterface not found');
                $orig->setStatusText($object->getStatusText());
                $orig->setStatusCode($object->getStatusCode());
            }
        }
        /** @var NormalizedArray $result */
        $result = parent::normalize($object, $format, $context);
        unset($result['type']);

        if (is_subclass_of($object->getClass(), CustomTranslatableExceptionInterface::class)) {
            $result['detail'] = $object->getMessage();
        }

        if (is_subclass_of($object->getClass(), AccessDeniedHttpException::class)) {
            $result['detail'] = $object->getMessage();
        }

        return $result;
    }
}
