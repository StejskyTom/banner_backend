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
    public function updateWidget(string $id, ?string $title = null, array $attachmentsOrder = [], ?array $attachmentsLinks = null, ?array $attachmentsAlts = null, ?int $imageSize = null, ?int $speed = null, ?bool $pauseOnHover = null, ?int $gap = null, ?array $settings = null): Widget
    {
        $widget = $this->widgetRepository->findOneBy(['id' => $id]);

        if (!$widget) {
            throw new WidgetNotFound('Widget neexistuje');
        }

        $widget->setTitle($title);
        $widget->setImageSize($imageSize);
        $widget->setSpeed($speed);
        if ($pauseOnHover !== null) {
            $widget->setPauseOnHover($pauseOnHover);
        }
        $widget->setGap($gap);
        $widget->setSettings($settings);

        foreach ($attachmentsOrder as $pos => $attId) {
            $att = $this->attachmentRepository->find($attId);
            if ($att && $att->getWidget() === $widget) {
                $att->setPosition($pos);

                // Update link if provided
                if ($attachmentsLinks && isset($attachmentsLinks[$attId])) {
                    $att->setLink($attachmentsLinks[$attId]);
                }
                
                // Update alt if provided
                if ($attachmentsAlts && isset($attachmentsAlts[$attId])) {
                    $att->setAlt($attachmentsAlts[$attId]);
                }
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

        $widget = new Widget(
            $user,
            $action->title,
        );
        $widget->setPauseOnHover($action->pauseOnHover);
        $widget->setGap($action->gap);

        return $widget;
    }
}
