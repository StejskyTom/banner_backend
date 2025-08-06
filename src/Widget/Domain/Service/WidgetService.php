<?php
namespace App\Widget\Domain\Service;

use App\Entity\Widget;
use App\Repository\WidgetRepository;
use App\User\Domain\Exception\InvalidEmailException;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\Widget\Domain\Exception\WidgetNotFound;

class WidgetService
{
    public function __construct(
        private WidgetRepository $widgetRepository,
    ) {}

    /**
     * @throws WidgetNotFound
     */
    public function updateWidget(string $id, ?string $title = null): Widget
    {
        $widget = $this->widgetRepository->findOneBy(['id' => $id]);

        if (!$widget) {
            throw new WidgetNotFound('Widget neexistuje');
        }

        $widget->setTitle($title);

        return $widget;
    }
}
