<?php

namespace leinne\pureentities\entity\hostile;

use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Husk extends Zombie
{

    public static function getNetworkTypeId(): string
    {
        return EntityIds::HUSK;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(2.2, 0.6);
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        return $this->baseTick($tickDiff);
    }

    public function interactTarget(): bool
    {
        if (!parent::interactTarget()) {
            return false;
        }

        if ($this->interactDelay >= 20) {
            $this->broadcastAnimation(new ArmSwingAnimation($this));

            $target = $this->getTargetEntity();
            $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getResultDamage());
            $target->attack($ev);
            $mode = $this->getWorld()->getDifficulty();
            if ($target instanceof Living)
                switch ($mode) {
                    case 2:
                        $target->getEffects()->add(new EffectInstance(VanillaEffects::HUNGER(), 7));
                        break;
                    case 3:
                        $target->getEffects()->add(new EffectInstance(VanillaEffects::HUNGER(), 14));
                        break;
                }

            if (!$ev->isCancelled())
                $this->interactDelay = 0;
        }
        return true;
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        if ($this->isBaby()) {
            $this->setScale(1.0);
        }

        $this->setSpeed(0.9);
        $this->setDamages([0, 2.5, 3, 4.5]);

        /**@var $items Item[] */
        $items = [VanillaItems::AIR(), VanillaItems::AIR(), VanillaItems::AIR(),
            VanillaItems::IRON_SWORD(), VanillaItems::IRON_SHOVEL(), VanillaItems::IRON_CHESTPLATE(),
            VanillaItems::IRON_HELMET(), VanillaItems::CHAINMAIL_CHESTPLATE(), VanillaItems::CHAINMAIL_HELMET()];
        $finalItem = $items[array_rand($items)];

        if ($finalItem instanceof Armor)
            $this->getArmorInventory()->setItem($finalItem->getArmorSlot(), $finalItem);
        else
            $this->getInventory()->setItemInHand($finalItem);
    }
}