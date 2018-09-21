# VillagerTradeAPI

VillageTradeAPI plugin for Altay Software 1.2-1.6

# API


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
