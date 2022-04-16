<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Conference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Class ConferenceFixtures
 * @package App\DataFixtures
 */
class ConferenceFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $conference = new Conference();
        $conference
            ->setCity('Tokio')
            ->setYear('1999')
            ->setIsInternational(true)
            ->setSlug('-')
            ;
        $comment = new Comment();
        $comment
            ->setAuthor('Pepito')
            ->setConference($conference)
            ->setEmail('pe@pe.pe')
            ->setText('what an amazing show')
            ->setPhotoFilename('c57207f7197e.jpg')
            ;
        $manager->persist($conference);
        $manager->persist($comment);

        $conference = new Conference();
        $conference
            ->setCity('Bogota')
            ->setYear('2000')
            ->setIsInternational(true)
            ->setSlug('-')
        ;
        $manager->persist($conference);

        $conference = new Conference();
        $conference
            ->setCity('Madrid')
            ->setYear('2001')
            ->setIsInternational(false)
            ->setSlug('-')
        ;
        $manager->persist($conference);

        $manager->flush();
    }
}
