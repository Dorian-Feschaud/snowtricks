<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Factory\GroupFactory;
use App\Factory\TrickFactory;
use App\Factory\MediaFactory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        GroupFactory::createMany(5);
        // TrickFactory::createMany(10, ['groupe' => GroupFactory::random()]);
        // MediaFactory::createMany(20, ['trick' => TrickFactory::random()]);

        $nb_groups = 5;
        $nb_tricks = 10;
        $nb_medias = 20;

        // for($i = 0; $i < $nb_groups; $i++) {
        //     GroupFactory::createOne();
        // }

        for($i = 0; $i < $nb_tricks; $i++) {
            TrickFactory::createOne(['groupe' => GroupFactory::random()]);
        }

        for($i = 0; $i < $nb_medias; $i++) {
            MediaFactory::createOne(['trick' => TrickFactory::random()]);
        }

        // $manager->flush();
    }
}
