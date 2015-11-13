<?php

namespace thebigsmileXD\InstantRespawn;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\level\Level;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\entity\Effect;
use pocketmine\event\player\PlayerMoveEvent;

class Main extends PluginBase implements Listener{
	public $tasks = array();
	public $tasks2 = array();

	public function onLoad(){
		$this->getLogger()->info(TextFormat::GREEN . "Loading " . $this->getDescription()->getFullName());
	}

	public function onEnable(){
		$this->makeSaveFiles();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getLogger()->info(TextFormat::GREEN . "Enabling " . $this->getDescription()->getFullName() . " by " . $this->getDescription()->getAuthors()[0]);
	}

	private function makeSaveFiles(){
		$this->saveDefaultConfig();
		if(!$this->getConfig()->exists("messages") || empty($this->getConfig()->getAll()["messages"])){
			$this->getConfig()->setNested("messages", array("runingame" => "Please run this command ingame","messagetoplayer" => "You died","messagetoplayerrespawn" => "You respawned","killedbyunknown" => "{PLAYER} died","killedby" => "{PLAYER} was killed by {KILLER}",
					"killedbyprojectile" => "{PLAYER} was shot by {KILLER}"));
		}
		if(!$this->getConfig()->exists("teleport") || empty($this->getConfig()->getAll()["messages"])){
			$this->getConfig()->setNested("teleport", array("wait" => 0));
		}
		if(!$this->getConfig()->exists("level") || empty($this->getConfig()->getAll()["level"])){
			$this->getConfig()->setNested("level", array());
		}
		$this->setConfig();
	}

	public function setConfig(){
		$this->getConfig()->save();
		$this->getConfig()->reload();
	}

	public function onDisable(){
		$this->getServer()->getLogger()->info(TextFormat::RED . "Disabling " . $this->getDescription()->getFullName() . " by " . $this->getDescription()->getAuthors()[0]);
	}

	public function runIngame($sender){
		if($sender instanceof Player) return true;
		else{
			$sender->sendMessage(TextFormat::RED . $this->getConfig()->getNested("messages.runingame"));
			return false;
		}
	}

	public function getLevelByName($level){
		foreach($this->getServer()->getLevels() as $olevel){
			if(strtolower($olevel->getName()) === strtolower($level)){
				return $olevel;
			}
		}
		return false;
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		switch($command->getName()){
			case "instantrespawn":
				{
					$command = strtolower($command);
					if(count($args) > 0){
						switch($args[0]){
							case "add":
								{
									if(isset($args[1]) && $this->getLevelByName($args[1])){
										$this->addWorld($sender, $this->getLevelByName($args[1]));
										return true;
									}
									elseif(isset($args[1])){
										$sender->sendMessage(TextFormat::RED . "The world " . TextFormat::AQUA . $args[1] . TextFormat::RED . " doesn't exist, check case\n" . TextFormat::AQUA . $args[1] . TextFormat::RED . " must be a valid ManyWorld-World");
										return true;
									}
									else{
										$sender->sendMessage(TextFormat::RED . "Invalid arguments");
										return false;
									}
								}
							case "remove":
								{
									if(isset($args[1]) && $this->getLevelByName($args[1])){
										$this->removeWorld($sender, $this->getLevelByName($args[1]));
										return true;
									}
									elseif(isset($args[1])){
										$sender->sendMessage(TextFormat::RED . "The world " . TextFormat::AQUA . $args[1] . TextFormat::RED . " doesn't exist\n" . TextFormat::AQUA . $args[1] . TextFormat::RED . " must be a valid ManyWorld-World");
										return true;
									}
									else{
										$sender->sendMessage(TextFormat::RED . "Invalid arguments");
										return false;
									}
								}
							case "list":
								{
									$this->listWorlds($sender);
								}
							case "ls":
								{
									$this->listWorlds($sender);
								}
							default:
								return false;
						}
					}
					else
						return false;
				}
			default:
				return false;
		}
	}

	public function waitTask(Player $player){
		if($player->isOnline()){
			$this->spawnPlayer($player, $player->getLevel());
		}
		else{
			$this->getServer()->getScheduler()->cancelTask($this->tasks[$player->getName()]);
			$this->getServer()->getScheduler()->cancelTask($this->tasks2[$player->getName()]);
			unset($this->tasks[$player->getName()]);
		}
	}

	public function spawnPlayer(Player $player, Level $level){
		$this->getServer()->getScheduler()->cancelTask($this->tasks2[$player->getName()]);
		unset($this->tasks[$player->getName()]);
		$message = str_replace("{PLAYER}", $player->getDisplayName(), TextFormat::RED . $this->getConfig()->getNested("messages.messagetoplayerrespawn"));
		$player->getInventory()->setHotbarSlotIndex(0, 0);
		$player->getInventory()->setHotbarSlotIndex(1, 1);
		$player->getInventory()->setHotbarSlotIndex(2, 2);
		$player->getInventory()->setHotbarSlotIndex(3, 3);
		$player->getInventory()->setHotbarSlotIndex(4, 4);
		$player->getInventory()->setHotbarSlotIndex(5, 5);
		$player->getInventory()->setHotbarSlotIndex(6, 6);
		$player->sendTip($message);
		$this->getServer()->getScheduler()->cancelTask($this->tasks[$player->getName()]);
		return true;
	}

