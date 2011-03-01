<?php
//========================================================================================================================
//Seeder_loadWorld.php
//by N1n9u3m
//========================================================================================================================
//Seeder_loadWorld
//========================================================================================================================
function Seeder_loadWorld()//fixed v1.1.2
{
$T = time(true);
AddLog2("Seeder_loadWorld> start");

//======================================
global $userId;

$px_time = time();
$amf = new AMFObject("");
$amf->_bodys[0] = new MessageBody();
$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
$amf->_bodys[0]->responseURI = '/1/onStatus';
$amf->_bodys[0]->responseIndex = '/1';
$amf->_bodys[0]->_value[0] = GetAMFHeaders();
$amf->_bodys[0]->_value[2] = 0;

$amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
$amf->_bodys[0]->_value[1][0]['params'] = Array();
$amf->_bodys[0]->_value[1][0]['params'][0] = '-1';
$amf->_bodys[0]->_value[1][0]['functionName'] = "SocialMissionService.onGetActiveMission";
#$amf->_bodys[0]->_value[1][0]['functionName'] = "SocialMissionService.onGetAllMissionData";

$amf->_bodys[0]->_value[1][1]['sequence'] = GetSequense();
$amf->_bodys[0]->_value[1][1]['params'] = Array();
$amf->_bodys[0]->_value[1][1]['params'][0] = $userId;
$amf->_bodys[0]->_value[1][1]['functionName'] = "UserService.postInit";

SaveAuthParams();

$serializer = new AMFSerializer();
$result = $serializer->serialize($amf);
$s = Connect();
$answer = Request($s, $result);
@fclose($s);

$amf2 = new AMFObject($answer);
$deserializer2 = new AMFDeserializer($amf2->rawData);
$deserializer2->deserialize($amf2);

//======================================

$amf_error = 0;
if (!isset($amf2->_bodys[0]->_value['data'][0]))
{
$amf_error = "SocialMissionService.onGetAllMissionData"."\n"."BAD AMF REPLY (OOS?)";
}
if (isset($amf2->_bodys[0]->_value['data'][0]['errorType']) && ($amf2->_bodys[0]->_value['data'][0]['errorType'] != 0))
{
$amf_error = "SocialMissionService.onGetAllMissionData"."\n".$amf2->_bodys[0]->_value['data'][1]['amf_error'];
}
if (!isset($amf2->_bodys[0]->_value['data'][1]))
{
$amf_error = "UserService.postInit"."\n"."BAD AMF REPLY (OOS?)";
}
if (isset($amf2->_bodys[0]->_value['data'][1]['errorType']) && ($amf2->_bodys[0]->_value['data'][1]['errorType'] != 0))
{
$amf_error = "UserService.postInit"."\n".$amf2->_bodys[0]->_value['data'][1]['amf_error'];
}

if ($amf_error == 0)
{

Seeder_Write(@$amf2->_bodys[0]->_value['data'][0]['data'],"activeMission");
AddLog2("Seeder_loadWorld> Active Co-Op Job updated");
Seeder_Write(@$amf2->_bodys[0]->_value['data'][1]['data']['stats'],"stats");
AddLog2("Seeder_loadWorld> Stats updated");
Seeder_Write(@$amf2->_bodys[0]->_value['data'][1]['data']['marketView'],"market");
AddLog2("Seeder_loadWorld> Market updated");
#Seeder_Write(@$amf2->_bodys[0]->_value['data'][1]['data']['craftingState'],"crafting");
#AddLog2("Seeder_loadWorld> crafting updated");
$greenhouse = @$amf2->_bodys[0]->_value['data'][1]['data']['breedingState'][0];
Seeder_Write($greenhouse,"greenhouse");//added 1.1.6
AddLog2("Seeder_loadWorld> Greenhouse updated");
#Seeder_Write(@$amf2->_bodys[0]->_value['data'][1]['data'],"postInit");

$MarketStallCount = @$amf2->_bodys[0]->_value['data'][1]['data']['craftingState']['currentMarketStallCount'];
$crafting_items = @$amf2->_bodys[0]->_value['data'][1]['data']['craftingState']['craftingItems'];
$maxbushels = @$amf2->_bodys[0]->_value['data'][1]['data']['craftingState']['maxCapacity'];
$pendingRewards = @$amf2->_bodys[0]->_value['data'][1]['data']['craftingState']['pendingRewards'];

//======================================
//plots
//======================================
$plots_array = array();
$all_plots = GetObjects('Plot');
$n_plots = count($all_plots);

foreach ($all_plots as $plot)
{
 if (($plot['state'] == 'grown') || ($plot['state'] == 'ripe') || ($plot['state'] == 'planted'))
 {
  $plot_name = $plot['itemName'];
  if (!isset($plots_array[$plot_name]['name']))
  {
  $plots_array[$plot_name]['name'] = $plot_name;
  $plots_array[$plot_name]['count'] = 1;
  }
  else
  {
  $plots_array[$plot_name]['count'] += 1;
  }
 }
}

unset($all_plots);
AddLog2("Seeder_loadWorld> ".$n_plots." Plots updated");

//======================================
//craftingItems
//======================================
$crafting_array = array();
$n_bushels = 0;

if(is_array($crafting_items)) foreach ($crafting_items as $bushel)
{
 $bushel_code = $bushel['itemCode'];
 $crafting_array[$bushel_code]['bushel_code'] = $bushel_code;
 $crafting_array[$bushel_code]['bushel_count'] = $bushel['quantity'];
 $n_bushels += $bushel['quantity'];
}
unset($crafting_items);unset($bushel_code);

$units_bushel = Units_GetByType('bushel',true);
$bushels_array = array();

foreach($units_bushel as $unit)
{
 $bushel_crop = Units_GetNameByCode($unit['crop']);
 $bushels_array[$bushel_crop]['bushel_crop']  = $bushel_crop;
 $bushels_array[$bushel_crop]['bushel_name'] = $unit['name'];
 $bushels_array[$bushel_crop]['bushel_code'] = $unit['code'];
 $bushels_array[$bushel_crop]['bushel_iconurl'] = $unit['iconurl'];
 $bushel_count = @$crafting_array[$unit['code']]['bushel_count']; if (!$bushel_count) {$bushel_count = 0;}
 $bushels_array[$bushel_crop]['bushel_count'] = $bushel_count;
}

unset($crafting_array);unset($units_bushel);
AddLog2("Seeder_loadWorld> ".$n_bushels." Bushels updated");

//======================================
//pendingRewards 1.1.4
//======================================
$Rewards = 0;
if (count($pendingRewards) > 0)
 {
 foreach ($pendingRewards as $Reward)
 {
 $Rewards += $Reward['count'];
 }
}
$pendingRewards = $Rewards;

//======================================
//masteries
//======================================
$world = unserialize(file_get_contents(F('world.txt')));
$mastery_counters = @$world['data'][0]['data']['userInfo']['player']['masteryCounters'];
$mastery_levels = @$world['data'][0]['data']['userInfo']['player']['mastery'];


//[seenFlags][greenhousebuildable_finished_t] => 1
//[expansionLevel] => 2
//[state] => built
//======================================
//licenses
//======================================
$licenses = @$world['data'][0]['data']['licenses'];

//======================================
//seedpackages
//======================================
$inconbox = unserialize(file_get_contents(F('inconbox.txt')));

//======================================
//green house trays
//======================================
$tray_array = array();

for ($i = 0; $i < count($greenhouse['trays']); $i++)
{
$tray_code = @$greenhouse['trays'][$i]['trayResult'];

 if ($tray_code)
 {
 $tray_array[$tray_code]['tray_code'] = $tray_code;
 $tray_array[$tray_code]['tray_quant'] = @$greenhouse['trays'][$i]['tray']['itemIngredients'][0]['quantity'];
 }

}

//======================================
//seeds requirements
//======================================
list($userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy) = explode(';', file_get_contents(F('params.txt')));
$items_xml = "./farmville-xml/".$flashRevision."_items.xml";
$reqs_array = array();

if (file_exists($items_xml))
{

$xml_doc = simplexml_load_file($items_xml);

 foreach ($xml_doc->items->item as $item_xml)
 {
 $xml_item_name = (string)$item_xml['name'];
 $xml_item_type = (string)$item_xml['type'];

  if ($xml_item_type == "seed")
  {
   foreach ($item_xml->children() as $seed_param => $seed_value)
   {
    if ($seed_param == "requirements")
    {
    $reqs_array[$xml_item_name] = array();
    $n_reqs = 0;
     foreach($seed_value->children() as $seed_req_param => $seed_req_value)
     {
      foreach($seed_req_value->attributes() as $req_classname => $req_value)
      {
       if ($req_classname == "name")
       {
       $reqs_array[$xml_item_name][$n_reqs] = (string)$req_value;
       $n_reqs += 1;
       }
      }
     }
    }
   }
  }//if ($item_type == "seed")
 }
unset($xml_doc);

}//if (file_exists($items_xml)

//======================================
//seeds
//======================================
$units_seeds = Units_GetByType('seed',true);
$seeds_array = array();

//Timezone added 1.2.3
global $Seeder_settings;
$Seeder_settings = Seeder_Read("settings");
$localOffset = date('Z');// fixed 1.1.3b
//$serverOffset = -(Units_GetFarming('globalServerUTCOffsetHours') * 60 * 60);//(GMT -5:00) EST - Eastern Standard Time (U.S. & Canada)
$serverOffset = (-5 * 60 * 60);//(GMT -5:00) EST - Eastern Standard Time (U.S. & Canada)

//booster fixed 1.1.4
$booster_crop = @$world['data'][0]['data']['userInfo']['player']['buffs']['BBushel']['crop'];
$booster_time = @$world['data'][0]['data']['userInfo']['player']['buffs']['BBushel']['time'];
$booster_type = @$world['data'][0]['data']['userInfo']['player']['buffs']['BBushel']['type'];
$booster_time = ($booster_time + (2 * 60 * 60));//+ 2 hours

foreach($units_seeds as $seed)
{
 $name = $seed['name'];
 $seed_code = $seed['code'];
 $seeds_array[$name]['name'] = $name;
 $seeds_array[$name]['code'] = $seed_code;

 //default fields
 if (@$seed['realname']){$seeds_array[$name]['realname'] = Seeder_replace($seed['realname']);} else {$seeds_array[$name]['realname'] = $name;}//Chinese Fix
 if ($seed['buyable'] == 'true'){$seeds_array[$name]['buyable'] = 1;} else {$seeds_array[$name]['buyable'] = 0;}
 if ($seed['iconurl']){$seeds_array[$name]['iconurl'] = $seed['iconurl'];} else {$seeds_array[$name]['iconurl'] = Seeder_imgURL.'space.png';}
 if ($seed['cost']){$seeds_array[$name]['cost'] = $seed['cost'];} else {$seeds_array[$name]['cost'] = 0;}
 if ($seed['growTime']){$seeds_array[$name]['growTime'] = round(($seed['growTime']) * 23, 0);} else {$seeds_array[$name]['growTime'] = 0;}
 if ($seed['coinYield']){$seeds_array[$name]['coinYield'] = $seed['coinYield'];} else {$seeds_array[$name]['coinYield'] = 0;}
 if ($seed['plantXp']){$seeds_array[$name]['plantXp'] = $seed['plantXp'];} else {$seeds_array[$name]['plantXp'] = 0;}
 if (@$seed['masterymax']){$seeds_array[$name]['masterymax'] = $seed['masterymax'];} else {$seeds_array[$name]['masterymax'] = 0;}
 if ($seed['requiredLevel']){$seeds_array[$name]['requiredLevel'] = $seed['requiredLevel'];} else {$seeds_array[$name]['requiredLevel'] = 0;}

 //gain
 $seeds_array[$name]['profit'] = ($seeds_array[$name]['coinYield'] -($seeds_array[$name]['cost'] + 15));
 $seeds_array[$name]['profit_time'] = number_format(($seeds_array[$name]['profit'] / $seeds_array[$name]['growTime']), 2, '.', '');
 $seeds_array[$name]['xp_time'] = number_format((($seeds_array[$name]['plantXp'] + 1) / $seeds_array[$name]['growTime']), 2, '.', '');

 //masteries
 $mastery_count = @$mastery_counters[$seed_code]; if (!$mastery_count) {$mastery_count = 0;}
 $seeds_array[$name]['mastery_count'] = $mastery_count;
 $mastery_level = @$mastery_levels[$seed_code]; if (!$mastery_level) {$mastery_level = 0;} else {$mastery_level += 1;}
 $seeds_array[$name]['mastery_level'] = $mastery_level;

 //plots
 $plots_planted = @$plots_array[$name]['count']; if (!$plots_planted) {$plots_planted = 0;}
 $seeds_array[$name]['plots_planted'] = $plots_planted;

 //tomastery & mastery_time
 $to_mastery = ($seeds_array[$name]['masterymax'] - $mastery_count - $plots_planted); if ($to_mastery < 0){$to_mastery = 0;}
 $seeds_array[$name]['to_mastery'] = $to_mastery;
 $seeds_array[$name]['mastery_time'] = number_format((($to_mastery * $seeds_array[$name]['growTime']) / $n_plots), 2, '.', '');

 //bushels
 $bushel_name = @$bushels_array[$name]['bushel_name'];
 if (!$bushel_name)
 {
 $seeds_array[$name]['bushel_name'] = "NULL";
 $seeds_array[$name]['bushel_code'] = "NULL";
 $seeds_array[$name]['bushel_iconurl'] = "NULL";
 $seeds_array[$name]['bushel_count'] = 0;
 } else {
 $seeds_array[$name]['bushel_name'] = $bushel_name;
 $seeds_array[$name]['bushel_code'] = @$bushels_array[$name]['bushel_code'];
 $seeds_array[$name]['bushel_iconurl'] = @$bushels_array[$name]['bushel_iconurl'];
 $seeds_array[$name]['bushel_count'] = @$bushels_array[$name]['bushel_count'];
 }

 //active bushel fixed 1.1.4
 if ($seeds_array[$name]['code'] == $booster_crop)
 {
 $seeds_array[$name]['booster_time'] = $booster_time;
 } else {
 $seeds_array[$name]['booster_time'] = 0;
 }

 //isHybrid added 1.1.6
 if (@$seed['isHybrid'])
 {
 $seeds_array[$name]['isHybrid'] = 1;
 } else {
 $seeds_array[$name]['isHybrid'] = 0;
 }
 
 //seedpackage
 if (@$seed['seedpackage'])
 {
 $seeds_array[$name]['seedpackage_name'] = $seed['seedpackage'];
 $seedpackage_code = Units_GetCodeByName($seed['seedpackage']);//hybrid added 1.1.6
 $seeds_array[$name]['seedpackage_code'] = $seedpackage_code;
 $seeds_array[$name]['seedpackage_realname'] = Units_GetRealnameByName($seed['seedpackage']);
 $seedpackage_count = @$inconbox[$seedpackage_code]; if (!$seedpackage_count) {$seedpackage_count = 0;}
 $seeds_array[$name]['seedpackage_count'] = $seedpackage_count;

  //isHybrid added 1.1.6
#  if (@$seed['isHybrid'])
#  {

   foreach ($greenhouse['genealogy'] as $gen)
   {
    if ($gen['itemCode'] == $seedpackage_code)
    {
    $seeds_array[$name]['seedpackage_UnlockState'] = $gen['startingUnlockState'];
     foreach ($gen['ingredient'] as $ing)
     {
     $ingname = Units_GetNameByCode($ing['code']);
     $seeds_array[$name]['genealogy'][$ingname]['name'] = $ingname;
     $seeds_array[$name]['genealogy'][$ingname]['code'] = $ing['code'];
     $seeds_array[$name]['genealogy'][$ingname]['quantity'] = $ing['quantity'];
     $seeds_array[$name]['genealogy'][$ingname]['realname'] = Units_GetRealnameByName($ingname);
     $seeds_array[$name]['genealogy'][$ingname]['iconurl'] = Seeder_ShowImagebyName($ingname);
     }
    }
   }

  $seedpackage_tray = @$tray_array[$seedpackage_code]['tray_quant']; if (!$seedpackage_tray) {$seedpackage_tray = 0;}
  $seeds_array[$name]['seedpackage_tray'] = $seedpackage_tray;
  $seedpackages_to_mastery = ($to_mastery - $seedpackage_count - $seedpackage_tray);if ($seedpackages_to_mastery < 0){$seedpackages_to_mastery = 0;}
  $seeds_array[$name]['seedpackages_to_mastery'] = $seedpackages_to_mastery;
  
#  }


 } else {
 $seeds_array[$name]['seedpackage_name'] = "NULL";
 $seeds_array[$name]['seedpackage_code'] = "NULL";
 $seeds_array[$name]['seedpackage_realname'] = "NULL";
 $seeds_array[$name]['seedpackage_count'] = 0;
 $seeds_array[$name]['seedpackage_tray'] = 0;
 $seeds_array[$name]['seedpackages_to_mastery'] = 0;
 $seeds_array[$name]['seedpackage_UnlockState'] = 0;
 }

 //limited timestamp added 1.1.3
 if (@$seed['limitedStart'])
 {
 $limitedStartTimestamp = strtotime($seed['limitedStart']);
 $limitedStartOffset = ($limitedStartTimestamp - $serverOffset + $localOffset);
 $seeds_array[$name]['limitedStartTimestamp'] = $limitedStartOffset;
 $seeds_array[$name]['limitedStart'] = date($Seeder_settings['timeformat'],$limitedStartOffset);
 } else {
 $seeds_array[$name]['limitedStartTimestamp'] = (time() - 100000000);
 $seeds_array[$name]['limitedStart'] = 'NULL';
 }
 if (@$seed['limitedEnd'])
 {
 $limitedEndTimestamp = strtotime($seed['limitedEnd']);
 $limitedEndOffset = ($limitedEndTimestamp - $serverOffset + $localOffset);
 $seeds_array[$name]['limitedEndTimestamp'] = $limitedEndOffset;
 $seeds_array[$name]['limitedEnd'] = date($Seeder_settings['timeformat'],$limitedEndOffset);
 } else {
 $seeds_array[$name]['limitedEndTimestamp'] = (time() + 100000000);
 $seeds_array[$name]['limitedEnd'] = 'NULL';
 }

 //creationDate added 1.1.4
 if (@$seed['creationDate'])
 {
 $creationDateTimestamp = strtotime($seed['creationDate']);
 $creationDateOffset = ($creationDateTimestamp - $serverOffset + $localOffset);
 $seeds_array[$name]['creationDateTimestamp'] = $creationDateOffset;
 $seeds_array[$name]['creationDate'] = date($Seeder_settings['timeformat'],$creationDateOffset);
 } else {
 $seeds_array[$name]['creationDateTimestamp'] = (time() - 100000000);
 $seeds_array[$name]['creationDate'] = 'NULL';
 }

//new fiels
//marketCardDescriptor
//masteryYield - candycane
//donation name
//expires="false"

 //licensed
 if (@$seed['license'])
 {
 $seeds_array[$name]['license'] = $seed['license'];
 $licensed = @$licenses[$seed_code]; if ($licensed) {$licensed = 1;} else {$licensed = 0;}
 $seeds_array[$name]['licensed'] = $licensed;
 }else {
 $seeds_array[$name]['license'] = "NULL";
 $seeds_array[$name]['licensed'] = 0;
 }

 //unlock & limitedLocale
 if (@$seed['unlock']){$seeds_array[$name]['unlock'] = $seed['unlock'];} else {$seeds_array[$name]['unlock'] = "NULL";}
 if (@$seed['limitedLocale']){$seeds_array[$name]['limitedLocale'] = $seed['limitedLocale'];} else {$seeds_array[$name]['limitedLocale'] = "NULL";}

 //requirements fixed 1.1.3
 $seeds_array[$name]['requirements'] = array();
 $seeds_array[$name]['requirements'] = @$reqs_array[$name];
 $reqs_count = count($seeds_array[$name]['requirements']);

 if ($reqs_count > 0)
 {
 $seeds_array[$name]['reqs'] = $reqs_count;
 } else {
 $seeds_array[$name]['requirements'][0] = "NULL";
 $seeds_array[$name]['reqs'] = 0;
 }

}//foreach($units_seeds as $seed)

$n_seeds = count($seeds_array);
Seeder_Write($seeds_array,"seeds");
unset($units_seeds);unset($seeds_array);unset($plots_planted);unset($bushels_array);unset($mastery_counters);unset($mastery_levels);
unset($inconbox);unset($reqs_array);unset($tray_array);unset($greenhouse);
AddLog2("Seeder_loadWorld> ".$n_seeds." Seeds updated");

//======================================
//Seeder_info
//======================================
$Seeder_info = array();
global $Seeder_info;

$energy = $world['data'][0]['data']['userInfo']['player']['energyManager']['purchased'];
$energy += $world['data'][0]['data']['userInfo']['player']['energyManager']['misc'];
$energy += $world['data'][0]['data']['userInfo']['player']['energyManager']['feed'];
$Seeder_info['energy'] = number_format($energy, 0, '.', '.');
$Seeder_info['userId'] = $world['data'][0]['data']['userInfo']['id'];
$Seeder_info['name'] = Seeder_replace($world['data'][0]['data']['userInfo']['attr']['name']);
$Seeder_info['gold'] = number_format($world['data'][0]['data']['userInfo']['player']['gold'], 0, '.', '.');
$Seeder_info['cash'] = number_format($world['data'][0]['data']['userInfo']['player']['cash'], 0, '.', '.');

//higherLevelXp
$xp = $world['data'][0]['data']['userInfo']['player']['xp'];
$Seeder_info['xp'] = number_format($xp, 0, '.', '.');
$level = $world['data'][0]['data']['userInfo']['player']['level'];
$higherLevelXp = Units_GetFarming('higherLevelXp');
$higherLevelBegin = Units_GetFarming('higherLevelBegin');
$higherLevelStep = Units_GetFarming('higherLevelStep');
 if ($xp >= $higherLevelXp)
 {
 $new_level = $higherLevelBegin + floor(($xp - $higherLevelXp) / $higherLevelStep);
 } else {
 $new_level = $level;
 }
//$Seeder_info['level'] = number_format($new_level, 0, '.', '.');
$Seeder_info['level'] = $new_level;

$Seeder_info['locale'] = $world['data'][0]['data']['locale'];
$Seeder_info['geoip'] = $world['data'][0]['data']['geoip'];
$Seeder_info['licenses'] = $licenses;// added 1.1.4

//Timezone added 1.1.3
$Seeder_info['localOffset'] = $localOffset;
$Seeder_info['serverOffset'] = $serverOffset;

//Quests requirements added 1.1.4
$objects = @$world['data'][0]['data']['userInfo']['world']['objectsArray'];
$greenhouse_name = @$amf2->_bodys[0]->_value['data'][1]['data']['breedingState'][0]['featureName'];

$Seeder_info['greenhouse'] = 0;
$Seeder_info['craftingbakery'] = 0;
$Seeder_info['craftingspa'] = 0;
$Seeder_info['craftingwinery'] = 0;

foreach ($objects as $object)
{
 if ($object['itemName'] == "craftingbakery") {$Seeder_info['craftingbakery'] = 1;}
 if ($object['itemName'] == "craftingspa") {$Seeder_info['craftingspa'] = 1;}
 if ($object['itemName'] == "craftingwinery") {$Seeder_info['craftingwinery'] = 1;}
 if ($object['itemName'] == $greenhouse_name)
 {
 $Seeder_info['greenhouse'] = 1;
 $Seeder_info['greenhouse_name'] = $greenhouse_name;
 $Seeder_info['greenhouse_level'] = $object['expansionLevel'];
 $Seeder_info['greenhouse_trays'] = @$amf2->_bodys[0]->_value['data'][1]['data']['breedingState'][0]['upgradeUnlockedTrays'][$object['expansionLevel']];//upgradeUnlockedTrays
 }
}

$Seeder_info['witherOn'] = @$world['data'][0]['data']['userInfo']['player']['witherOn'];
$Seeder_info['friendUnwithered'] = @$world['data'][0]['data']['userInfo']['player']['friendUnwithered'];
$Seeder_info['firstAirplaneFly'] = @$world['data'][0]['data']['userInfo']['player']['seenFlags']['firstAirplaneFly'];
$Seeder_info['witherMultiplier'] = Units_GetFarming('witherMultiplier');//added 1.1.2
$Seeder_info['witherRandomRange'] = (Units_GetFarming('witherRandomRange') + 1);//added 1.1.2

if (!$MarketStallCount){$MarketStallCount = 0;}
$Seeder_info['MarketStallCount'] = $MarketStallCount;

$Seeder_info['pendingRewards'] = $pendingRewards;//1.1.4

if (!$maxbushels){$maxbushels = 100;}
$Seeder_info['maxbushels'] = $maxbushels;

$Seeder_info['bushels'] = $n_bushels;
$Seeder_info['plots'] = $n_plots;

if (!$booster_crop){$booster_crop = "NULL";} $Seeder_info['booster_crop'] = $booster_crop;
if (!$booster_time) {$booster_time = 0;}
$Seeder_info['booster_time'] = $booster_time;

unset($booster_crop);unset($booster_time);
unset($objects);unset($world);
Seeder_Write($Seeder_info,"info");

//======================================
//check quests fix 1.1.6
 if (!file_exists(Seeder_dbPath.'quests.txt')) {Seeder_MakeQuests();}
 else{
 $quests = Seeder_ReadDefault("quests");
 if ((!is_array($quests)) || (count($quests) == 0)) {Seeder_MakeQuests();}
 unset($quests);
 }
//======================================
} //if ($errData == 0)
else
{
Seeder_error("Seeder_loadWorld> ".$amf_error);
}
unset($amf2);unset($amf);unset($s);unset($answer);unset($deserializer2);unset($serializer);
//======================================
$T2 = time();
$T2 -= $T;
AddLog2("Seeder_loadWorld> end ".$T2." Secs.");

}//function Seeder_loadWorld()
//========================================================================================================================
?>
