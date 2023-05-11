<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use App\Service\VersioningService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class BookController extends AbstractController
{
    #[Route('/api/books', name: 'store_book', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Admin role only.')]
    public function store(Request $request, AuthorRepository $authorRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $book = $serializer->deserialize($request->getContent(), Book::class, 'json');

        $errors = $validator->validate($book);

        if ($errors->count() > 0) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $errors->get(0)->getMessage());
        }

        $book->setAuthor($authorRepository->find($request->toArray()['authorId'] ?? -1));
        $entityManager->persist($book);
        $entityManager->flush();

        $cache->invalidateTags(['index_books_cache']);

        $location = $urlGenerator->generate('show_book', ['book' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse(
            $serializer->serialize($book, 'json', SerializationContext::create()->setGroups(['getBooks'])),
            Response::HTTP_CREATED,
            ['location' => $location],
            true
        );
    }

    #[Route('/api/books', name: 'index_book', methods: ['GET'])]
    public function index(BookRepository $bookRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $cacheKey = 'index_book-' . $page . '-' . $limit;

        $books = $cache->get($cacheKey, function (ItemInterface $item) use ($bookRepository, $page, $limit, $serializer) {
            $item->tag('index_books_cache');
            return $serializer->serialize($bookRepository->findAllWithPagination($page, $limit), 'json', SerializationContext::create()->setGroups(['getBooks']));
        });

        return new JsonResponse(
            $books,
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/books/{book}', name: 'show_book', methods: ['GET'])]
    public function show(Book $book, SerializerInterface $serializer, VersioningService $versioningService): JsonResponse
    {
        $version = $versioningService->getVersion();
        $context = SerializationContext::create()->setGroups(['getBooks']);
        $context->setVersion($version);

        return new JsonResponse(
            $serializer->serialize($book, 'json', $context),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/books/{book}', name: 'update_book', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Admin role only.')]
    public function update(Book $book, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, AuthorRepository $authorRepository, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $updatedBook = $serializer->deserialize($request->getContent(), Book::class, 'json');

        $book->setTitle($updatedBook->getTitle());
        $book->setCoverText($updatedBook->getCoverText());


        $errors = $validator->validate($updatedBook);

        if ($errors->count() > 0) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $errors->get(0)->getMessage());
        }

        $book->setAuthor($authorRepository->find($request->toArray()['authorId'] ?? -1));

        $entityManager->persist($book);
        $entityManager->flush();

        $cache->invalidateTags(['index_books_cache']);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/books/{book}', name: 'destroy_book', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Admin role only.')]
    public function destroy(Book $book, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $entityManager->remove($book);
        $entityManager->flush();

        $cache->invalidateTags(['index_books_cache']);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
