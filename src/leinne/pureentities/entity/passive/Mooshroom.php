<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\passive;

use leinne\pureentities\animation\EatGrassAnimation;
use leinne\pureentities\PureEntities;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\item\Bowl;
use pocketmine\item\Item;
use pocketmine\item\Shears;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class Mooshroom extends Cow{
    //TODO: 다른 색상

    const TAG_TYPE = "type";

    public const TYPE_RED = "red";

    public const TYPE_BROWN = "brown";


    public function __construct(Location $location, ?CompoundTag $nbt = null, protected string $type = self::TYPE_RED)
    {
        parent::__construct($location, $nbt);
    }

    public static function getNetworkTypeId() : string{
        return EntityIds::MOOSHROOM;
    }

    public function getName() : string{
        return 'Mooshroom';
    }

    /**
     * Set the type of the cow (red | brown)
     *
     * @param string $type
     * @return bool
     */
    public function setType(string $type): bool
    {
        if ($type === "red" || $type === "brown") {
            $this->type = $type;
            $this->saveNBT();
            return true;
        }
        return false;
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();

        $nbt->setString(self::TAG_TYPE, $this->type);
        return $nbt;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function interact(Player $player, Item $item): bool
    {
        if (!$this->isBaby() && $item instanceof Shears) {
            if ($this->getType() === self::TYPE_RED) {
                $item->applyDamage(1);
                $this->getWorld()->dropItem($this->getLocation(), VanillaBlocks::RED_MUSHROOM()->asItem()->setCount(5));
            } elseif ($this->getType() === self::TYPE_BROWN) {
                $item->applyDamage(1);
                $this->getWorld()->dropItem($this->getLocation(), VanillaBlocks::BROWN_MUSHROOM()->asItem()->setCount(5));
            }
            $location = $this->getLocation();
            $this->flagForDespawn();
            PureEntities::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($location) {
            (new Cow($location))->spawnToAll();
            }), 20);
            return true;
        }
        if ($item instanceof Bowl) {
            $item->pop();
            $player->getInventory()->addItem(VanillaItems::MUSHROOM_STEW());
            return true;
        }
        return true;
    }
}