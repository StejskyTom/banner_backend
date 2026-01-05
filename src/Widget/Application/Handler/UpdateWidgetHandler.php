<?php
namespace App\Widget\Application\Handler;

use App\Entity\Widget;
use App\Lib\ViolationsTrait;
use App\Widget\Application\Action\CreateWidgetAction;
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
                $action->attachmentsLinks,
                $action->attachmentsAlts,
                $action->imageSize,
                $action->speed,
                $action->pauseOnHover,
                $action->gap,
                $action->settings
            );

            $this->logger->info('Widget byl úspěšně uložen', [
                'title' => $action->title,
            ]);

        } catch (WidgetNotFound $e) {
            $this->createFieldValidationFailedException(
                'Widget nebyl nalezen.',
                'id'
            );
        }

        return $widget;
    }


    #[AsMessageHandler]
    public function onWidgetCreate(CreateWidgetAction $action): Widget
    {
        try {
            $widget = $this->widgetService->createWidget($action);

            $this->logger->info('Widget byl úspěšně vytvořen', [
                'title' => $action->title,
            ]);

        } catch (WidgetNotFound $e) {
            $this->createFieldValidationFailedException(
                'Chyba při vytváření widgetu.',
                'title'
            );
        }

        return $widget;
    }
}
