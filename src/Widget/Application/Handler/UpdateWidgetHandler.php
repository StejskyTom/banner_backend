<?php
namespace App\Widget\Application\Handler;

use App\Entity\Widget;
use App\Lib\ViolationsTrait;
use App\Widget\Application\Action\UpdateWidgetAction;
use App\Widget\Domain\Exception\WidgetNotFound;
use App\Widget\Domain\Service\WidgetService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

readonly class UpdateWidgetHandler
{
    use ViolationsTrait;

    public function __construct(
        private WidgetService          $widgetService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface        $logger
    ) {}

    #[AsMessageHandler]
    public function onWidgetUpdate(UpdateWidgetAction $action): Widget
    {
        try {
            $widget = $this->widgetService->updateWidget(
                $action->id,
                $action->title,
                $action->attachmentsOrder,
                $action->imageSize,
                $action->speed
            );

            $this->logger->info('Widget byl úspěšně uložen', [
                'title' => $action->title,
            ]);

        } catch (WidgetNotFound $e) {
            $this->createFieldValidationFailedException(
                'Uživatel s tímto emailem již existuje.',
                'email'
            );
        }

        return $widget;
    }
}
