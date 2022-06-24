<?php

namespace leinne\pureentities\commands;

use leinne\pureentities\entity\EntityDataHelper;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\data\bedrock\LegacyEntityIdToStringIdMap;
use pocketmine\entity\EntityFactory;
use pocketmine\lang\Translatable;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

class SummonCommand extends Command
{

    public function __construct(string $name = "summon", Translatable|string $description = "Spawnt ein Tier.", Translatable|string|null $usageMessage = "§4Use: §r/summon <entity-id>", array $aliases = [])
    {
        $this->setPermission("summon.cmd");
        parent::__construct($name, $description, $usageMessage, $aliases);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("Nur In-Game!");
            return;
        }

        if (!isset($args[0])) {
            $sender->sendMessage($this->usageMessage);
            return;
        }

        $name = $args[0];
        $nbt = EntityDataHelper::createBaseNBT($sender->getPosition());
        $nbt->setString("id", $name);
        $entity = EntityFactory::getInstance()->createFromData($sender->getPosition()->getWorld(), $nbt);
        if ($entity !== null) {
            $entity->spawnToAll();
        }
    }
}