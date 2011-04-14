<?php
//========================================================================================================================
//Seeder_coop.php
//by N1n9u3m
//========================================================================================================================
//Seeder_CoopJobs
//========================================================================================================================
function Seeder_CoopJobs()//added v1.1.4
{
$T = time(true);
AddLog2("Seeder_CoopJobs> start");

global $Seeder_settings;

$coop_active = Seeder_Read("activeMission");
$coop_id = $coop_active['id'];

 if ($coop_id != "active_mission_id_none")
 {
 AddLog2("Seeder_CoopJobs> active mission :".Seeder_GetQuestRealname($coop_active['id']));
 if ($Seeder_settings['coop_plant'] == 1) {Seeder_plant_quest();}

 } else {

 AddLog2("Seeder_CoopJobs> no active mission");
 $quests_available = Seeder_quests_available();
 $check = @$quests_available[$Seeder_settings['coop_host']];

 if ($check)
 {
  if ($Seeder_settings['coop_mode'] == "host")
  {
  AddLog2("Seeder_CoopJobs> co-op host mode selected");
  AddLog2("Seeder_CoopJobs> starting mission :".Seeder_GetQuestRealname($Seeder_settings['coop_host']));
  Seeder_start_quest($Seeder_settings['coop_host']);
  if ($Seeder_settings['coop_plant'] == 1) {Seeder_plant_quest();}
  } else {//mode guest
  AddLog2("Seeder_CoopJobs> co-op guest mode selected");
  AddLog2("Seeder_CoopJobs> joining mission :".Seeder_GetQuestRealname($Seeder_settings['coop_host'])." from ".$Seeder_settings['coop_follow']);
  Seeder_join_quest($Seeder_settings['coop_host'],$Seeder_settings['coop_follow']);
  if ($Seeder_settings['coop_plant'] == 1) {Seeder_plant_quest();}
  }

 } else {//if ($check)
 AddLog2("Seeder_CoopJobs> ERROR: mission ".Seeder_GetQuestRealname($Seeder_settings['coop_host']." not available"));
 }


 unset($quests_available);
 }

unset($coop_active);
$T2 = time();
$T2 -= $T;
AddLog2("Seeder_CoopJobs> end ".$T2." Secs.");
}
//========================================================================================================================
//Seeder_loadJobs
//========================================================================================================================
function Seeder_loadJobs()//revised v1.1.4
{
$T = time(true);
AddLog2("Seeder_loadJobs> start");

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
$amf->_bodys[0]->_value[1][0]['params'][0] = '-1';
$amf->_bodys[0]->_value[1][0]['functionName'] = "SocialMissionService.onGetActiveMission";

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
$amf_error = "SocialMissionService.onGetAllMissionData"."\n".$amf2->_bodys[0]->_value['data'][0]['amf_error'];
}

if ($amf_error == 0)
{

Seeder_Write(@$amf2->_bodys[0]->_value['data'][0]['data'],"activeMission");
AddLog2("Seeder_loadWorld> Active Co-Op Job updated");

} else {
Seeder_error("Seeder_loadJobs> ".$amf_error);
}

unset($amf2);unset($amf);unset($s);unset($answer);unset($deserializer2);unset($serializer);
//======================================

