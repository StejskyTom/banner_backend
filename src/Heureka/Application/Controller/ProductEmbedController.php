<?php

namespace App\Heureka\Application\Controller;

use App\Entity\HeurekaFeed;
use App\Repository\HeurekaFeedRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ProductEmbedController extends AbstractController
{
    #[Route('/api/heureka/feed/{feedId}/embed.js', name: 'heureka_product_embed')]
    public function generateProductEmbedJS(
        Request $request,
        string $feedId,
        HeurekaFeedRepository $feedRepository,
        ProductRepository $productRepository,
        SerializerInterface $serializer,
        CacheInterface $cache
    ): Response {
        $feed = $feedRepository->find($feedId);

        if (!$feed) {
            return new Response('// Feed not found', 404, ['Content-Type' => 'application/javascript']);
        }

        // Generate ETag based on feed config and last sync time
        $etag = md5(serialize([
            $feedId,
            $feed->getLayout(),
            $feed->getLayoutOptions(),
            $feed->getLastSyncedAt()?->getTimestamp(),
            $feed->getProductCount()
        ]));

        $response = new Response();
        $response->setEtag($etag);
        $response->setPublic();

        // Check if the response has not been modified
        if ($response->isNotModified($request)) {
            return $response;
        }

        $content = $cache->get('heureka_product_embed_' . $feedId, function (ItemInterface $item) use ($feedId, $feed, $productRepository, $serializer) {
            $item->expiresAfter(3600 * 24); // Server cache for 24 hours

            $products = $productRepository->findSelectedByFeed($feed);

            $productsJson = $serializer->serialize(
                $products,
                'json',
                [
                    'groups' => ['widget:embed'],
                    'json_encode_options' => JSON_UNESCAPED_SLASHES
                ]
            );

            return $this->renderView('embed-products.js.twig', [
                'feedId' => $feedId,
                'feedName' => $feed->getName(),
                'products' => $productsJson,
                'layout' => $feed->getLayout(),
                'layoutOptions' => $feed->getLayoutOptions(),
            ]);
        });

        $response->setContent($content);
        $response->headers->set('Content-Type', 'application/javascript');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        // Force browser to revalidate with server every time
        $response->headers->set('Cache-Control', 'public, no-cache');

        return $response;
    }
}
