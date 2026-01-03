<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager
    ) {
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['username'] ?? $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $rememberMe = $data['rememberMe'] ?? false;

        if (!$email || !$password) {
            return $this->json(['message' => 'Missing credentials'], 401);
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['message' => 'Invalid credentials'], 401);
        }

        // Calculate expiration
        $payload = [];
        if ($rememberMe) {
            // 30 days
            $payload['exp'] = time() + (30 * 24 * 60 * 60);
        } else {
            // Default is handled by config (24h), but we can force it here too to be sure
            // or just leave empty to let config decide.
            // Let's rely on config for default, but if rememberMe is true, we override.
        }

        $token = $this->jwtManager->create($user, $payload);

        return $this->json([
            'token' => $token,
        ]);
    }
}
