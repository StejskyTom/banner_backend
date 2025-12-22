<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/subscription')]
#[IsGranted('ROLE_USER')]
class SubscriptionController extends AbstractController
{
    #[Route('/status', name: 'api_subscription_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $activeSubscription = null;
        foreach ($user->getSubscriptions() as $sub) {
            if ($sub->getStatus() === 'active' && $sub->getEndDate() > new \DateTime()) {
                $activeSubscription = $sub;
                break;
            }
        }

        if (!$activeSubscription) {
            return $this->json([
                'active' => false,
                'message' => 'No active subscription found.'
            ]);
        }

        return $this->json([
            'active' => true,
            'plan' => $activeSubscription->getPlan(),
            'endDate' => $activeSubscription->getEndDate()->format('Y-m-d H:i:s'),
            'price' => $activeSubscription->getPrice(),
            'status' => $activeSubscription->getStatus(),
        ]);
    }

    #[Route('/subscribe', name: 'api_subscription_subscribe', methods: ['POST'])]
    public function subscribe(EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Check if already has active subscription
        if ($user->hasActiveSubscription()) {
             return $this->json([
                'success' => false,
                'message' => 'User already has an active subscription.'
            ], 400);
        }

        // Create dummy subscription
        $subscription = new Subscription();
        $subscription->setUser($user);
        $subscription->setStatus('active');
        $subscription->setPlan('monthly_500');
        $subscription->setPrice(500.00);
        $subscription->setStartDate(new \DateTime());
        $subscription->setEndDate((new \DateTime())->modify('+1 month'));
        $subscription->setPaymentGateway('dummy');
        $subscription->setPaymentId('dummy_' . uniqid());
        $subscription->setCreatedAt(new \DateTimeImmutable());
        $subscription->setUpdatedAt(new \DateTime());

        $em->persist($subscription);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Subscription activated successfully.',
            'subscription' => [
                'plan' => $subscription->getPlan(),
                'endDate' => $subscription->getEndDate()->format('Y-m-d H:i:s'),
            ]
        ]);
    }
}
