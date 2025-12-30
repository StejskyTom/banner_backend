<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class ResetPasswordController extends AbstractController
{
    #[Route('/api/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return new JsonResponse(['message' => 'Email is required'], 400);
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            // Do not reveal if user exists
            return new JsonResponse(['message' => 'If the email exists, a reset link has been sent.']);
        }

        $token = Uuid::v4()->toRfc4122();
        $user->setResetPasswordToken($token);
        $user->setResetPasswordTokenExpiresAt(new \DateTime('+1 hour'));

        $entityManager->flush();

        try {
            $emailMessage = (new TemplatedEmail())
                ->from('podpora@visualy.cz')
                ->to($user->getEmail())
                ->subject('Obnovení hesla')
                ->htmlTemplate('emails/reset_password.html.twig')
                ->textTemplate('emails/reset_password.txt.twig')
                ->context([
                    'resetToken' => $token,
                    'userEmail' => $user->getEmail(),
                ]);

            $mailer->send($emailMessage);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }

        return new JsonResponse(['message' => 'If the email exists, a reset link has been sent.']);
    }

    #[Route('/api/reset-password', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $newPassword = $data['password'] ?? null;

        if (!$token || !$newPassword) {
            return new JsonResponse(['message' => 'Token and password are required'], 400);
        }

        $user = $userRepository->findOneBy(['resetPasswordToken' => $token]);

        if (!$user) {
            return new JsonResponse(['message' => 'Invalid or expired token'], 400);
        }

        if ($user->getResetPasswordTokenExpiresAt() < new \DateTime()) {
            return new JsonResponse(['message' => 'Token expired'], 400);
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $user->setResetPasswordToken(null);
        $user->setResetPasswordTokenExpiresAt(null);

        $entityManager->flush();

        return new JsonResponse(['message' => 'Password successfully reset']);
    }
}
