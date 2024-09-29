<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Actor;
use App\Entity\Enum\EventTypeEnum;
use App\Entity\Event;
use App\Entity\Repo;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EventFixtures extends Fixture
{
    public const EVENT_1_ID = 1;
    public const ACTOR_1_ID = 1;
    public const REPO_1_ID = 1;

    public function load(ObjectManager $manager)
    {
        $event = new Event(
            self::EVENT_1_ID,
            EventTypeEnum::COMMENT,
            new Actor(
                self::ACTOR_1_ID,
                'jdoe',
                'https://api.github.com/users/jdoe',
                'https://avatars.githubusercontent.com/u/1?'
            ),
            new Repo(
                self::REPO_1_ID,
                'yousign/test',
                'https://api.github.com/repos/yousign/backend-test'
            ),
            [],
            new \DateTimeImmutable(),
            'Test comment initiate by fixture '
        );

        $manager->persist($event);
        $manager->flush();
    }
}
