<?php

declare(strict_types=1);

namespace leinne\pureentities;

use leinne\pureentities\commands\SummonCommand;
use leinne\pureentities\entity\ai\path\astar\AStarPathFinder;
use leinne\pureentities\entity\LivingBase;
use leinne\pureentities\entity\neutral\CaveSpider;
use leinne\pureentities\entity\neutral\IronGolem;
use leinne\pureentities\entity\neutral\PolarBear;
use leinne\pureentities\entity\neutral\ZombifiedPiglin;
use leinne\pureentities\entity\neutral\Spider;
use leinne\pureentities\entity\passive\Chicken;
use leinne\pureentities\entity\passive\Cow;
use leinne\pureentities\entity\passive\Mooshroom;
use leinne\pureentities\entity\passive\Pig;
use leinne\pureentities\entity\passive\Rabbit;
use leinne\pureentities\entity\passive\Sheep;
use leinne\pureentities\entity\hostile\Creeper;
use leinne\pureentities\entity\hostile\Skeleton;
use leinne\pureentities\entity\hostile\Zombie;
use leinne\pureentities\entity\passive\SnowGolem;
use leinne\pureentities\event\EntityInteractByPlayerEvent;
use leinne\pureentities\item\SpawnEgg;
use leinne\pureentities\task\AutoSpawnTask;
use leinne\pureentities\entity\Vehicle;
use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\item\ItemUseResult;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\PlayerInputPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\math\Facing;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use function mt_rand;

class PureEntities extends PluginBase implements Listener
{
    use SingletonTrait;

    public static bool $enableAstar = true;

    /**
     * @var SpawnEgg[]
     */
    public static array $spawnEggs;

    protected function onLoad(): void
    {
        self::setInstance($this);
    }

