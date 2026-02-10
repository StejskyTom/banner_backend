<?php

namespace App\Controller;

use App\Entity\ArticleWidget;
use App\Entity\AuthorWidget;
use App\Entity\FaqWidget;
use App\Entity\HeurekaFeed;
use App\Entity\Widget;
use App\Repository\ArticleWidgetRepository;
use App\Repository\AuthorWidgetRepository;
use App\Repository\FaqWidgetRepository;
use App\Repository\HeurekaFeedRepository;
use App\Repository\WidgetRepository;
use App\Repository\WidgetStatRepository;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UserStatsController extends AbstractController
{
    public function __construct(
        private WidgetStatRepository $statRepo,
        private WidgetRepository $logoRepo,
        private HeurekaFeedRepository $heurekaRepo,
        private FaqWidgetRepository $faqRepo,
        private ArticleWidgetRepository $articleRepo,
        private AuthorWidgetRepository $authorRepo,
    ) {}

    #[Route('/api/user/stats', name: 'api_user_stats', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $today = new \DateTime('today');

        // Doctrine doesn't properly convert UUID entities to BINARY(16) in
        // setParameter — pass the raw binary ID instead.
        $userIdBinary = $user->getId()->toBinary();

        // 1. Logo Widgets (Carousel)
        $logoIds = $this->logoRepo->createQueryBuilder('w')
             ->select('w.id')
             ->where('w.user = :user')
             ->setParameter('user', $userIdBinary)
             ->getQuery()
             ->getSingleColumnResult();
        $logoViews = $this->statRepo->getViewsForWidgets($logoIds, $today);

        // 2. Heureka
        $heurekaIds = $this->heurekaRepo->createQueryBuilder('h')
             ->select('h.id')
             ->where('h.user = :user')
             ->setParameter('user', $userIdBinary)
             ->getQuery()
             ->getSingleColumnResult();
        $heurekaViews = $this->statRepo->getViewsForWidgets($heurekaIds, $today);

        // 3. FAQ
        $faqIds = $this->faqRepo->createQueryBuilder('f')
             ->select('f.id')
             ->where('f.user = :user')
             ->setParameter('user', $userIdBinary)
             ->getQuery()
             ->getSingleColumnResult();
        $faqViews = $this->statRepo->getViewsForWidgets($faqIds, $today);

        // 4. Article
        $articleIds = $this->articleRepo->createQueryBuilder('a')
             ->select('a.id')
             ->where('a.user = :user')
             ->setParameter('user', $userIdBinary)
             ->getQuery()
             ->getSingleColumnResult();
        $articleViews = $this->statRepo->getViewsForWidgets($articleIds, $today);

        // 5. Author
        $authorIds = $this->authorRepo->createQueryBuilder('a')
             ->select('a.id')
             ->where('a.user = :user')
             ->setParameter('user', $userIdBinary)
             ->getQuery()
             ->getSingleColumnResult();
        $authorViews = $this->statRepo->getViewsForWidgets($authorIds, $today);

        return new JsonResponse([
            'logo' => $logoViews,
            'heureka' => $heurekaViews,
            'faq' => $faqViews,
            'article' => $articleViews,
            'author' => $authorViews,
        ]);
    }
}
