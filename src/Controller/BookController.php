<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Faker;

class BookController extends AbstractController
{
    #[Route('/api/books', name: 'app_book', methods: ['GET'])]
    public function index(BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
        return new JsonResponse(
            $serializer->serialize($bookRepository->findAll(), 'json', ["groups" => "getBooks"]),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/books/{book}', name: 'app_book_details', methods: ['GET'])]
    public function show(Book $book, SerializerInterface $serializer): JsonResponse
    {
        return new JsonResponse(
            $serializer->serialize($book, 'json', ["groups" => "getBooks"]),
            Response::HTTP_OK,
            [],
            true
        );
    }
}
