<?php
namespace App\Widget\Domain\Service;

use App\Entity\Widget;
use App\Repository\AttachmentRepository;
use App\Repository\WidgetRepository;
use App\User\Domain\Exception\InvalidEmailException;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\Widget\Domain\Exception\WidgetNotFound;

class WidgetService
{
    public function __construct(
        private WidgetRepository $widgetRepository,
        private AttachmentRepository $attachmentRepository,
    ) {}

    /**
     * @throws WidgetNotFound
     */
    public function updateWidget(string $id, ?string $title = null, array $attachmentsOrder = [], ?int $imageSize = null, ?int $speed = null): Widget
    {
        $widget = $this->widgetRepository->findOneBy(['id' => $id]);

        if (!$widget) {
            throw new WidgetNotFound('Widget neexistuje');
        }

        $widget->setTitle($title);
        $widget->setImageSize($imageSize);
        $widget->setSpeed($speed);
        foreach ($attachmentsOrder as $pos => $attId) {
            $att = $this->attachmentRepository->find($attId);
            if ($att && $att->getWidget() === $widget) {
                $att->setPosition($pos);
            }
        }


        return $widget;
    }
}
