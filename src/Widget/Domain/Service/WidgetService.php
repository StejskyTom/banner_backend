<?php
namespace App\Widget\Domain\Service;

use App\Entity\User;
use App\Entity\Widget;
use App\Repository\AttachmentRepository;
use App\Repository\WidgetRepository;
use App\User\Domain\Exception\InvalidEmailException;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\Widget\Application\Action\CreateWidgetAction;
use App\Widget\Domain\Exception\WidgetNotFound;
use Symfony\Bundle\SecurityBundle\Security;

class WidgetService
{
    public function __construct(
        private WidgetRepository $widgetRepository,
        private AttachmentRepository $attachmentRepository,
        private Security $security,
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

    public function createWidget(CreateWidgetAction $action): Widget
    {
        if (!$this->security->getUser()) {
            throw new UserAlreadyExistsException();
        }

        /** @var User $user */
        $user = $this->security->getUser();

        return new Widget(
            $user,
            $action->title,
        );
    }
}
