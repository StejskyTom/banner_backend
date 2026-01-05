<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\Subscription;
use App\Repository\SubscriptionRepository;
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
    #[Route('', name: 'api_subscription_status', methods: ['GET'])]
    public function status(SubscriptionRepository $subscriptionRepository, \App\Repository\UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($this->getUser()->getId());
        if (!$user) {
             return $this->json(['error' => 'User not found'], 404);
        }
        
        // Find latest active or meaningful subscription
        // 1. Try to find the latest ACTIVE subscription
        $subscription = $subscriptionRepository->findOneBy(
            ['user' => $user, 'status' => 'active'],
            ['endDate' => 'DESC']
        );

        // 2. If no active found, find the latest one (history)
        if (!$subscription) {
            $subscription = $subscriptionRepository->findOneBy(
                ['user' => $user],
                ['endDate' => 'DESC']
            );
        }

        if (!$subscription) {
            return $this->json([
                'status' => 'inactive',
                'hasHistory' => false,
                'offer' => [
                    'name' => 'Měsíční předplatné',
                    'price' => 550,
                    'currency' => 'CZK',
                    'interval' => 'month'
                ],
                'billing' => [
                    'name' => $user->getBillingName(),
                    'ico' => $user->getBillingIco(),
                    'dic' => $user->getBillingDic(),
                    'street' => $user->getBillingStreet(),
                    'city' => $user->getBillingCity(),
                    'zip' => $user->getBillingZip(),
                    'country' => $user->getBillingCountry(),
                ],
                'invoices' => $user->getPayments()->map(fn(Payment $p) => [
                    'id' => $p->getId(),
                    'number' => $p->getInvoiceNumber(),
                    'amount' => $p->getAmount(),
                    'currency' => $p->getCurrency(),
                    'date' => $p->getCreatedAt()->format('c'),
                    'status' => $p->getStatus(),
                    'description' => $p->getDescription()
                ])->toArray()
            ]);
        }

        $now = new \DateTime();
        $isActive = $subscription->getStatus() === 'active' && $subscription->getEndDate() > $now;

        return $this->json([
            'status' => $isActive ? 'active' : 'inactive',
            'currentPeriodEnd' => $subscription->getEndDate()?->format('c'),
            'plan' => $subscription->getPlan(),
            'hasHistory' => true,
            'offer' => [
                'name' => 'Měsíční předplatné',
                'price' => 550,
                'currency' => 'CZK',
                'interval' => 'month'
            ],
            'billing' => [
                'name' => $user->getBillingName(),
                'ico' => $user->getBillingIco(),
                'dic' => $user->getBillingDic(),
                'street' => $user->getBillingStreet(),
                'city' => $user->getBillingCity(),
                'zip' => $user->getBillingZip(),
                'country' => $user->getBillingCountry(),
            ],
            'invoices' => $user->getPayments()->map(fn(Payment $p) => [
                'id' => $p->getId(),
                'number' => $p->getInvoiceNumber(),
                'amount' => $p->getAmount(),
                'currency' => $p->getCurrency(),
                'date' => $p->getCreatedAt()->format('c'),
                'status' => $p->getStatus(),
                'description' => $p->getDescription()
            ])->toArray()
        ]);
    }

    #[Route('/create', name: 'api_subscription_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, \App\Repository\UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($this->getUser()->getId());
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }
        
        $data = json_decode($request->getContent(), true);

        // Update Billing Details if provided
        if (isset($data['billing'])) {
            $user->setBillingName($data['billing']['name'] ?? $user->getBillingName());
            $user->setBillingIco($data['billing']['ico'] ?? $user->getBillingIco());
            $user->setBillingDic($data['billing']['dic'] ?? $user->getBillingDic());
            $user->setBillingStreet($data['billing']['street'] ?? $user->getBillingStreet());
            $user->setBillingCity($data['billing']['city'] ?? $user->getBillingCity());
            $user->setBillingZip($data['billing']['zip'] ?? $user->getBillingZip());
            $user->setBillingCountry($data['billing']['country'] ?? $user->getBillingCountry());
            $em->persist($user);
        }
        
        $price = 550.00;
        
        // 1. Create Payment Record (Simulated Paid)
        $payment = new Payment();
        $payment->setUser($user);
        $payment->setAmount($price);
        $payment->setCurrency('CZK');
        $payment->setStatus('paid'); // Simulated
        $payment->setDescription('Měsíční předplatné (Simulated)');
        
        // Invoice Logic
        $payment->setInvoiceNumber(date('Y') . mt_rand(10000, 99999)); // Simple random invoice ID
        $payment->setBillingSnapshot([
            'name' => $user->getBillingName(),
            'ico' => $user->getBillingIco(),
            'dic' => $user->getBillingDic(),
            'street' => $user->getBillingStreet(),
            'city' => $user->getBillingCity(),
            'zip' => $user->getBillingZip(),
            'country' => $user->getBillingCountry(),
        ]);

        $em->persist($payment);

        // 2. Create/Extend Subscription (Priority Active Check from before logic could be here but for simplicity we assume creation)
        $startDate = new \DateTime();
        $endDate = (clone $startDate)->modify('+1 month');

        $subscription = new Subscription();
        $subscription->setUser($user);
        $subscription->setPlan('monthly');
        $subscription->setStatus('active');
        $subscription->setStartDate($startDate);
        $subscription->setEndDate($endDate);
        $subscription->setNextBillingDate($endDate);
        
        $em->persist($subscription);
        $em->flush();

        return $this->json([
            'message' => 'Subscription activated successfully',
            'status' => 'active',
            'expiresAt' => $endDate->format('c')
        ]);
    }

    #[Route('/billing', name: 'api_subscription_billing_update', methods: ['POST'])]
    public function updateBilling(Request $request, EntityManagerInterface $em, \App\Repository\UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($this->getUser()->getId());
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }
        
        $data = json_decode($request->getContent(), true);

        if (isset($data['billing'])) {
            $user->setBillingName($data['billing']['name'] ?? $user->getBillingName());
            $user->setBillingIco($data['billing']['ico'] ?? $user->getBillingIco());
            $user->setBillingDic($data['billing']['dic'] ?? $user->getBillingDic());
            $user->setBillingStreet($data['billing']['street'] ?? $user->getBillingStreet());
            $user->setBillingCity($data['billing']['city'] ?? $user->getBillingCity());
            $user->setBillingZip($data['billing']['zip'] ?? $user->getBillingZip());
            $user->setBillingCountry($data['billing']['country'] ?? $user->getBillingCountry());
            
            $em->persist($user);
            $em->flush();
            
            return $this->json(['message' => 'Billing details updated']);
        }

        return $this->json(['error' => 'No billing data provided'], 400);
    }

    #[Route('/invoice/{id}', name: 'api_subscription_invoice_download', methods: ['GET'])]
    public function downloadInvoice(Payment $payment): \Symfony\Component\HttpFoundation\Response
    {
        if ($payment->getUser() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Invoice not found'], 404);
        }

        $billing = $payment->getBillingSnapshot();
        $user = $payment->getUser();
        
        // Simple HTML Invoice Template
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Faktura ' . $payment->getInvoiceNumber() . '</title>
            <style>
                body { font-family: "DejaVu Sans", sans-serif; max-width: 800px; mx-auto; padding: 40px; font-size: 14px; }
                .header { width: 100%; margin-bottom: 50px; }
                .title { font-size: 24px; font-weight: bold; }
                .meta { float: right; text-align: right; }
                .details { width: 100%; margin-bottom: 50px; clear: both; }
                .col { width: 48%; float: left; }
                .col-right { float: right; }
                .table { width: 100%; border-collapse: collapse; margin-bottom: 30px; clear: both; }
                .table th, .table td { border-bottom: 1px solid #ddd; padding: 10px; text-align: left; }
                .total { text-align: right; font-size: 20px; font-weight: bold; margin-top: 20px; }
                .clear { clear: both; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="meta">
                    <div>Datum vystavení: ' . $payment->getCreatedAt()->format('d.m.Y') . '</div>
                    <div>Datum zdan. plnění: ' . $payment->getCreatedAt()->format('d.m.Y') . '</div>
                </div>
                <div>
                    <div class="title">FAKTURA - DAŇOVÝ DOKLAD</div>
                    <div style="margin-top: 10px">Číslo: ' . $payment->getInvoiceNumber() . '</div>
                </div>
                <div class="clear"></div>
            </div>

            <div class="details">
                <div class="col">
                    <strong>Dodavatel:</strong><br>
                    Visualy.cz<br>
                    Testovací ulice 123<br>
                    100 00 Praha<br>
                    IČ: 12345678<br>
                    DIČ: CZ12345678
                </div>
                <div class="col col-right">
                    <strong>Odběratel:</strong><br>
                    ' . ($billing['name'] ?? $user->getEmail()) . '<br>
                    ' . ($billing['street'] ?? '') . '<br>
                    ' . ($billing['city'] ?? '') . ' ' . ($billing['zip'] ?? '') . '<br>
                    ' . ($billing['country'] ?? '') . '<br><br>
                    IČ: ' . ($billing['ico'] ?? '-') . '<br>
                    DIČ: ' . ($billing['dic'] ?? '-') . '
                </div>
                <div class="clear"></div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Položka</th>
                        <th>Množství</th>
                        <th>Cena</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>' . $payment->getDescription() . '</td>
                        <td>1</td>
                        <td>' . number_format($payment->getAmount(), 2, ',', ' ') . ' ' . $payment->getCurrency() . '</td>
                    </tr>
                </tbody>
            </table>

            <div class="total">
                Celkem k úhradě: ' . number_format($payment->getAmount(), 2, ',', ' ') . ' ' . $payment->getCurrency() . '
            </div>
        </body>
        </html>';

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new \Symfony\Component\HttpFoundation\Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="faktura-' . $payment->getInvoiceNumber() . '.pdf"'
        ]);
    }
}
