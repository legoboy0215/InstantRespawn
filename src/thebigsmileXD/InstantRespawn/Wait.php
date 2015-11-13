<?php

namespace thebigsmileXD\InstantRespawn;

use pocketmine\scheduler\PluginTask;
use pocketmine\plugin\Plugin;
use pocketmine\Player;

class Wait extends PluginTask{

	public function __construct(Plugin $owner, Player $player){
		parent::__construct($owner);
		$this->plugin = $owner;
		$this->player = $player;
	}

	public function onRun($currentTick){
		$this->getOwner()->waitTask($this->player);
	}
	public function cancel(){
		$this->getHandler()->cancel();
	}
}
?>