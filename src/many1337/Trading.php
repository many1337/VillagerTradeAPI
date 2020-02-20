<?php

namespace many1337;

use pocketmine\entity\Entity;
use pocketmine\entity\Villager;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ActorEventPacket as EntityEventPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket as RemoveEntityPacket;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\network\mcpe\protocol\UpdateTradePacket;
use pocketmine\plugin\PluginBase;

class Trading extends PluginBase implements Listener{
	/** @var array */
	public $villagerId = [];

	/** @var Entity */
	public $en;

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getLogger()->notice("Please Wait... Repcies makeing♡");
	}

	public function onClick(PlayerInteractEvent $e){
		$p = $e->getPlayer();
		if($e->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			$this->villagerId[$p->getName()] = $eid = mt_rand(0xfffff, 0x7fffffff);
			$pk = new AddActorPacket();
			$pk->entityRuntimeId = $eid;
			$pk->type = Villager::NETWORK_ID;
			$pk->position = new Vector3();
			$pk->motion = new Vector3();
			$pk->yaw = $pk->pitch = 0;
			$pk->metadata = [
				Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, 1 << Entity::DATA_FLAG_IMMOBILE],
				Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0]
			];
			$p->dataPacket($pk);
			$pk = new UpdateTradePacket();
			$pk->windowId = WindowTypes::TRADING;
			$pk->tradeTier = 1;
			$pk->isWilling = false;
			$pk->traderEid = $eid;
			$pk->playerEid = $p->getId();
			$pk->displayName = "Trading";
			$writer = new NetworkLittleEndianNBTStream();
			$tags = new CompoundTag("Offers", [
						new ListTag("Recipes", [
						new CompoundTag("", [
						new ByteTag("rewardExp", 1),
						new IntTag("maxUses", mt_rand(2, 12)),
						new IntTag("uses", 0),
						Item::get(Item::DIAMOND)->nbtSerialize(-1, "buy"),
						Item::get(Item::PAPER)->nbtSerialize(-1, "sell")
			])
			])
			]);
			$pk->offers = $writer->write($tags);
			$p->dataPacket($pk);
		}
	}

	public function Entity(): Entity{
		return $this->en;
	}

	public function onDisconnect(PlayerQuitEvent $e){
		$p = $e->getPlayer();
		if(isset($this->villagerId[$p->getName()])){
			unset($this->villagerId[$p->getName()]);
		}
	}

	public function onPacket(DataPacketReceiveEvent $e){
		$player = $e->getPlayer();
		$pk = $e->getPacket();
		if($pk instanceof ContainerClosePacket){
			if($pk->windowId === 0xff and isset($this->villagerId[$player->getName()])){
				$pk = new RemoveEntityPacket();
				$pk->entityUniqueId = $eid = $this->villagerId[$player->getName()];
				$player->dataPacket($pk);
				unset($this->villagerId[$player->getName()]);
			}
		}
		if($pk instanceof EntityEventPacket){
			if($pk->event === 62){ //TRADING_TRANSACTION
				if(isset($this->villagerId[$player->getName()]) and $pk->entityRuntimeId === $this->villagerId[$player->getName()] and isset($this->tags[$player->getName()][$pk->data])){
					//TODO: make trading inventory
					$e->setCancelled();
				}
			}
		}
		if($pk instanceof InventoryTransactionPacket){
			if($pk->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY && isset($pk->trData->entityRuntimeId)){
				$entity = $player->level->getEntity($pk->trData->entityRuntimeId);
				if($entity instanceof Villager){
					//Open menu
				}
			}
		}
	}
}
