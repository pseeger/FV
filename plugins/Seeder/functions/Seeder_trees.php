<?php
//========================================================================================================================
//Seeder_trees.php
//by N1n9u3m
//========================================================================================================================
//Seeder_MakeTrees
//========================================================================================================================
function Seeder_MakeTrees()//added v1.1.5
{

global $Seeder_settings;

$trees_all = Units_GetByType("tree", true);
$trees_all = Seeder_ArrayFilter($trees_all, 'subtype', '<>', 'consumable');

$tree_array = array();
$mastery_counters =  unserialize(file_get_contents(F('cropmastery.txt')));
$mastery_levels = unserialize(file_get_contents(F('cropmasterycount.txt')));
$ingiftbox = unserialize(file_get_contents(F('ingiftbox.txt')));

$localOffset = date('Z');// fixed 1.1.3b
$serverOffset = (-5 * 60 * 60);//(GMT -5:00) EST - Eastern Standard Time (U.S. & Canada)

foreach($trees_all as $tree)
{

$name = $tree['name'];
$code = $tree['code'];
$tree_array[$code] = $tree;

$mastery_count = @$mastery_counters[$code]; if (!$mastery_count) {$mastery_count = 0;}
$tree_array[$code]['mastery_count'] = $mastery_count;
$mastery_level = @$mastery_levels[$code]; if (!$mastery_level) {$mastery_level = 0;} else {$mastery_level += 1;}
$tree_array[$code]['mastery_level'] = $mastery_level;

if ($tree['masterymax'] > 0)
{
$to_mastery = ($tree['masterymax'] - $mastery_count); if ($to_mastery < 0){$to_mastery = 0;}
$tree_array[$code]['masterymax'] = $tree['masterymax'];
$tree_array[$code]['to_mastery'] = $to_mastery;
} else {
$tree_array[$code]['masterymax'] = 0;
$tree_array[$code]['to_mastery'] = 0;
}

 if ($tree['limitedStart'])
 {
 $limitedStartTimestamp = strtotime($tree['limitedStart']);
 $limitedStartOffset = ($limitedStartTimestamp - $serverOffset + $localOffset);
 $tree_array[$code]['limitedStartTimestamp'] = $limitedStartOffset;
 $tree_array[$code]['limitedStart'] = date($Seeder_settings['timeformat'],$limitedStartOffset);
 } else {
 $tree_array[$code]['limitedStartTimestamp'] = (time() - 100000000);
 $tree_array[$code]['limitedStart'] = 'NULL';
 }
 
 if ($tree['limitedEnd'])
 {
 $limitedEndTimestamp = strtotime($tree['limitedEnd']);
 $limitedEndOffset = ($limitedEndTimestamp - $serverOffset + $localOffset);
 $tree_array[$code]['limitedEndTimestamp'] = $limitedEndOffset;
 $tree_array[$code]['limitedEnd'] = date($Seeder_settings['timeformat'],$limitedEndOffset);
 } else {
 $tree_array[$code]['limitedEndTimestamp'] = (time() + 100000000);
 $tree_array[$code]['limitedEnd'] = 'NULL';
 }

 if ($tree['creationDate'])
 {
 $creationDateTimestamp = strtotime($tree['creationDate']);
 $creationDateOffset = ($creationDateTimestamp - $serverOffset + $localOffset);
 $tree_array[$code]['creationDateTimestamp'] = $creationDateOffset;
 $tree_array[$code]['creationDate'] = date($Seeder_settings['timeformat'],$creationDateOffset);
 } else {
 $tree_array[$code]['creationDateTimestamp'] = (time() - 100000000);
 $tree_array[$code]['creationDate'] = 'NULL';
 }

if ($tree['subtype']) {$tree_array[$code]['subtype'] = $tree['subtype'];} else {$tree_array[$code]['subtype'] = "NULL";}
if ($tree['iphoneonly']) {$tree_array[$code]['locked'] = "iPhone";} else {$tree_array[$code]['locked'] = "NULL";}
if ($tree['market']) {$tree_array[$code]['market'] = $tree['market'];} else {$tree_array[$code]['market'] = "NULL";}
if ($tree['requiredLevel']) {$tree_array[$code]['requiredLevel'] = $tree['requiredLevel'];} else {$tree_array[$code]['requiredLevel'] = 1;}
#if ($tree['giftable']) {$tree_array[$code]['giftable'] = $tree['giftable'];} else {$tree_array[$code]['giftable'] = "NULL";}
#if ($tree['buyable']) {$tree_array[$code]['buyable'] = $tree['buyable'];} else {$tree_array[$code]['buyable'] = "NULL";}
if (($tree['giftable'] == "false") && ($tree['buyable'] == "false")) {$tree_array[$code]['reserved'] = 1;} else {$tree_array[$code]['reserved'] = 0;}

$growTime = round(($tree['growTime']) * 23, 0);
$tree_array[$code]['growTime'] = $growTime;
$tree_array[$code]['profit_time'] = round(($tree['coinYield'] / $growTime), 2);
$tree_array[$code]['profit_orchard'] = round(($tree['coinYield'] / 48), 2);

$tree_array[$code]['seedling'] = 0;
$tree_array[$code]['orchard'] = 0;
$tree_array[$code]['farm'] = 0;

$giftbox = @$ingiftbox[$code]; if (!$giftbox) {$giftbox = 0;}
$tree_array[$code]['giftbox'] = $giftbox;
$tree_array[$code]['amount'] = $giftbox;

}

unset($trees_all);unset($mastery_counters);unset($mastery_levels);unset($ingiftbox);

//======================================
$objectsArray = unserialize(file_get_contents(F('objects.txt')));

foreach($objectsArray as $object)
{

 if ($object['className'] == 'MysterySeedling')
 {
 $code = $object['seedType'];
 $tree_array[$code]['seedling'] += 1;
 $tree_array[$code]['amount'] += 1;
 }

 if ($object['className'] == 'Tree')
 {
 $code = Units_GetCodeByName($object['itemName']);
 $tree_array[$code]['farm'] += 1;
 $tree_array[$code]['amount'] += 1;
 }

 if ($object['className'] == 'OrchardBuilding')
 {
  if (sizeof($object['contents']) > 0 )
  {
   foreach($object['contents'] as $content)
   {
    if ($content['numItem'] > 0 )
    {
    $code = $content['itemCode'];
    $tree_array[$code]['orchard'] += $content['numItem'];
    $tree_array[$code]['amount'] += $content['numItem'];
    }
   }
  }
 }

}
//======================================

unset($objectsArray);
return $tree_array;

}
//========================================================================================================================
//Seeder_MakeOrchards
//========================================================================================================================
function Seeder_MakeOrchards()//added v1.1.6
{

$orchards = GetObjects('OrchardBuilding');
$orchards = Seeder_ArrayFilter($orchards, 'isFullyBuilt', '==', 1);
$mastery_counters =  unserialize(file_get_contents(F('cropmastery.txt')));
$mastery_levels = unserialize(file_get_contents(F('cropmasterycount.txt')));

$orchards_array = array();

foreach($orchards as $orchard)
{
 $id = $orchard['id'];
 $amount = 0;
 $contents = array();
 $trees_mastery = 0;

 if (sizeof($orchard['contents']) > 0 )
 {
  foreach($orchard['contents'] as $key => $content)
  {
   if ($content['numItem'] > 0 )
   {
   $amount += $content['numItem'];
   $itemCode = $content['itemCode'];
   $contents[$key]['itemCode'] = $itemCode;
   $contents[$key]['numItem'] = $content['numItem'];
   $tree = Units_GetUnitByCode($itemCode, true);
   $contents[$key]['itemName'] = $tree['name'];
   $contents[$key]['realname'] = $tree['realname'];
   $contents[$key]['iconurl'] = $tree['iconurl'];

   $mastery_count = @$mastery_counters[$itemCode]; if (!$mastery_count) {$mastery_count = 0;}
   $contents[$key]['mastery_count'] = $mastery_count;
   $mastery_level = @$mastery_levels[$itemCode]; if (!$mastery_level) {$mastery_level = 0;} else {$mastery_level += 1;}
   $contents[$key]['mastery_level'] = $mastery_level;

    if ($tree['masterymax'] > 0)
    {
    $to_mastery = ($tree['masterymax'] - $mastery_count);
     if ($to_mastery < 0)
     {
     $to_mastery = 0;
     } else {
     $trees_mastery = 1;
     }
    $contents[$key]['masterymax'] = $tree['masterymax'];
    $contents[$key]['to_mastery'] = $to_mastery;
    } else {
    $contents[$key]['masterymax'] = 0;
    $contents[$key]['to_mastery'] = 0;
    }

   }//if ($content['numItem'] > 0 )
  }
 }

 $orchard['contents'] = $contents;
 $orchard['amount'] = $amount;
 $orchard['trees_mastery'] = $trees_mastery;
 $orchards_array[$id] = $orchard;

}

unset($orchards);unset($mastery_counters);unset($mastery_levels);
return $orchards_array;

}
//========================================================================================================================
//Seeder_TreeMastery
//based on code: ToolBox by Hypothalamus TB_run_animalmanager_farmGold()
//========================================================================================================================
function Seeder_masteryTrees()
{
$T = time(true);
AddLog2("Seeder_masteryTrees> start");

global $Seeder_settings, $Seeder_info;

//======================================

	if(AM_farmGold_getIDs() <> 0)
    {
		if(!isset($farm)) {
			AddLog2('TB - reloding farm');
			DoInit();
			$farm = TB_buildFarmArray();
		}
		$info = AM_farmGold_getIDs();
		$re = AM_farmGold_restore($info, $farm);
		if(is_array($re)) $farm = $re;
		AM_farmGold_deletIDs();

		//reloding farm
		AddLog2('TB - animals restored');
		AddLog2('TB - reloding farm');
		DoInit();
		$farm = TB_buildFarmArray();
	}

//======================================

$tree_cycles = $Seeder_settings['tree_cycles'];
$orchards = AM_loadBuildingObject("OrchardBuilding", false);

 $cycle = 0;
 $skip = false;
 
 while($cycle < $tree_cycles && !$skip)
 {

  if(!isset($farm))
  {
  AddLog2('TB - reloding farm');
  DoInit();
  $farm = TB_buildFarmArray();
  }
				
  AddLog2("Seeder_masteryTrees> Cycle ".($cycle + 1)."/".$tree_cycles." start");
  foreach($orchards as $orchard)
  {
  AddLog2("Seeder_masteryTrees> Orchard: ".$orchard['id']." (".$orchard['position']['x'] . '-' . $orchard['position']['y'].")");


//======================================
                    $re = Seeder_TreeMasteryRun($orchard, $farm);

					if(is_array($re)) $farm = $re;
					elseif($re == false)
                    {
						Addlog2('++ ERROR ++');
						AddLog2('TB - reloding farm');
						DoInit();
						$farm = TB_buildFarmArray();
						$info = AM_farmGold_getIDs();
						$re = AM_farmGold_restore($info, $farm);
						if(is_array($re))
                        {
							AM_farmGold_deletIDs();
							$farm = $re;
							AddLog2('TB - animals restored');
						} elseif($re == false) {
                            Seeder_error("Error while restoring!"."\n"."skipping other orchards");
							$skip = true;
						}
					}
//======================================

  }//foreach($orchards as $orchard)
  $cycle += 1;

 }//while($cycle < $tree_cycles && !$skip)


$T2 = time();
$T2 -= $T;
AddLog2("Seeder_masteryTrees> end ".$T2." Secs.");
}