    public function onEnable() : void{
        $entityFactory = EntityFactory::getInstance();
        /** Register hostile */
        $entityFactory->register(Creeper::class, function(World $world, CompoundTag $nbt) : Creeper{
            return new Creeper(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Creeper", "minecraft:creeper"]);
        $entityFactory->register(Skeleton::class, function(World $world, CompoundTag $nbt) : Skeleton{
            return new Skeleton(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Skeleton", "minecraft:skeleton"]);
        $entityFactory->register(Zombie::class, function(World $world, CompoundTag $nbt) : Zombie{
            return new Zombie(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Zombie", "minecraft:zombie"]);

        /** Register neutral */
        $entityFactory->register(IronGolem::class, function(World $world, CompoundTag $nbt) : IronGolem{
            return new IronGolem(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["IronGolem", "minecraft:iron_golem"]);
        $entityFactory->register(ZombifiedPiglin::class, function(World $world, CompoundTag $nbt) : ZombifiedPiglin{
            return new ZombifiedPiglin(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["ZombiePigman", "minecraft:zombie_pigman"]);
        $entityFactory->register(Spider::class, function(World $world, CompoundTag $nbt) : Spider{
            return new Spider(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Spider", "minecraft:spider"]);

        /** Register passive */
        $entityFactory->register(Chicken::class, function(World $world, CompoundTag $nbt) : Chicken{
            return new Chicken(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Chicken", "minecraft:chicken"]);
        $entityFactory->register(Cow::class, function(World $world, CompoundTag $nbt) : Cow{
            return new Cow(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Cow", "minecraft:cow"]);
        $entityFactory->register(Mooshroom::class, function(World $world, CompoundTag $nbt) : Mooshroom{
            return new Mooshroom(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Mooshroom", "minecraft:mooshroom"]);
        $entityFactory->register(Pig::class, function(World $world, CompoundTag $nbt) : Pig{
            return new Pig(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Pig", "minecraft:pig"]);
        $entityFactory->register(Sheep::class, function(World $world, CompoundTag $nbt) : Sheep{
            return new Sheep(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Sheep", "minecraft:sheep"]);
        $entityFactory->register(Rabbit::class, function(World $world, CompoundTag $nbt) : Rabbit{
            return new Rabbit(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Rabbit", "minecraft:rabbit"]);
        $entityFactory->register(SnowGolem::class, function(World $world, CompoundTag $nbt) : SnowGolem{
            return new SnowGolem(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["SnowGolem", "minecraft:snow_golem"]);
        $entityFactory->register(PolarBear::class, function(World $world, CompoundTag $nbt) : PolarBear{
            return new PolarBear(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["PolarBear", "minecraft:polar_bear"]);
        $entityFactory->register(CaveSpider::class, function(World $world, CompoundTag $nbt) : CaveSpider{
            return new CaveSpider(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["CaveSpider", "minecraft:cave_spider"]);

        //BlockFactory::register(new block\MonsterSpawner(new BlockIdentifier(BlockLegacyIds::MOB_SPAWNER, 0, null, tile\MonsterSpawner::class), "Monster Spawner"), true);

        $itemFactory = ItemFactory::getInstance();
        /** Register hostile */
        /*$itemFactory->register(new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::CREEPER), "Creeper Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new Creeper(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }, true);*/
        $itemFactory->register(($skeleton = new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::SKELETON), "Skeleton Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new Skeleton(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }), true);
        self::$spawnEggs[$skeleton->getMeta()] = $skeleton;
        $itemFactory->register(($zombie = new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::ZOMBIE), "Zombie Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new Zombie(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }), true);
        self::$spawnEggs[$zombie->getMeta()] = $zombie;
        /** Register neutral */
        $itemFactory->register(($ironGolem = new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::IRON_GOLEM), "IronGolem Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new IronGolem(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }), true);
        self::$spawnEggs[$ironGolem->getMeta()] = $ironGolem;
        $itemFactory->register(($zombiefiedPiglin = new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::ZOMBIE_PIGMAN), "ZombifiedPiglin Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new ZombifiedPiglin(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }), true);
        self::$spawnEggs[$zombiefiedPiglin->getMeta()] = $zombiefiedPiglin;
        $itemFactory->register(($spider = new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::SPIDER), "Spider Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new Spider(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }), true);
        self::$spawnEggs[$spider->getMeta()] = $spider;

        /** Register passive */
        /*$this->registerSpawnegg(Chicken::class, EntityLegacyIds::CHICKEN, "Chicken");
        $this->registerSpawnegg(Cow::class, EntityLegacyIds::COW, "Cow");
        $this->registerSpawnegg(Mooshroom::class, EntityLegacyIds::MOOSHROOM, "Mooshroom");
        $this->registerSpawnegg(Pig::class, EntityLegacyIds::PIG, "Pig");
        $this->registerSpawnegg(Sheep::class, EntityLegacyIds::SHEEP, "Sheep");
        $this->registerSpawnegg(SnowGolem::class, EntityLegacyIds::SNOW_GOLEM, "Snow Golem");
        $this->registerSpawnegg(IronGolem::class, EntityLegacyIds::IRON_GOLEM, "Iron Golem");
        $this->registerSpawnegg(Zombie::class, EntityLegacyIds::ZOMBIE, "Zombie");
        $this->registerSpawnegg(Spider::class, EntityLegacyIds::SPIDER, "Spider");
        $this->registerSpawnegg(Skeleton::class, EntityLegacyIds::SKELETON, "Skeleton");
        $this->registerSpawnegg(ZombifiedPiglin::class, EntityLegacyIds::ZOMBIE_PIGMAN, "Zombie Piglin");*/

        $itemFactory->register(($chicken = new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::CHICKEN), "Chicken Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new Chicken(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }), true);
        self::$spawnEggs[$chicken->getMeta()] = $chicken;
        $itemFactory->register(($cow = new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::COW), "Cow Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new Cow(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }), true);
        self::$spawnEggs[$cow->getMeta()] = $cow;
        $itemFactory->register(($mooshroom = new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::MOOSHROOM), "Mooshroom Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new Mooshroom(Location::fromObject($pos, $world, $yaw, $pitch), null, (mt_rand(0, 2) == 1 ? Mooshroom::TYPE_BROWN : Mooshroom::TYPE_RED));
            }
        }), true);
        self::$spawnEggs[$mooshroom->getMeta()] = $mooshroom;
        $itemFactory->register(($pig = new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::PIG), "Pig Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new Pig(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }), true);
        self::$spawnEggs[$pig->getMeta()] = $pig;
        $itemFactory->register(($sheep = new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::SHEEP), "Sheep Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new Sheep(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }), true);
        self::$spawnEggs[$sheep->getMeta()] = $sheep;
        $itemFactory->register(($snowGolem = new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::SNOW_GOLEM), "SnowGolem Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new SnowGolem(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }), true);
        self::$spawnEggs[$snowGolem->getMeta()] = $snowGolem;
        $itemFactory->register(($rabbit = new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::RABBIT), "Rabbit Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new Rabbit(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }), true);
        self::$spawnEggs[$rabbit->getMeta()] = $rabbit;
        $itemFactory->register(($polarBear = new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::POLAR_BEAR), "Polar Bear Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new PolarBear(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }), true);
        self::$spawnEggs[$polarBear->getMeta()] = $polarBear;
        $itemFactory->register(($caveSpider = new class(new ItemIdentifier(ItemIds::SPAWN_EGG, EntityLegacyIds::CAVE_SPIDER), "Cave Spider Spawn Egg") extends SpawnEgg{
            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch) : Entity{
                return new CaveSpider(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }), true);
        self::$spawnEggs[$caveSpider->getMeta()] = $caveSpider;

        $creaInv = CreativeInventory::getInstance();
        $creaInv->remove($skeleton);
        $creaInv->remove($zombie);
        $creaInv->remove($ironGolem);
        $creaInv->remove($zombiefiedPiglin);
        $creaInv->remove($chicken);
        $creaInv->remove($cow);
        $creaInv->remove($mooshroom);
        $creaInv->remove($pig);
        $creaInv->remove($sheep);
        $creaInv->remove($snowGolem);
        $creaInv->remove($rabbit);
        $creaInv->remove($polarBear);
        $creaInv->remove($caveSpider);

        $creaInv->add($skeleton);
        $creaInv->add($zombie);
        $creaInv->add($ironGolem);
        $creaInv->add($zombiefiedPiglin);
        $creaInv->add($chicken);
        $creaInv->add($cow);
        $creaInv->add($mooshroom);
        $creaInv->add($pig);
        $creaInv->add($sheep);
        $creaInv->add($snowGolem);
        $creaInv->add($rabbit);
        $creaInv->add($polarBear);
        $creaInv->add($caveSpider);

        $this->saveDefaultConfig();
        $data = $this->getConfig()->getAll();

        $spawnable = $data["autospawn"] ?? [];
        if($spawnable["enable"] ?? false){
            $this->getScheduler()->scheduleRepeatingTask(new AutoSpawnTask(), (int) ($spawnable["tick"] ?? 80));
        }

        self::$enableAstar = (bool) ($data["astar"]["enable"] ?? false);
        if(self::$enableAstar){
            AStarPathFinder::setData((int) ($data["astar"]["maximum-tick"] ?? 150), (int) ($data["astar"]["block-per-tick"] ?? 70));
        }

        /*$this->getServer()->getLogger()->info(
            TextFormat::AQUA . "\n" .
            "---------------------------------------------------------\n" .
            " _____                _____       _    _ _    _\n" .
            "|  __ \              |  ___|     | |  |_| |  |_|\n" .
            "| |__) |   _ _ __ ___| |__  _ __ | |__ _| |__ _  ___  ___ \n" .
            "|  ___/ | | | '__/ _ \  __|| '_ \| ___| | ___| |/ _ \/ __|\n" .
            "| |   | |_| | | |  __/ |___| | | | |__| | |__| |  __/\__ \\\n" .
            "|_|    \__,_|_|  \___|_____|_| |_|\___|_|\___|_|\___||___/\n" .
            "----------------------------------------------------------\n"
        );*/

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getCommandMap()->register("PureEntities", new SummonCommand());
    }

    public function onPlayerQuitEvent(PlayerQuitEvent $event) : void{
        $player = $event->getPlayer();
        if(isset(Vehicle::$riders[$id = $player->getId()])){
            Vehicle::$riders[$id]->removePassenger($player);
        }
    }

    public function onEntityDeathEvent(EntityDeathEvent $event) : void{
        $entity = $event->getEntity();
        if(isset(Vehicle::$riders[$id = $entity->getId()])){
            Vehicle::$riders[$id]->removePassenger($entity);
        }
    }

    public function onPlayerTeleportEvent(EntityTeleportEvent $event) : void{
        $entity = $event->getEntity();
        if(isset(Vehicle::$riders[$id = $entity->getId()])){
            Vehicle::$riders[$id]->removePassenger($entity);
        }
    }

    /** @priority HIGHEST */
    public function onDataPacketEvent(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        if($packet instanceof InteractPacket && $packet->action === InteractPacket::ACTION_LEAVE_VEHICLE){
            $event->cancel();
            $player = $event->getOrigin()->getPlayer();
            $entity = $player->getWorld()->getEntity($packet->target);
            if($entity instanceof Vehicle && !$entity->isClosed()){
                $entity->removePassenger($player);
            }
        }elseif(
            $packet instanceof InventoryTransactionPacket &&
            $packet->trData instanceof UseItemOnEntityTransactionData &&
            $packet->trData->getActionType() === UseItemOnEntityTransactionData::ACTION_INTERACT
        ){
            $player = $event->getOrigin()->getPlayer();
            $entity = $player->getWorld()->getEntity($packet->trData->getActorRuntimeId());
            if(($entity instanceof LivingBase || $entity instanceof Vehicle) && !$entity->isClosed()){
                $event->cancel();
                $item = $player->getInventory()->getItemInHand();
                $oldItem = clone $item;
                $ev = new EntityInteractByPlayerEvent($entity, $player, $item);
                $ev->call();

                if(!$ev->isCancelled() && $entity->interact($player, $item)){
                    if(
                        $player->hasFiniteResources() &&
                        !$item->equalsExact($oldItem) &&
                        $oldItem->equalsExact($player->getInventory()->getItemInHand())
                    ){
                        $player->getInventory()->setItemInHand($item);
                    }
                }
            }
        }elseif($packet instanceof MoveActorAbsolutePacket){
            $player = $event->getOrigin()->getPlayer();
            $entity = $player->getWorld()->getEntity($packet->actorRuntimeId);
            if($entity instanceof Vehicle && !$entity->isClosed() && $entity->getRider() === $player){
                $event->cancel();
                //[xRot, yRot, zRot] = [pitch, headYaw, yaw]
                $entity->absoluteMove($packet->position, $packet->yRot, $packet->xRot);
            }
        }elseif($packet instanceof AnimatePacket){
            $player = $event->getOrigin()->getPlayer();
            $vehicle = Vehicle::$riders[$player->getId()] ?? null;
            if($vehicle !== null && !$vehicle->isClosed() && $vehicle->handleAnimatePacket($packet)){
                $event->cancel();
            }
        }elseif($packet instanceof PlayerInputPacket){
            $player = $event->getOrigin()->getPlayer();
            $vehicle = Vehicle::$riders[$player->getId()] ?? null;
            if($vehicle !== null && !$vehicle->isClosed() && $vehicle->getRider() === $player){
                $event->cancel();
                $vehicle->updateMotion($packet->motionX, $packet->motionY);
            }
        }
    }

    public function onInteractEvent(PlayerInteractEvent $ev) : void{
        //TODO: MonsterSpawner 기능 준비
        /*if($ev->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK){
            return;
        }

        $item = $ev->getItem();
        $block = $ev->getBlock();
        if($item->getId() === ItemIds::SPAWN_EGG && $block->getId() === ItemIds::MONSTER_SPAWNER){
            $ev->cancel();

            $tile = $block->getPosition()->getWorld()->getTile($block->getPosition());
            if($tile instanceof tile\MonsterSpawner){
                $tile->setSpawnEntityType($item->getMeta());
            }else{
                if($tile !== null){
                    $tile->close();
                }

                $tile = TileFactory::create("MobSpawner", $block->getPosition()->getWorld(), $block->getPosition());
                $tile->readSaveData(CompoundTag::create()->setInt("EntityId", $item->getMeta()));
                $tile->getPosition()->getWorld()->addTile($tile);
            }
        }*/
    }

    /**
     * @priority MONITOR
     *
     * @param BlockPlaceEvent $ev
     */
    public function onBlockPlaceEvent(BlockPlaceEvent $ev) : void{
        $item = $ev->getItem();
        $block = $ev->getBlock();
        $player = $ev->getPlayer();
        $bid = $block->getId();
        if($bid === BlockLegacyIds::JACK_O_LANTERN || $bid === BlockLegacyIds::PUMPKIN || $bid === BlockLegacyIds::CARVED_PUMPKIN){
            if(
                $block->getSide(Facing::DOWN)->getId() === BlockLegacyIds::SNOW_BLOCK
                && $block->getSide(Facing::DOWN, 2)->getId() === BlockLegacyIds::SNOW_BLOCK
            ){
                $ev->cancel();

                $pos = $block->getPosition()->asVector3();
                $air = VanillaBlocks::AIR();
                for($y = 0; $y < 2; ++$y){
                    --$pos->y;
                    $block->getPosition()->getWorld()->setBlock($pos, $air);
                }

                $entity = new SnowGolem(Location::fromObject($block->getPosition()->add(0.5, -2, 0.5), $block->getPosition()->getWorld()));
                $entity->spawnToAll();

                if($player->hasFiniteResources()){
                    $item->pop();
                    $player->getInventory()->setItemInHand($item);
                }
            }elseif(
                ($down = $block->getSide(Facing::DOWN))->getId() === BlockLegacyIds::IRON_BLOCK
                && $block->getSide(Facing::DOWN, 2)->getId() === BlockLegacyIds::IRON_BLOCK
            ){
                if(($first = $down->getSide(Facing::EAST))->getId() === BlockLegacyIds::IRON_BLOCK){
                    $second = $down->getSide(Facing::WEST);
                }

                if(!isset($second) && ($first = $down->getSide(Facing::NORTH))->getId() === BlockLegacyIds::IRON_BLOCK){
                    $second = $down->getSide(Facing::SOUTH);
                }

                if(!isset($second) || $second->getId() !== BlockLegacyIds::IRON_BLOCK){
                    return;
                }

                $ev->cancel();
                $entity = new IronGolem(Location::fromObject($pos = $block->getPosition()->add(0.5, -2, 0.5), $block->getPosition()->getWorld()), CompoundTag::create()->setByte("PlayerCreated", 1));
                $entity->spawnToAll();

                $down->getPosition()->getWorld()->setBlock($pos, $air = VanillaBlocks::AIR());
                $down->getPosition()->getWorld()->setBlock($first->getPosition(), $air);
                $down->getPosition()->getWorld()->setBlock($second->getPosition(), $air);
                $down->getPosition()->getWorld()->setBlock($block->getPosition()->add(0, -1, 0), $air);

                if($player->hasFiniteResources()){
                    $item->pop();
                    $player->getInventory()->setItemInHand($item);
                }
            }
        }
    }

    protected function registerSpawnegg(string $entityClass, int $entityId, string $entityName): void
    {
        ItemFactory::getInstance()->register($item = new class(new ItemIdentifier(ItemIds::SPAWN_EGG, $entityId, $entityName . " Spawn Egg", $entityClass)) extends SpawnEgg {

            public function __construct(ItemIdentifier $identifier, string $name = "Unknown", private string $namespace = "")
            {
                parent::__construct($identifier, $name);
            }

            protected function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch): Entity
            {
                return new $this->namespace(Location::fromObject($pos, $world, $yaw, $pitch));
            }
        }, true);

        if(!CreativeInventory::getInstance()->contains($item))
            CreativeInventory::getInstance()->add($item);
    }

    //TODO: check golem block shape
    /*private function canSpawnGolem(Position $pos, int $id) : bool{
        $resultShape = [];
        for($x = -1; $x < 2; ++$x){
            for($y = -1; $y > -3; --$y){
                $resultShape[$x + 1][$y + 2] = $pos->world->getBlock($pos->add($x, $y, 0))->getId() === $id ? "O" : "X";
            }
        }
        return $resultShape == [["O", "X"], ["O", "O"], ["O", "X"]];
    }*/

    //TODO: SilverFish
    /*public function BlockBreakEvent(BlockBreakEvent $ev){
        if($ev->isCancelled()){
            return;
        }

        $block = $ev->getBlock();
        if(
            (
                $block->getId() === BlockLegacyIds::STONE
                or $block->getId() === BlockLegacyIds::STONE_WALL
                or $block->getId() === BlockLegacyIds::STONE_BRICK
                or $block->getId() === BlockLegacyIds::STONE_BRICK_STAIRS
            ) && ($block->level->getBlockLightAt((int) $block->x, (int) $block->y, (int) $block->z) < 12 and mt_rand(1, 5) < 2)
        ){
            $entity = PureEntities::create("Silverfish", $block);
            if($entity !== \null){
                $entity->spawnToAll();
            }
        }
    }*/

}