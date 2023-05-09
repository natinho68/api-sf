<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');
        $listAuthor = [];
        for ($i = 1; $i < 10; $i++) {
            $author = new Author();
            $author->setFirstName($faker->firstName());
            $author->setLastName($faker->lastName());
            $manager->persist($author);

            $listAuthor[] = $author;
        }

        for ($i = 1; $i <= 20; $i++) {
            $book = new Book();
            $book->setTitle($faker->sentence(3));
            $book->setCoverText($faker->sentence(18));
            $book->setAuthor($listAuthor[array_rand($listAuthor)]);

            $manager->persist($book);
        }

        $manager->flush();
    }
}
