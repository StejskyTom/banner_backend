<?php

namespace App\Controller;

use App\Entity\ArticleWidget;
use App\Repository\ArticleWidgetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArticleEmbedController extends AbstractController
{
    #[Route('/api/article-widgets/{id}/embed.js', name: 'api_article_widgets_embed')]
    public function embed(ArticleWidget $widget): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/javascript');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');

        $content = $this->renderView('article-embed.js.twig', [
            'widget' => $widget,
            'content' => $widget->getContent(),
        ]);

        $response->setContent($content);

        return $response;
    }
}
