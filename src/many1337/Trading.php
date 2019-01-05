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
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\network\mcpe\protocol\UpdateTradePacket;
use pocketmine\plugin\PluginBase;

class Trading extends PluginBase implements Listener{
	/** @var array */
	public $villagerId = [];
	/** @var array */
	public $recipes = [];

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getLogger()->notice("Please Wait... Repcies makeing♡");
	}

	public function makeRecipe(Item $buyA, Item $sell, Item $buyB = null){
		if($buyB === null){
			return new CompoundTag("", [
				$buyA->nbtSerialize(-1, "buyA"),
				new IntTag("maxUses", 32767),
				new ByteTag("rewardExp", 0),
				$sell->nbtSerialize(-1, "sell"),
				new IntTag("uses", 0),
			]);
		}else{
			return new CompoundTag("", [
				$buyA->nbtSerialize(-1, "buyA"),
				$buyB->nbtSerialize(-1, "buyB"),
				new IntTag("maxUses", 32767),
				new ByteTag("rewardExp", 0),
				$sell->nbtSerialize(-1, "sell"),
				new IntTag("uses", 0),
			]);
		}
	}

	public function onClick(PlayerInteractEvent $e){
		$p = $e->getPlayer();
		if($e->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			$this->villagerId[$p->getName()] = $eid = mt_rand(0xfffff, 0x7fffffff);
			$pk = new AddEntityPacket();
			$pk->entityRuntimeId = $eid;
			$pk->type = Villager::NETWORK_ID;
			$pk->position = $p->subtract(0, 4, 0);
			$pk->motion = new Vector3;
			$pk->yaw = $pk->pitch = 0;
			$pk->metadata = [
				Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, 1 << Entity::DATA_FLAG_IMMOBILE],
				Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0]
			];
			$p->dataPacket($pk);

			$tag = new CompoundTag("", [
				new ListTag("Recipes", []),
			]);
			$recipes = [[Item::get(Item::BRICK, 0, 1), Item::get(Item::SANDSTONE, 2, 2)], [Item::get(Item::BRICK, 0, 7), Item::get(Item::END_STONE, 0, 1)]];
			$this->recipes[$p->getName()] = $recipes;
			$i = 0;
			foreach($recipes as $recipe){
				$tag->Recipes[$i] = $this->makeRecipe($recipe[0], $recipe[1], $recipe[2] ?? null);
				++$i;
			}
			$nbt = new NBT;
			$nbt->setData($tag);

			$tr = new UpdateTradePacket;
			$tr->windowId = 2;
			$tr->varint1 = 0;
			$tr->varint2 = 0;
			$tr->isWilling = false;
			$tr->traderEid = $eid;
			$tr->playerEid = -1;
			$tr->displayName = "Shop";
			$tr->offers = $nbt->write(true);
			$p->dataPacket($tr);
		}
	}

	public function onDisconnect(PlayerQuitEvent $e){
		$p = $e->getPlayer();
		if(isset($this->villagerId[$p->getName()])){
			unset($this->villagerId[$p->getName()]);
			unset($this->recipes[$p->getName()]);
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
				unset($this->recipes[$player->getName()]);
			}
		}
		if($pk instanceof EntityEventPacket){
			if($pk->event === 62){ //TRADING_TRANSACTION
				if(isset($this->villagerId[$player->getName()]) and $pk->entityRuntimeId === $this->villagerId[$player->getName()] and isset($this->recipes[$player->getName()][$pk->data])){
					$recipe = $this->recipes[$player->getName()][$pk->data];
					//TODO: make trading inventory
					$e->setCancelled();
				}
			}
		}
		if($pk instanceof InventoryTransactionPacket){
			if($pk->transactionData->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY && isset($pk->transactionData->entityRuntimeId)){
				$entity = $player->level->getEntity($pk->transactionData->entityRuntimeId);
				if($entity instanceof Villager){
					//Open menu
				}
			}
		}
	}
}
