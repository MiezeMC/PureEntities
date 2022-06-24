<?php

namespace leinne\pureentities\item;

use MyPlot\MyPlot;
use pocketmine\block\Block;
use pocketmine\item\ItemUseResult;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use function in_array;
use function printf;
use function strtolower;

abstract class SpawnEgg extends \pocketmine\item\SpawnEgg
{

    public function onInteractBlock(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector): ItemUseResult
    {
        $final_pos = $blockReplace->getPosition()->add(0.5, 0, 0.5);

        $spawnEntity = function () use ($player, $final_pos): void {
            $entity = $this->createEntity($player->getWorld(), $final_pos, lcg_value() * 360, 0);

            if($this->hasCustomName()){
                $entity->setNameTag($this->getCustomName());
            }
            $this->pop();
            $entity->spawnToAll();
        };

        if (Server::getInstance()->getOps()->exists(strtolower($player->getName()))
        || $player->hasPermission("mmc.mobs")) {
            $spawnEntity();
            return ItemUseResult::SUCCESS();
        }

        printf("FALSE!");

        $plot = MyPlot::getInstance()->getPlotByPosition(new Position(
                $final_pos->getX(),
                $final_pos->getY(),
                $final_pos->getZ(),
                $player->getWorld()));

        if ($plot === null) {
            $player->sendTip("Dies ist hier nicht erlaubt!");
            return ItemUseResult::FAIL();
        }


        if ($plot->owner !== $player->getName()
            && !in_array($player->getName(), $plot->helpers)) {
            $player->sendTip("Dies ist hier nicht erlaubt!");
            return ItemUseResult::FAIL();
        }
        return ItemUseResult::FAIL();
    }
}