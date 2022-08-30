<?php

namespace leinne\pureentities\entity\hostile;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Bow;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use function mt_rand;

class Stray extends Skeleton
{

    public static function getNetworkTypeId(): string
    {
        return EntityIds::STRAY;
    }

    public function getDrops(): array
    {
        return [
            VanillaItems::ARROW()->setCount(mt_rand(0, 2)),
            VanillaItems::BONE()->setCount(mt_rand(0, 2))
        ];
    }

    public function interactTarget() : bool{
        if($this->inventory->getItemInHand() instanceof Bow){
            return $this->interactTargetBow();
        }

        if(!parent::interactTarget()){
            return false;
        }

        if($this->interactDelay >= 20){
            $target = $this->getTargetEntity();
            $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getResultDamage());
            $target->attack($ev);

            if(!$ev->isCancelled()){
                $this->interactDelay = 0;
            }
        }
        return true;
    }
}