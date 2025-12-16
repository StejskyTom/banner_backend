<?php

namespace App\Heureka\Application\Controller;

use App\Entity\HeurekaFeed;
use App\Repository\HeurekaFeedRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ProductEmbedController extends AbstractController
{
    #[Route('/api/heureka/feed/{feedId}/embed.js', name: 'heureka_product_embed')]
    public function generateProductEmbedJS(
        string $feedId,
        HeurekaFeedRepository $feedRepository,
        ProductRepository $productRepository,
        SerializerInterface $serializer
    ): Response {
        $feed = $feedRepository->find($feedId);

        if (!$feed) {
            return new Response('// Feed not found', 404, ['Content-Type' => 'application/javascript']);
        }

        $products = $productRepository->findSelectedByFeed($feed);

        $productsJson = $serializer->serialize(
            $products,
            'json',
            [
                'groups' => ['widget:embed'],
                'json_encode_options' => JSON_UNESCAPED_SLASHES
            ]
        );

        $jsTemplate = $this->renderView('embed-products.js.twig', [
            'feedId' => $feedId,
            'feedName' => $feed->getName(),
            'products' => $productsJson,
            'layout' => $feed->getLayout(),
            'layoutOptions' => $feed->getLayoutOptions(),
        ]);

        $response = new Response($jsTemplate);
        $response->headers->set('Content-Type', 'application/javascript');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Cache-Control', 'public, max-age=300');

        return $response;
    }
}
