<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Google\Client;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/auth')]
class GoogleAuthController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private JWTTokenManagerInterface $jwtManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('/google', name: 'api_auth_google', methods: ['POST'])]
    public function googleAuth(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $idToken = $data['id_token'] ?? null;

        if (!$idToken) {
            return $this->json(['error' => 'ID token is missing'], 400);
        }

        try {
            $client = new Client(['client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? null]);
            $payload = $client->verifyIdToken($idToken);

            if (!$payload) {
                return $this->json(['error' => 'Invalid ID token'], 401);
            }

            $googleId = $payload['sub'];
            $email = $payload['email'];
            $name = $payload['name'] ?? null;
            $picture = $payload['picture'] ?? null;

            // Find user by Google ID or Email
            $user = $this->userRepository->findOneBy(['googleId' => $googleId]);

            if (!$user) {
                $user = $this->userRepository->findOneBy(['email' => $email]);

                if ($user) {
                    // Link existing user
                    $user->setGoogleId($googleId);
                    if ($picture && !$user->getAvatarUrl()) {
                        $user->setAvatarUrl($picture);
                    }
                } else {
                    // Create new user
                    $user = new User();
                    $user->setEmail($email);
                    $user->setGoogleId($googleId);
                    $user->setAvatarUrl($picture);
                    $user->setRoles(['ROLE_USER']);
                    // Set a random password as it won't be used
                    $user->setPassword(
                        $this->passwordHasher->hashPassword(
                            $user,
                            bin2hex(random_bytes(16))
                        )
                    );

                    $this->entityManager->persist($user);

                    // Create 14-day Free Trial
                    $subscription = new \App\Entity\Subscription();
                    $subscription->setUser($user);
                    $subscription->setPlan('trial');
                    $subscription->setStatus('active');
                    $subscription->setStartDate(new \DateTime());
                    $subscription->setEndDate((new \DateTime())->modify('+14 days'));
                    
                    $this->entityManager->persist($subscription);
                }

                $this->entityManager->flush();
            }

            // Generate JWT
            $token = $this->jwtManager->create($user);

            return $this->json([
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'avatarUrl' => $user->getAvatarUrl()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Authentication failed: ' . $e->getMessage()], 500);
        }
    }
}
