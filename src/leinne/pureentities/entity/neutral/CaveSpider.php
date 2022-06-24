<?php

namespace leinne\pureentities\entity\neutral;

use leinne\pureentities\entity\Monster;
use leinne\pureentities\entity\ai\walk\WalkEntityTrait;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Human;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\player\Player;
use pocketmine\Server;

class CaveSpider extends Monster
{
    use WalkEntityTrait {
        entityBaseTick as baseTick;
    }

    private bool $angry = false;

    public static function getNetworkTypeId(): string
    {
        return EntityIds::CAVE_SPIDER;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.5, 0.7);
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->angry = $nbt->getByte('Angry', 0) !== 0;

        $this->setDamages([0, 2, 2, 3]);
    }

    public function getName(): string
    {
        return 'Cave Spider';
    }

    public function canInteractWithTarget(Entity $target, float $distance): bool
    {
        return $this->isAngry() && parent::canInteractWithTarget($target, $distance);
    }

    public function attack(EntityDamageEvent $source): void
    {
        parent::attack($source);

        if (!$source->isCancelled() && $source instanceof EntityDamageByEntityEvent && $source->getDamager() instanceof Human) {
            $this->setAngry(true);
        }
    }

    public function isAngry(): bool
    {
        return $this->angry;
    }

    public function setAngry(bool $value): void
    {
        $this->angry = $value;
        $this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::ANGRY, $this->angry);
    }

    public function toggleAngry(): bool
    {
        $this->setAngry(!$this->angry);
        return $this->angry;
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        if ($this->isAlive() && $this->angry) {
            //TODO: 얘 화 어떻게 풀림?
            //--$this->angry;
        }

        $hasUpdate = false;

        $time = $this->getWorld()->getTime();
        $loc = $this->getLocation();

        return $this->baseTick($tickDiff);
    }

    public function interactTarget(): bool
    {
        if (!parent::interactTarget()) {
            return false;
        }

        $target = $this->getTargetEntity();
        if ($this->getSpeed() < 2 && $this->isAngry() && $target instanceof Living) {
            $this->setSpeed(2);
        } elseif ($this->getSpeed() == 2) {
            $this->setSpeed(0.7);
        }

        if ($this->interactDelay >= 20) {
            $this->broadcastAnimation(new ArmSwingAnimation($this));

            $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamages(Server::getInstance()->getDifficulty())[1]);
            $target->attack($ev);
            if (Server::getInstance()->getDifficulty() <= 2 && Server::getInstance()->getDifficulty() !== 0)$target->getEffects()->add(new EffectInstance(VanillaEffects::POISON(), (20 * 7)));
            if (Server::getInstance()->getDifficulty() == 3)$target->getEffects()->add(new EffectInstance(VanillaEffects::POISON(), (20 * 15)));

            if (!$ev->isCancelled()) {
                $this->interactDelay = 0;
            }
        }
        return true;
    }

    protected function syncNetworkData(EntityMetadataCollection $properties): void
    {
        parent::syncNetworkData($properties);

        $properties->setGenericFlag(EntityMetadataFlags::ANGRY, $this->angry);
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();
        $nbt->setByte("IsAngry", $this->angry ? 1 : 0);
        return $nbt;
    }

    public function getDrops(): array
    {
        $drops = [
            VanillaItems::STRING()->setCount(mt_rand(0, 2))
        ];

        if (
            $this->lastDamageCause instanceof EntityDamageByEntityEvent
            && $this->lastDamageCause->getDamager() instanceof Player
            && !mt_rand(0, 2)
        ) {
            $drops[] = VanillaItems::SPIDER_EYE();
        }
        return $drops;
    }

    public function getXpDropAmount(): int
    {
        return 5;
    }
}