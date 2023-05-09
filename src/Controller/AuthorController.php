<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class AuthorController extends AbstractController
{
    #[Route('/api/authors', name: 'store_author', methods: ['POST'])]
    public function store(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $author = $serializer->deserialize($request->getContent(), Author::class, 'json');

        $entityManager->persist($author);
        $entityManager->flush();

        $location = $urlGenerator->generate('show_author', ['author' => $author->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse(
            null,
            Response::HTTP_NO_CONTENT,
            ['location' => $location]
        );
    }

    #[Route('/api/authors', name: 'index_author', methods: ['GET'])]
    public function index(AuthorRepository $authorRepository, SerializerInterface $serializer): JsonResponse
    {
        return new JsonResponse(
            $serializer->serialize($authorRepository->findAll(), 'json', ['groups' => 'getAuthors']),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/authors/{author}', name: 'show_author', methods: ['GET'])]
    public function show(Author $author, SerializerInterface $serializer): JsonResponse
    {
        return new JsonResponse(
            $serializer->serialize($author, 'json', ['groups' => 'getAuthors']),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/authors/{author}', name: 'update_author', methods: ['PUT'])]
    public function update(Author $author, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager)
    {
        $updatedAuthor = $serializer->deserialize($request->getContent(), Author::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $author]);

        $entityManager->persist($updatedAuthor);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/authors/{author}', name: 'destroy_author', methods: ['DELETE'])]
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
