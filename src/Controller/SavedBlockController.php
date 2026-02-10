<?php

namespace App\Controller;

use App\Entity\SavedBlock;
use App\Entity\User;
use App\Repository\SavedBlockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/saved-blocks')]
class SavedBlockController extends AbstractController
{
    public function __construct(
        private SavedBlockRepository $repository,
        private EntityManagerInterface $entityManager,
    ) {}

    #[Route('', name: 'api_saved_blocks_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $blocks = $this->repository->findBy(['user' => $user], ['updatedAt' => 'DESC']);

        return $this->json($blocks, Response::HTTP_OK, [], ['groups' => ['saved_block:read']]);
    }

    #[Route('', name: 'api_saved_blocks_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['name']) || empty($data['blockType']) || empty($data['blockData'])) {
            return $this->json(['error' => 'Missing required fields: name, blockType, blockData'], Response::HTTP_BAD_REQUEST);
        }

        $savedBlock = new SavedBlock();
        $savedBlock->setUser($user);
        $savedBlock->setName($data['name']);
        $savedBlock->setBlockType($data['blockType']);
        $savedBlock->setBlockData($data['blockData']);

        $this->entityManager->persist($savedBlock);
        $this->entityManager->flush();

        return $this->json($savedBlock, Response::HTTP_CREATED, [], ['groups' => ['saved_block:read']]);
    }

    #[Route('/{id}', name: 'api_saved_blocks_update', methods: ['PUT'])]
    public function update(Request $request, SavedBlock $savedBlock): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($savedBlock->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $savedBlock->setName($data['name']);
        }

        $this->entityManager->flush();

        return $this->json($savedBlock, Response::HTTP_OK, [], ['groups' => ['saved_block:read']]);
    }

    #[Route('/{id}', name: 'api_saved_blocks_delete', methods: ['DELETE'])]
    public function delete(SavedBlock $savedBlock): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($savedBlock->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $this->entityManager->remove($savedBlock);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
