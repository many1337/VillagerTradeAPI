 VillagerTrade
-----------
**Items and NBT** 

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
# Finished & Planned Features
 - Villager
  - [X] Right Click to open Inventory
    - [X] Tap item and move to slot
    - [ ] Custom item config
  - [ ] Custom Villager Entity
-----------------------
***many1337 @2020***
