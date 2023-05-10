<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BookController extends AbstractController
{
    #[Route('/api/books', name: 'store_book', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Admin role only.')]
    public function store(Request $request, AuthorRepository $authorRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator): JsonResponse
    {
        $book = $serializer->deserialize($request->getContent(), Book::class, 'json');

        $errors = $validator->validate($book);

        if ($errors->count() > 0) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $errors->get(0)->getMessage());
        }

        $book->setAuthor($authorRepository->find($request->toArray()['authorId'] ?? -1));
        $entityManager->persist($book);
        $entityManager->flush();

        $location = $urlGenerator->generate('show_book', ['book' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse(
            $serializer->serialize($book, 'json', ['groups' => 'getBooks']),
            Response::HTTP_CREATED,
            ['location' => $location],
            true
        );
    }

    #[Route('/api/books', name: 'index_book', methods: ['GET'])]
    public function index(BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
        return new JsonResponse(
            $serializer->serialize($bookRepository->findAll(), 'json', ['groups' => 'getBooks']),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/books/{book}', name: 'show_book', methods: ['GET'])]
    public function show(Book $book, SerializerInterface $serializer): JsonResponse
    {
        return new JsonResponse(
            $serializer->serialize($book, 'json', ['groups' => 'getBooks']),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/books/{book}', name: 'update_book', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Admin role only.')]
    public function update(Book $book, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, AuthorRepository $authorRepository, ValidatorInterface $validator): JsonResponse
    {
        $updatedBook = $serializer->deserialize(
            $request->getContent(),
            Book::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $book]
        );

        $errors = $validator->validate($updatedBook);

        if ($errors->count() > 0) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $errors->get(0)->getMessage());
        }

        $book->setAuthor($authorRepository->find($request->toArray()['authorId'] ?? -1));

        $entityManager->persist($updatedBook);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/books/{book}', name: 'destroy_book', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Admin role only.')]
    public function destroy(Book $book, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($book);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
