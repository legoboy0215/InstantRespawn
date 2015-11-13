<?php

namespace thebigsmileXD\InstantRespawn;

use pocketmine\scheduler\PluginTask;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Float;
use pocketmine\nbt\tag\Int;
use pocketmine\nbt\tag\Byte;
use pocketmine\Player;

class SendTip extends PluginTask{

	public function __construct(Plugin $owner, $player){
		parent::__construct($owner);
		$this->plugin = $owner;
		$this->player = $player;
	}

	public function onRun($currentTick){
		if($this->player instanceof Player && $this->getOwner()->getServer()->getPlayer($this->player->getName())->isOnline()){
			$player = $this->getOwner()->getServer()->getPlayer($this->player->getName());
			$player->sendTip(str_replace("{PLAYER}", $player->getDisplayName(), TextFormat::RED . $this->getOwner()->getConfig()->getNested("messages.messagetoplayer")));
			/*
			 * $fall = Entity::createEntity("FallingSand", $player->getLevel()->getChunk($player->x >> 4, $player->z >> 4), new Compound("", ["Pos" => new Enum("Pos", [new Double("", $player->x),new Double("", $player->y + 1),new Double("", $player->z)]),
			 * "Motion" => new Enum("Motion", [new Double("", 0),new Double("", 0),new Double("", 0)]),"Rotation" => new Enum("Rotation", [new Float("", 0),new Float("", 0)]),"TileID" => new Int("TileID", Block::STAINED_HARDENED_CLAY),"Data" => new Byte("Data", 15)]));
			 * $fall->spawnTo($player);
			 */
			return;
		}
		else
			// if(isset($fall) && $fall !== null) $fall->despawnFromAll();
			$this->getOwner()->getServer()->getScheduler()->cancelTask($this->getTaskId());
		return;
	}
}
?>