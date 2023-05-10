<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
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

class AuthorController extends AbstractController
{
    #[Route('/api/authors', name: 'store_author', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Admin role only.')]
    public function store(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $author = $serializer->deserialize($request->getContent(), Author::class, 'json');

        $errors = $validator->validate($author);

        if ($errors->count() > 0) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $errors->get(0)->getMessage());
        }

        $entityManager->persist($author);
        $entityManager->flush();
        $cache->invalidateTags(['index_authors_cache']);

        $location = $urlGenerator->generate('show_author', ['author' => $author->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse(
            null,
            Response::HTTP_NO_CONTENT,
            ['location' => $location]
        );
    }

    #[Route('/api/authors', name: 'index_author', methods: ['GET'])]
    public function index(AuthorRepository $authorRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);
        $cacheKey = 'index_authors-' . $page . '-' . $limit;

        $authors = $cache->get($cacheKey, function (ItemInterface $item) use ($authorRepository, $page, $limit, $serializer) {
            $item->tag(['index_authors_cache']);

            return $serializer->serialize($authorRepository->findAllWithPagination($page, $limit), 'json', SerializationContext::create()->setGroups(['getAuthors']));
        });

        return new JsonResponse(
            $authors,
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/authors/{author}', name: 'show_author', methods: ['GET'])]
    public function show(Author $author, SerializerInterface $serializer): JsonResponse
    {
        return new JsonResponse(
            $serializer->serialize($author, 'json', SerializationContext::create()->setGroups(['getAuthors'])),
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/api/authors/{author}', name: 'update_author', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Admin role only.')]
    public function update(Author $author, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator, TagAwareCacheInterface $cache)
    {
        $updatedAuthor = $serializer->deserialize($request->getContent(), Author::class, 'json');
        $author->setLastName($updatedAuthor->getLastName());
        $author->setFirstName($updatedAuthor->getFirstName());

        $errors = $validator->validate($updatedAuthor);

        if ($errors->count() > 0) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $errors->get(0)->getMessage());
        }

        $entityManager->persist($author);
        $entityManager->flush();
        $cache->invalidateTags(['index_authors_cache']);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/authors/{author}', name: 'destroy_author', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Admin role only.')]
    public function destroy(Author $author, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $entityManager->remove($author);
        $entityManager->flush();
        $cache->invalidateTags(['index_authors_cache']);

        return new JsonResponse(
            null,
            Response::HTTP_NO_CONTENT,
        );
    }
}