//========================================================================================================================
//Seeder_TreeMasteryRun()
//based on code: ToolBox by Hypothalamus AM_farmGold()
//========================================================================================================================
function Seeder_TreeMasteryRun($orchard,$getFarm)
{

	$re = array();
	$IDs = array();
	$positions = array();
	$animalsInBuilding = Seeder_TreeMasteryGetTrees($orchard);
	$cInBuilding = 0;
	$farm = $getFarm;
	$itemName = array();

	foreach($animalsInBuilding as $animal => $num)
    {
		$cInBuilding += $num;
	}
	if($cInBuilding == 0) return $farm;

	$saveInBuilding = $cInBuilding;

		if ($cInBuilding == 1)
        {
			AddLog2('moving ' . ($cInBuilding) . ' trees to farm');
			$re = AM_farmGold_moveAnimalsToFarm($orchard, $cInBuilding, $farm);
		}
		else {
			AddLog2('moving ' . ($cInBuilding - 1) . ' trees to farm (leaving 1 in the Orchard)');
			$re = AM_farmGold_moveAnimalsToFarm($orchard, $cInBuilding - 1, $farm);
		}


	if($re == false) {
		echo "\$re ist false \r\n";
		return false;
	} else {
		if(@is_array($re['farm']) && @is_array($re['IDs']) && (@is_array($re['itemName']) && @is_array($re['positions'])))
        {
			$farm = $re['farm'];
			$IDs = $re['IDs'];
			$itemName = $re['itemName'];
			$positions = $re['positions'];
		} else {
			echo "nicht alles arrays sind gesetzt\r\n";
			return false;
		}
	}

				AddLog2('harvesting ' . ($saveInBuilding) . ' trees on farm');
				AM_farmGold_harvestonfarm($IDs, $itemName, $positions);

				AddLog2('moving (' . ($saveInBuilding) . ') trees into building');
				$re = AM_farmGold_moveAnimalsToBuilding($orchard, ($saveInBuilding), $farm, $IDs, $itemName, $positions);

		if(@is_array($re)){ $farm = $re;}
		else {return false;}

	AM_farmGold_deletIDs();
	return $farm;

}
//========================================================================================================================
//Seeder_TreeMasteryGetTrees()
//based on code: ToolBox by Hypothalamus AM_animalInBuilding()
//========================================================================================================================
function Seeder_TreeMasteryGetTrees($orchard)
{
$arr = array();
$mastery_counters =  unserialize(file_get_contents(F('cropmastery.txt')));

 foreach($orchard['contents'] as $unit)
 {
 $code = $unit['itemCode'];
 $tree = Units_GetUnitByCode($itemCode, true);
 $mastery_count = @$mastery_counters[$code]; if (!$mastery_count) {$mastery_count = 0;}

  if ($tree['masterymax'] > 0)
  {
  $to_mastery = ($tree['masterymax'] - $mastery_count);
   if ($to_mastery > 0)
   {
   $arr[$tree['name']] = $unit['numItem'];
   }
  }
 }

 ksort($arr);
 return $arr;

}
//========================================================================================================================

?>