	public function addWorld($sender, Level $level){
		if($level instanceof Level){
			if($this->getConfig()->exists("level." . $level->getName())){
				$sender->sendMessage(TextFormat::RED . "World " . TextFormat::AQUA . $level->getName() . TextFormat::RED . " is already set");
				return true;
			}
			else{
				if(empty($this->getConfig()->getAll()["level"])) $this->getConfig()->set("level", strtolower($level->getName()));
				else $this->getConfig()->setNested("level", array_push(array(array_keys($this->getConfig()->getAll()["level"]),strtolower($level->getName()))));
				$this->setConfig();
				if(!empty($this->getConfig()->getAll()["level"][strtolower($level->getName())])){
					$sender->sendMessage(TextFormat::GREEN . "World " . TextFormat::AQUA . $level->getName() . TextFormat::GREEN . " successfully added");
					return true;
				}
				else{
					$sender->sendMessage(TextFormat::RED . "Error while adding world " . TextFormat::AQUA . $level->getName() . "\n" . TextFormat::RED . "Error: Wasn't able to add world to config");
					return false;
				}
			}
		}
		else{
			$sender->sendMessage(TextFormat::RED . "World " . TextFormat::AQUA . $level->getName() . TextFormat::RED . " is not a level");
			return false;
		}
	}

	public function removeWorld($sender, Level $level){
		if(!empty($this->getConfig()->getAll()["level"][strtolower($level->getName())])){
			$this->getConfig()->remove("level." . strtolower($level->getName()));
			$this->setConfig();
			$sender->sendMessage(TextFormat::GREEN . "World " . TextFormat::AQUA . $level->getName() . TextFormat::GREEN . " successfully removed");
			return true;
		}
		else{
			$sender->sendMessage(TextFormat::RED . "World " . TextFormat::AQUA . $level->getName() . TextFormat::RED . " is not set in config");
			return false;
		}
	}

	public function listWorlds($sender){
		if(!$this->getConfig()->exists("level") && empty($this->getConfig()->getAll()["level"])){
			$message = TextFormat::RED . "No worlds set. Use " . TextFormat::AQUA . "/instantrespawn addworld <name>" . TextFormat::RED . " to add one.";
		}
		else{
			$message = TextFormat::GREEN . "Following worlds use InstantRespawn:\n" . TextFormat::AQUA;
			$message .= implode(", ", array_keys($this->getConfig()->getAll()["level"]));
		}
		$sender->sendMessage($message);
		return true;
	}

	public function damageHandler(EntityDamageEvent $event){
		$entity = $event->getEntity();
		$cause = $event->getCause();
		$message = $this->getConfig()->getNested("messages.killedbyunknown");
		if($entity instanceof Player && !empty($this->getConfig()->getAll()["level"][$entity->getLevel()->getName()])){
			if($cause == EntityDamageEvent::CAUSE_ENTITY_ATTACK){
				if($event instanceof EntityDamageByEntityEvent){
					$killer = $event->getDamager();
					if($killer instanceof Player){
						$message = $killer->getName();
						if($event->getDamage() >= $entity->getHealth()){
							$event->setCancelled(true);
							$this->killHandler($entity, $killer);
							$message2 = str_replace("{PLAYER}", $entity->getName(), str_replace("{KILLER}", $message, $this->getConfig()->getNested("messages.killedby")));
							$this->getServer()->broadcastMessage($message2);
						}
					}
					else{
						$message = $killer->getName();
						if($event->getDamage() >= $entity->getHealth()){
							$event->setCancelled(true);
							$this->killHandler($entity, $killer);
							$message2 = str_replace("{PLAYER}", $entity->getName(), $message);
							$this->getServer()->broadcastMessage($message2);
						}
					}
				}
			}
			elseif($cause == EntityDamageEvent::CAUSE_PROJECTILE){
				if($event instanceof EntityDamageByChildEntityEvent){
					$killer = $event->getDamager();
					if($killer instanceof Player){
						if($event->getDamage() >= $entity->getHealth()){
							$message2 = str_replace("{PLAYER}", $entity->getName(), str_replace("{KILLER}", $killer->getName(), $this->getConfig()->getNested("messages.killedbyprojectile")));
							$this->getServer()->broadcastMessage($message2);
							$event->setCancelled(true);
							$this->killHandler($entity, $killer);
						}
					}
				}
			}
			else{
				if($event->getDamage() >= $entity->getHealth()){
					$event->setCancelled(true);
					$this->killHandler($entity);
					$message2 = str_replace("{PLAYER}", $entity->getName(), $this->getConfig()->getNested("messages.died"));
					$this->getServer()->broadcastMessage($message2);
				}
			}
		}
	}

	public function killHandler(Player $entity){
		$this->tasks[$entity->getName()] =$this->getServer()->getScheduler()->scheduleDelayedTask( new Wait($this, $entity), $this->getConfig()->getNested("teleport.wait") * 20)->getTaskId();
		$entity->teleport($entity->getLevel()->getSafeSpawn());
		$this->tasks2[$entity->getName()] = $this->getServer()->getScheduler()->scheduleRepeatingTask(new SendTip($this, $this->tasks2), 1)->getTaskId();
		$effect = Effect::getEffectByName("SLOWNESS");
		$effect->setVisible(false);
		$effect->setAmplifier(99);
		$effect->setDuration($this->getConfig()->getNested("teleport.wait") * 20);
		$entity->addEffect($effect);
		/*
		 * $effect = Effect::getEffectByName("BLINDNESS); //Blindness
		 * $effect->setVisible(false);
		 * $effect->setAmplifier(254);
		 * $effect->setDuration($this->getConfig()->getNested("teleport.wait") * 20);
		 * $entity->addEffect($effect);
		 */
	}

	public function cancelMoveOnDeath(PlayerMoveEvent $event){
		if(in_array($event->getPlayer()->getName(), array_keys($this->tasks))){
			$event->setCancelled();
		}
		return;
	}
}