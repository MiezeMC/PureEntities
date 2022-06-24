<?php

namespace leinne\pureentities\entity\passive;

use leinne\pureentities\entity\ai\walk\WalkEntityTrait;
use leinne\pureentities\entity\Animal;
use pocketmine\entity\effect\EffectManager;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use function mt_rand;

class Rabbit extends Animal
{
    use WalkEntityTrait;


    public static function getNetworkTypeId(): string
    {
        return EntityIds::RABBIT;
    }

    public function getName(): string
    {
        return "Rabbit";
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.67, 0.67);
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->setSpeed(1.1);
        $this->setHealth($this->getDefaultMaxHealth());
    }

    public function getDrops(): array
    {
        $drops = [];
        if ($this->isOnFire()) {
            array_push($drops, VanillaItems::COOKED_RABBIT()->setCount(mt_rand(0, 1)));
        } else {
            array_push($drops, VanillaItems::RAW_RABBIT()->setCount(mt_rand(0, 1)));
        }

        if (mt_rand(0, 100) <= 10) { // at 10 percent chance, rabbits drop rabbit's foot
            array_push($drops, VanillaItems::RABBIT_FOOT()->setCount(1));
        }
        if ($this->getLastDamageCause() !== null && $this->getLastDamageCause() instanceof EntityDamageByEntityEvent && $this->getLastDamageCause()->getDamager() instanceof Player) {
            array_push($drops, VanillaItems::RABBIT_HIDE()->setCount(mt_rand(0, 1)));
        }

        return $drops;
    }

    public function canInteractWithTarget(Entity $target, float $distanceSquare): bool
    {
        return false; //TODO: 아이템 유인 구현
    }

    public function interactTarget(): bool
    {
        if (!parent::interactTarget()) {
            return false;
        }

        // TODO: 동물 AI 기능
        return false;
    }

    public function getDefaultMaxHealth(): int
    {
        return 3;
    }

    public function getXpDropAmount(): int
    {
        return mt_rand(1, 3);
    }
}