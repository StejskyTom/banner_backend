<?php

namespace App\Controller;

use App\Repository\WidgetStatRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

class WidgetTrackingController extends AbstractController
{
    #[Route('/api/widget/track', name: 'api_widget_track', methods: ['POST'])]
    public function track(Request $request, WidgetStatRepository $repository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $widgetId = $data['widgetId'] ?? null;
        $widgetType = $data['widgetType'] ?? null;

        if (!$widgetId || !$widgetType) {
            return new JsonResponse(['error' => 'Missing widgetId or widgetType'], 400);
        }

        try {
            $uuid = Uuid::fromString($widgetId);
            $repository->trackView($uuid, $widgetType);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }

        return new JsonResponse(['status' => 'ok']);
    }
}
