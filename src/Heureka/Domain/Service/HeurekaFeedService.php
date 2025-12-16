<?php

namespace App\Heureka\Domain\Service;

use App\Entity\HeurekaFeed;
use App\Entity\User;
use App\Heureka\Application\Action\CreateFeedAction;
use App\Heureka\Application\Action\UpdateFeedAction;
use App\Heureka\Domain\Exception\FeedNotFoundException;
use App\Repository\HeurekaFeedRepository;
use Symfony\Bundle\SecurityBundle\Security;

class HeurekaFeedService
{
    public function __construct(
        private HeurekaFeedRepository $feedRepository,
        private Security $security,
    ) {}

    public function createFeed(CreateFeedAction $action): HeurekaFeed
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user) {
            throw new \RuntimeException('Uživatel není přihlášen');
        }

        return new HeurekaFeed(
            $user,
            $action->name ?? 'Nový feed',
            $action->url
        );
    }

    /**
     * @throws FeedNotFoundException
     */
    public function updateFeed(UpdateFeedAction $action): HeurekaFeed
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $feed = $this->feedRepository->findOneByUserAndId($user, $action->id);

        if (!$feed) {
            throw new FeedNotFoundException('Feed nenalezen');
        }

        if ($action->name !== null) {
            $feed->setName($action->name);
        }

        if ($action->url !== null) {
            $feed->setUrl($action->url);
        }

        if ($action->layout !== null) {
            $feed->setLayout($action->layout);
        }

        if ($action->layoutOptions !== null) {
            $feed->setLayoutOptions($action->layoutOptions);
        }

        return $feed;
    }
}
