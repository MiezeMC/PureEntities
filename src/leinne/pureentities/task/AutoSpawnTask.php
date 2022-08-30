<?php

declare(strict_types=1);

namespace leinne\pureentities\task;

use leinne\pureentities\entity\hostile\Creeper;
use leinne\pureentities\entity\hostile\Husk;
use leinne\pureentities\entity\hostile\Skeleton;
use leinne\pureentities\entity\hostile\Stray;
use leinne\pureentities\entity\hostile\Zombie;
use leinne\pureentities\entity\neutral\CaveSpider;
use leinne\pureentities\entity\neutral\IronGolem;
use leinne\pureentities\entity\neutral\PolarBear;
use leinne\pureentities\entity\neutral\Spider;
use leinne\pureentities\entity\neutral\ZombifiedPiglin;
use leinne\pureentities\entity\passive\Chicken;
use leinne\pureentities\entity\passive\Cow;
use leinne\pureentities\entity\passive\Mooshroom;
use leinne\pureentities\entity\passive\Pig;
use leinne\pureentities\entity\passive\Rabbit;
use leinne\pureentities\entity\passive\Sheep;
use leinne\pureentities\PureEntities;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\entity\Location;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class AutoSpawnTask extends Task
{

    public function onRun(): void
    {
        foreach (Server::getInstance()->getOnlinePlayers() as $k => $player) {
            if (mt_rand(1, 200) !== 1) {
                continue;
            }

            if (is_array(PureEntities::getInstance()->getConfig()->get("worlds", [])) && in_array($player->getWorld()->getFolderName(), PureEntities::getInstance()->getConfig()->get("worlds", []))) {

                $radX = mt_rand(3, 24);
                $radZ = mt_rand(3, 24);
                $pos = $player->getLocation()->floor();
                $pos->y = $player->getWorld()->getHighestBlockAt($pos->x += mt_rand(0, 1) ? $radX : -$radX, $pos->z += mt_rand(0, 1) ? $radZ : -$radZ) + 1;

                $entityClasses = [
                    [Cow::class, Pig::class, Sheep::class, Chicken::class, Mooshroom::class, IronGolem::class, Mooshroom::class, Rabbit::class, PolarBear::class],//, "Slime", "Wolf", "Ocelot", "Rabbit"],
                    [Zombie::class, Creeper::class, Skeleton::class, Spider::class, CaveSpider::class, ZombifiedPiglin::class, Husk::class, Stray::class]//, "Enderman", "CaveSpider", "MagmaCube", "ZombieVillager", "Ghast", "Blaze"]
                ];

                /*if (($chunk = $player->getWorld()->getOrLoadChunkAtPosition($pos))) {
                    $biome = $chunk->getBiomeId($pos->getFloorX(), $pos->getFloorZ());
                    if ($biome === BiomeIds::FOREST_HILLS || $biome === BiomeIds::ICE_MOUNTAINS || $biome === BiomeIds::TAIGA) {
                        $entity = new CaveSpider(Location::fromObject($pos, $player->getWorld()));
                    } else $entity = new $entityClasses[mt_rand(0, 1)][mt_rand(0, 4)](Location::fromObject($pos, $player->getWorld()));
                } else $entity = new $entityClasses[mt_rand(0, 1)][mt_rand(0, 4)](Location::fromObject($pos, $player->getWorld()));*/

                $entity = new $entityClasses[mt_rand(0, 1)][mt_rand(0, 4)](Location::fromObject($pos, $player->getWorld()));
                $entity->spawnToAll();
            }
        }
    }
}