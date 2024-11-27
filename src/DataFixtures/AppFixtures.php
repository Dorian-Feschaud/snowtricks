<?php

namespace App\DataFixtures;

use App\Entity\Group;
use App\Entity\Media;
use App\Entity\Trick;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private readonly Generator $faker;

    private ?ObjectManager $manager = null;

    private UserPasswordHasherInterface $hasher;

    public function __construct(ObjectManager $manager, UserPasswordHasherInterface $hasher) {
        $this->manager = $manager;
        $this->hasher = $hasher;

        $this->faker = $this->createFaker();
    }

    protected function createFaker()
    {
        return Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        $users = $this->loadUsers();
        $groups = $this->loadGroups();
        $tricks = $this->loadTricks($users, $groups);
        $medias = $this->loadMedias($tricks);

        $manager->flush();
    }

    /**
     * @return User[]
     */
    protected function loadUsers(): array
    {
        $entities = [];

        $user_1 = new User();
        $user_1->setFirstname('Admin');
        $user_1->setLastname('Admin');
        $user_1->setEmail('admin@example.com');
        $user_1->setRoles([User::ROLE_ADMIN]);
        $user_1->setImage('user_default_pic.png');
        $user_1->setPassword($this->hasher->hashPassword($user_1, 'admin'));

        $entities[] = $user_1;

        $this->manager->persist($user_1);

        $user_2 = new User();
        $user_2->setFirstname('Moderator');
        $user_2->setLastname('Moderator');
        $user_2->setEmail('moderator@example.com');
        $user_2->setRoles([User::ROLE_MODERATOR]);
        $user_2->setImage('user_default_pic.png');
        $user_2->setPassword($this->hasher->hashPassword($user_2, 'moderator'));

        $entities[] = $user_2;

        $this->manager->persist($user_2);

        return $entities;
    }

    /**
     * @return Group[]
     */
    protected function loadGroups(int $count = 5): array
    {
        $entities = [];

        for ($i = 0; $i < $count; $i++) {
            $group = new Group();
            $group->setName(sprintf('Group %s', $i));

            $entities[] = $group;

            $this->manager->persist($group);
        }

        return $entities;
    }

    /**
     * @return Trick[]
     */
    protected function loadTricks(array $users, array $groups, int $count = 10): array
    {
        $entities = [];

        for ($i = 0; $i < $count; $i++) {
            $trick = new Trick();
            $trick->setName(sprintf('Trick %s', $i));
            $trick->setDescription($this->faker->text());
            $trick->setGroupe($this->faker->randomElement($groups));
            $trick->setUser($this->faker->randomElement($users));

            $entities[] = $trick;

            $this->manager->persist($trick);
        }

        return $entities;
    }

    /** 
     * @return Media[]
    */
    protected function loadMedias(array $tricks, int $count = 3): array
    {
        $entities = [];

        foreach($tricks as $trick) {
            $nb_medias = $count - $this->faker->numberBetween(0, 2);
            for ($i = 0; $i < $nb_medias; $i++) {
                $media = new Media();
                $media->setUrl('default_trick_pic_1.jpg');
                $media->setType('image');
                $media->setTrick($trick);
    
                $entities[] = $media;
    
                $this->manager->persist($media);
            }
        }

        return $entities;

    }
}
