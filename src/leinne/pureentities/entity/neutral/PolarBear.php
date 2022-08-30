<?php


namespace leinne\pureentities\entity\neutral;

use leinne\pureentities\entity\ai\walk\WalkEntityTrait;
use leinne\pureentities\entity\Monster;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use function mt_rand;

class PolarBear extends Monster
{
    use WalkEntityTrait;

    private bool $playerCreated = false;

    public static function getNetworkTypeId() : string{
        return EntityIds::POLAR_BEAR;
    }

    protected function getInitialSizeInfo() : EntitySizeInfo
    {
        return new EntitySizeInfo(1.4, 1.3);
    }

    public function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->playerCreated = $nbt->getByte("PlayerCreated", 0) !== 0;

        $this->setSpeed(1);
        $this->setDamages([0, 3, 5, 8]);
    }

    public function getDefaultMaxHealth() : int{
        return 30;
    }

    public function getName() : string{
        return "Polar Bear";
    }

    public function isPlayerCreated() : bool{
        return $this->playerCreated;
    }

    public function canSpawnPeaceful() : bool{
        return true;
    }

    /**
     * $this 와 $target의 관계가 적대관계인지 확인
     *
     * @param Entity $target
     * @param float $distanceSquare
     *
     * @return bool
     */
    public function canInteractWithTarget(Entity $target, float $distanceSquare) : bool{
        if($target instanceof Player && ($this->isPlayerCreated() || !$target->isSurvival())){
            return false;
        }elseif($target instanceof PolarBear){
            return false;
        }
        return $this->fixedTarget || ($target instanceof Monster || !$this->isPlayerCreated()) && $target->isAlive() && !$target->closed && $distanceSquare <= 324;
    }

    public function interactTarget() : bool{
        if(!parent::interactTarget()){
            return false;
        }

        if($this->interactDelay >= 20){
            $target = $this->getTargetEntity();
            if($target instanceof Player){
                $damage = $this->getResultDamage();
            }else{
                $damage = $this->getResultDamage(2);
            }

            if($damage >= 0){
                $this->broadcastAnimation(new ArmSwingAnimation($this));

                $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $damage);
                $target->attack($ev);
                if(!$ev->isCancelled()){
                    $this->interactDelay = 0;
                    $target->setMotion($target->getMotion()->add(0, 0.45, 0));
                }
            }
        }
        return true;
    }

    public function saveNBT() : CompoundTag
    {
        $nbt = parent::saveNBT();
        $nbt->setByte("PlayerCreated", $this->playerCreated ? 1 : 0);
        return $nbt;
    }

    public function getDrops() : array{
        $drops = [];

        if (mt_rand(1, 4) <= 3) {
            $drops[] = VanillaItems::RAW_SALMON()->setCount(mt_rand(0, 2));
        }
        if (mt_rand(1, 4) <= 3) {
            $drops[] = VanillaItems::RAW_FISH()->setCount(mt_rand(0, 2));
        }

        return $drops;
    }

    public function getXpDropAmount() : int{
        return mt_rand(1, 3);
    }
}