$T2 = time();
$T2 -= $T;
AddLog2("Seeder_loadJobs> end ".$T2." Secs.");
}//function Seeder_loadJobs()
//========================================================================================================================
//Seeder_end_quest
//========================================================================================================================
function Seeder_end_quest()//revised v1.1.4
{

global $Seeder_settings, $Seeder_info;

$ActiveMission = Seeder_Read("activeMission");

 if ($ActiveMission['isComplete'] == 1)
 {
 $res = 0;
 $px_time = time();
 $amf = new AMFObject("");
 $amf->_bodys[0] = new MessageBody();
 $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
 $amf->_bodys[0]->responseURI = '/1/onStatus';
 $amf->_bodys[0]->responseIndex = '/1';
 $amf->_bodys[0]->_value[0] = GetAMFHeaders();
 $amf->_bodys[0]->_value[2] = 0;

 $amf->_bodys[0]->_value[1][0]['params'][0] = false;
 $amf->_bodys[0]->_value[1][0]['params'][1] = Null;
 $amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
 $amf->_bodys[0]->_value[1][0]['functionName'] = "SocialMissionService.onGetMissionComplete";

 $res = RequestAMF($amf);
 AddLog2("Seeder_end_quest> Ending Complete Co-op Job result: ".$res);
 Seeder_loadJobs();//rapid reload

 } else {//if ($ActiveMission['isComplete'] == 1)
 AddLog2("Seeder_end_quest> no Co-op Job Complete to end");
 }

}
//========================================================================================================================
//Seeder_start_quest
//========================================================================================================================
function Seeder_start_quest($questid)//added v1.1.4
{

global $Seeder_settings, $Seeder_info;

 $res = 0;
 $px_time = time();
 $amf = new AMFObject("");
 $amf->_bodys[0] = new MessageBody();
 $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
 $amf->_bodys[0]->responseURI = '/1/onStatus';
 $amf->_bodys[0]->responseIndex = '/1';
 $amf->_bodys[0]->_value[0] = GetAMFHeaders();
 $amf->_bodys[0]->_value[2] = 0;
 $amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
 $amf->_bodys[0]->_value[1][0]['functionName'] = "SocialMissionService.onSendStartMission";
 $amf->_bodys[0]->_value[1][0]['params'][0] = $questid;
 $res = RequestAMF($amf);
 AddLog2("Seeder_start_quest> Start Co-op Job result: ".$res);
 Seeder_loadJobs();//rapid reload

}
//========================================================================================================================
//Seeder_join_quest
//========================================================================================================================
function Seeder_join_quest($questid,$uid)//revised v1.1.5
{

global $Seeder_settings, $Seeder_info;

 $uid = number_format($uid, 0, '', '');
 $res = 0;
 $px_time = time();
 $amf = new AMFObject("");
 $amf->_bodys[0] = new MessageBody();
 $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
 $amf->_bodys[0]->responseURI = '/1/onStatus';
 $amf->_bodys[0]->responseIndex = '/1';
 $amf->_bodys[0]->_value[0] = GetAMFHeaders();
 $amf->_bodys[0]->_value[2] = 0;

 $amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
 $amf->_bodys[0]->_value[1][0]['functionName'] = "SocialMissionService.onSendJoinMission";
 $amf->_bodys[0]->_value[1][0]['params'][0] = $questid;
 $amf->_bodys[0]->_value[1][0]['params'][1] = $uid;

 $res = RequestAMF($amf);
 AddLog2("Seeder_join_quest> Join Co-op Job result: ".$res);
 Seeder_loadJobs();//rapid reload

}
//========================================================================================================================
//Seeder_plant_quest
//========================================================================================================================
function Seeder_plant_quest()//added v1.1.4
{

global $Seeder_settings, $Seeder_info;
$coop_active = Seeder_Read("activeMission");
$coop_id = $coop_active['id'];

 if ((is_array($coop_active)) && ($coop_id != "active_mission_id_none"))
 {

  if ($coop_active['completeType'] == 'in_progress')
  {

  $quests_available = Seeder_quests_available();
  $seeds_available = Seeder_available();

  $coop_seeds = array();
  $coop_active_data = $quests_available[$coop_id];

  AddLog2("Seeder_plant_quest> Co-op Job ".$coop_active_data['realname']." in progress:");
  $reqs = $coop_active_data['completionRequirements'][2]['requirement'];//gold only

  $reqs_count = count($reqs);

  //make a new array to Growtime
  for ($i = 0; $i < $reqs_count; $i++)
  {
   if ($reqs[$i]['action'] == "seed")
   {
   $seed = $reqs[$i]['type'];
   $coop_seeds[$seed]['name'] = $seed;
   $coop_seeds[$seed]['realname'] = $seeds_available[$seed]['realname'];
   $coop_seeds[$seed]['growTime'] = $seeds_available[$seed]['growTime'];
   $coop_seeds[$seed]['many'] = $reqs[$i]['many'];
   $coop_seeds[$seed]['progress'] =  $coop_active['currentProgress'][$i]['progress'];
   }
  }

  $recs = 0;
  $coop_seeds = Seeder_ArrayOrder($coop_seeds, 'growTime', $Seeder_settings['coop_growTime']);
  foreach ($coop_seeds as $coop_seed)
  {
  AddLog2("Seeder_plant_quest> seed ".$coop_seed['realname']." : ".floor(($coop_seed['progress'] / $coop_seed['many']) * 100)."% (".$coop_seed['progress']."/".$coop_seed['many'].")");

  
  $toplant = ($coop_seed['many'] - $coop_seed['progress']);
   if ($toplant > 0 )
   {
   $strline .= $coop_seed['name'].":".$toplant.";";
   $recs += 1;
   }
  }

  if ($recs > 0)
  {
   $strline = substr($strline, 0, -1);
   if ($fh = fopen(F('seed.txt'), "w+"))
   {
   fwrite($fh, $strline);
   fclose($fh);
   AddLog2("Seeder_plant_quest> ".$recs." seeds to Co-Op Job");
   }
  }


  unset($quests_available);unset($seeds_available);unset($coop_active_data);unset($coop_active);unset($quests_all);
  }//if ($completeType == 'in_progress')
 }//if ((is_array($coop_active)) && ($coop_id != "active_mission_id_none"))

}
//========================================================================================================================
//Seeder_MakeQuests
//========================================================================================================================
function Seeder_MakeQuests()//added v1.1.4
{

global $Seeder_settings;
list($userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy) = explode(';', file_get_contents(F('params.txt')));

$quests_xml = "./farmville-xml/".$flashRevision."_Quests.xml";
$locale_xml = "./farmville-xml/".$flashRevision."_flashLocaleXml.xml";
$quests_array = array();
$locale_array = array();

$localOffset = date('Z');// fixed 1.1.3b
$serverOffset = (-5 * 60 * 60);//(GMT -5:00) EST - Eastern Standard Time (U.S. & Canada)


if (file_exists($locale_xml))
{

//locale
$xml_doc = simplexml_load_file($locale_xml);
foreach ($xml_doc->bundle as $bundle)
{

 foreach ($bundle->attributes() as $attribute => $attribute_value)
 {

  $bundle_name = (string)$attribute_value;
  if ($bundle_name == 'SocialMissionQuests')
  {

   foreach ($bundle->children() as $bundleLine)
   {

    $name = (string)$bundleLine['key'];
    if(substr($name,-6) == '_Title')
    {
    $realname = (string)$bundleLine->value;
    $locale_array[$name] = Seeder_replace($realname);
    }

   }

  }

 }

}//foreach ($xmlDoc->bundle as $item)
unset($xml_doc);

} else {
//Seeder_error("Seeder_MakeQuests> file ".$locale_xml." file does not exist");
}


if (file_exists($quests_xml)) {


//quests
$Seeder_settings = Seeder_Read("settings");
$localOffset = date('Z');
$serverOffset = (-5 * 60 * 60);//(GMT -5:00) EST - Eastern Standard Time (U.S. & Canada)

$xml_doc = simplexml_load_file($quests_xml);
$quests_array = Sedeer_XMLToArray($xml_doc);
unset($xml_doc);

$quests_count = count($quests_array['quest']);
$quests = array();
$seeds_all = Seeder_Read("seeds");

for ($i = 0; $i < $quests_count; $i++)
{
$quest_id = $quests_array['quest'][$i]['id'];
$quest_name = $quests_array['quest'][$i]['text']['title'];
$quests[$quest_id] = $quests_array['quest'][$i];
$quests[$quest_id]['realname'] = @$locale_array[$quest_name];

 if (strlen($quests[$quest_id]['realname']) == 0)//Chinese Fix
 {
 $quests[$quest_id]['realname'] = Seeder_replace($quests[$quest_id]['text']['title']);
 }
 
 if ($quests_array['quest'][$i]['limitedEditionStart'])
 {
 $limitedStartTimestamp = strtotime($quests_array['quest'][$i]['limitedEditionStart']);
 $limitedStartOffset = ($limitedStartTimestamp - $serverOffset + $localOffset);
 $quests[$quest_id]['limitedStartTimestamp'] = $limitedStartOffset;
 $quests[$quest_id]['limitedStart'] = date($Seeder_settings['timeformat'],$limitedStartOffset);
 } else {
 $quests[$quest_id]['limitedStartTimestamp'] = (time() - 100000000);
 $quests[$quest_id]['limitedStart'] = 'NULL';
 }
 
 if ($quests_array['quest'][$i]['limitedEditionEnd'])
 {
 $limitedEndTimestamp = strtotime($quests_array['quest'][$i]['limitedEditionEnd']);
 $limitedEndOffset = ($limitedEndTimestamp - $serverOffset + $localOffset);
 $quests[$quest_id]['limitedEndTimestamp'] = $limitedEndOffset;
 $quests[$quest_id]['limitedEnd'] = date($Seeder_settings['timeformat'],$limitedEndOffset);
 } else {
 $quests[$quest_id]['limitedEndTimestamp'] = (time() + 100000000);
 $quests[$quest_id]['limitedEnd'] = 'NULL';
 }
 
 //fix candy cane type
 if (!$quests_array['quest'][$i]['type']) {$quests[$quest_id]['type'] = 'basic';}

//score fields
  $reqs = $quests_array['quest'][$i]['completionRequirements'][2]['requirement'];//gold
  $reqs_count = count($reqs);
  $score_growTime = 0;
  $score_plantXp = 0;
  $score_cost = 0;
  $score_plots = 0;
  
  for ($n = 0; $n < $reqs_count; $n++)
  {
   if ($reqs[$n]['action'] == "seed")
   {
   $seed = $reqs[$n]['type'];
   $many = $reqs[$n]['many'];
  //tripleberry fix
  
  
   $growTime = @$seeds_all[$seed]['growTime'];
   if ($growTime > $score_growTime) {$score_growTime = $growTime;}//max growTime
   $score_coinYield += (@$seeds_all[$seed]['coinYield'] * $many);
   $score_plantXp += ((@$seeds_all[$seed]['plantXp'] + 1) * $many);
   $score_cost += ((@$seeds_all[$seed]['cost'] + 15)* $many);
   $score_plots += $many;
   }
  }
  $quests[$quest_id]['score_growTime'] = $score_growTime;
  $quests[$quest_id]['score_coinYield'] = $score_coinYield;
  $quests[$quest_id]['score_plantXp'] = $score_plantXp;
  $quests[$quest_id]['score_cost'] = $score_cost;
  $quests[$quest_id]['score_plots'] = $score_plots;
}

} else {Seeder_error("Seeder_MakeQuests> file ".$quests_xml." file does not exist");return;}

if (count($quests > 0)) {Seeder_WriteDefault($quests,"quests");}//fix 1.1.6

unset($locale_array);unset($quests);unset($quests_array);
}
//========================================================================================================================
//functions
//========================================================================================================================
function Seeder_GetQuest($quest_id)//revised v1.1.4
{

$quest_array = array();
$quests_array = Seeder_ReadDefault("quests");
$quest_array = $quests_array[$quest_id];

return $quest_array;
unset($quests_array);
}
//========================================================================================================================
function Seeder_GetQuestRealname($quest_id)//added v1.1.4
{

$quests_array = Seeder_ReadDefault("quests");

 if ($quest_id)
 {
  foreach ($quests_array as $quest)
  if ($quest['id'] == $quest_id)
   if ($quest['realname'])
   {
   return Seeder_replace($quest['realname']);
   } else {
   return Seeder_replace($quest['text']['title']);
   }

 }
 else {
 return $quest_id;}

}
//========================================================================================================================
function Seeder_quests_available()//added v1.1.4
{

global $Seeder_settings, $Seeder_info;
$quests_available = array();
$quests = Seeder_ReadDefault("quests");
$seeds_available = Seeder_available();

if ($Seeder_settings['coop_mode'] == "host")
{
$quests = Seeder_ArrayFilter($quests, 'requiredLevel', '<=', $Seeder_info['level']);
} else {
$quests = Seeder_ArrayFilter($quests, 'requiredJoinLevel', '<=', $Seeder_info['level']);
}
$quests = Seeder_ArrayFilter($quests, 'limitedStartTimestamp', '<=', time());
$quests = Seeder_ArrayFilter($quests, 'limitedEndTimestamp', '>=', time());


 foreach ($quests as $quest)
 {
 $quest_id = $quest['id'];
 $quest_filter = 0;

  if (@$quest['requiredHostItemName'])
  {
  if (($quest['requiredHostItemName'] == "craftingbakery") && ($Seeder_info['craftingbakery'] == 0)) {$quest_filter = 1;}
  if (($quest['requiredHostItemName'] == "craftingspa") && ($Seeder_info['craftingspa'] == 0)) {$quest_filter = 1;}
  if (($quest['requiredHostItemName'] == "craftingwinery") && ($Seeder_info['craftingwinery'] == 0)) {$quest_filter = 1;}
  }

  //check seeds

  $reqs = $quest['completionRequirements'][2]['requirement'];//gold
  $reqs_count = count($reqs);

  for ($i = 0; $i < $reqs_count; $i++)
  {
   if ($reqs[$i]['action'] == "seed")
   {
   $seed = $reqs[$i]['type'];
   $check = @$seeds_available[$seed]; if (!$check) {$quest_filter = 1;}
   }
  }

  if ($quest_filter == 0) {$quests_available[$quest_id] = $quest;}

 }//foreach ($quests as $quest)


unset($seeds_available);unset($quests);
return $quests_available;


}//function Seeder_quests_available()
//========================================================================================================================
