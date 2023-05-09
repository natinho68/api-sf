<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class AuthorController extends AbstractController
{
    #[Route('/api/authors', name: 'app_author')]
    public function index(AuthorRepository $authorRepository, SerializerInterface $serializer): JsonResponse
    {
        return new JsonResponse(
            $serializer->serialize($authorRepository->findAll(), 'json', ['groups' => 'getAuthors']),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/authors/{author}', name: 'app_author_details')]
    public function show(Author $author, SerializerInterface $serializer): JsonResponse
    {
        return new JsonResponse(
            $serializer->serialize($author, 'json', ['groups' => 'getAuthors']),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/authors/{author}', name: 'app_author_details')]
    public function destroy(Author $author, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($author);
        $entityManager->flush();
        return new JsonResponse(
            null,
            Response::HTTP_NO_CONTENT,
        );
    }
}
