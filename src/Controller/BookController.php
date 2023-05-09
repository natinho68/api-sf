<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

class BookController extends AbstractController
{
    #[Route('/api/books', name: 'store_book', methods: ['POST'])]
    public function store(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $book = $serializer->deserialize($request->getContent(), Book::class, 'json');
        $entityManager->persist($book);
        $entityManager->flush();


        $location = $urlGenerator->generate('show_book', ['book' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse(
            $serializer->serialize($book, 'json', ["groups" => "getBooks"]),
            Response::HTTP_CREATED,
            ["location" => $location],
            true
        );
    }

    #[Route('/api/books', name: 'index_book', methods: ['GET'])]
    public function index(BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
        return new JsonResponse(
            $serializer->serialize($bookRepository->findAll(), 'json', ["groups" => "getBooks"]),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/books/{book}', name: 'show_book', methods: ['GET'])]
    public function show(Book $book, SerializerInterface $serializer): JsonResponse
    {
        return new JsonResponse(
            $serializer->serialize($book, 'json', ["groups" => "getBooks"]),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/books/{book}', name: 'destroy_book', methods: ['DELETE'])]
    public function destroy(Book $book, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($book);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
