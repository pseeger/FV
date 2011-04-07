<?php
//========================================================================================================================
//Seeder_functions.php
//by N1n9u3m
//========================================================================================================================
//Seeder_seeds_available
//========================================================================================================================
function Seeder_available()//added v1.1.2
{

global $Seeder_settings, $Seeder_info;
$seeds_available = array();

 if (file_exists(Seeder_dbPath.PluginF('info.txt')))
 {
 $Seeder_info = Seeder_Read("info");

  if (file_exists(Seeder_dbPath.PluginF('seeds.txt')))
  {
  $seeds = Seeder_Read("seeds");
  $seeds = Seeder_ArrayFilter($seeds, 'buyable', '==', '1');
  $seeds = Seeder_ArrayFilter($seeds, 'limitedStartTimestamp', '<=', time());
  $seeds = Seeder_ArrayFilter($seeds, 'limitedEndTimestamp', '>=', time());
  $seeds = Seeder_ArrayFilter($seeds, 'requiredLevel', '<=', $Seeder_info['level']);
  $mastery_counters = unserialize(file_get_contents(F('cropmasterycount.txt')));
  $licenses = $Seeder_info['licenses'];
  
//Filters
   foreach ($seeds as $seed)
   {
    $seed_filter = 0;
    $name = $seed['name'];
    
//license
    if ($seed['license'] != "NULL")
    {
     if ($seed['licensed'] == 0) {$seed_filter = 1;}
    }
//limitedLocale
    if (($seed['limitedLocale'] != "NULL") && ($seed['limitedLocale'] != $Seeder_info['locale']))
    {
    $seed_filter = 1;
    }
//seedpackage
    if (($seed['seedpackage_name'] != "NULL") && ($seed['seedpackage_count'] == 0))
    {
    $seed_filter = 1;
    }
//requirements
    if (($seed['reqs'] > 0) && ($seed['isHybrid'] == 0))//fix 1.1.6
    {
      $count = count($seed['requirements']);
      for ($i = 0; $i < $count; $i++)
      {
       if ($seed['requirements'][$i] != "farm")
       {
       $mastery_req =  @$mastery_counters[Units_GetCodeByName($seed['requirements'][$i])];
        if ($mastery_req <> 2)
        {
        $seed_filter = 1;
        }
       }
      }
    }

    //all filters ok
//force_planting
   if ($Seeder_settings['force_planting'] == 1) {$seed_filter = 0;}

   if ($seed_filter == 0) {$seeds_available[$name] = $seed;}

   }//foreach ($seeds as $seed)
  }//if (file_exists(Seeder_dbPath.PluginF('seeds.txt')))
 }//if (file_exists(Seeder_dbPath.PluginF('info.txt')))

unset($mastery_counters);unset($seeds);
$seeds_available = Seeder_ArrayOrder($seeds_available, $Seeder_settings['seeds_order'], $Seeder_settings['seeds_sort']);
return $seeds_available;

}//function Seeder_seeds_available()

//========================================================================================================================
//Seeder_SeedFilter
//========================================================================================================================
function Seeder_SeedFilter($name)//added v1.1.4
{

global $Seeder_settings, $Seeder_info;
$Seeder_info = Seeder_Read("info");
$licenses = $Seeder_info['licenses'];
$mastery_counters = unserialize(file_get_contents(F('cropmasterycount.txt')));
  
$seeds = Seeder_Read("seeds");
$seed =  Seeder_ArrayFilter($seeds, 'name', '==', $name);

$seed = Seeder_ArrayFilter($seed, 'buyable', '==', '1');
$seed = Seeder_ArrayFilter($seed, 'limitedStartTimestamp', '<=', time());
$seed = Seeder_ArrayFilter($seed, 'limitedEndTimestamp', '>=', time());
$seed = Seeder_ArrayFilter($seed, 'requiredLevel', '<=', $Seeder_info['level']);

$SeedFilter = 0;

if (count($seed) > 0 )
{
//license
    if ($seed[$name]['license'] != "NULL")
    {
     if ($seed[$name]['licensed'] == 0) {$SeedFilter = 1;}
    }
//limitedLocale
    if (($seed[$name]['limitedLocale'] != "NULL") && ($seed[$name]['limitedLocale'] != $Seeder_info['locale']))
    {
    $SeedFilter = 1;
    }
//seedpackage
    if (($seed[$name]['seedpackage_name'] != "NULL") && ($seed[$name]['seedpackage_count'] == 0))
    {
    $SeedFilter = 1;
    }
//requirements
    if (($seed['reqs'] > 0) && ($seed['isHybrid'] == 0))//fix 1.1.6
    {
      $count = count($seed[$name]['requirements']);
      for ($i = 0; $i < $count; $i++)
      {
       if ($seed['requirements'][$i] != "farm")
       {
       $mastery_req =  @$mastery_counters[Units_GetCodeByName($seed['requirements'][$i])];
        if ($mastery_req <> 2)
        {
        $seed_filter = 1;
        }
       }
      }
    }

} else {$SeedFilter = 1;}// 1st Seeder_ArrayFilter

//force_planting
   if ($Seeder_settings['force_planting'] == 1) {$SeedFilter = 0;}


unset($mastery_counters);unset($seeds);
return $SeedFilter;

}//function Seeder_seeds_available()
//========================================================================================================================
//Seeder_mastery
//========================================================================================================================
function Seeder_mastery()//revised v1.1.3
{
$T = time(true);
AddLog2("Seeder_mastery> start");
$recs = 0;

global $Seeder_settings, $Seeder_info;
$Seeder_info = Seeder_Read("info");

$seeds_tomastery = Seeder_available();//added 1.1.2
$seeds_tomastery = Seeder_ArrayFilter($seeds_tomastery, 'masterymax', '>', '0');
$seeds_tomastery = Seeder_ArrayFilter($seeds_tomastery, 'to_mastery', '>', '0');
if ($Seeder_settings['mastery_adjustment'] == 0) {$seeds_tomastery = Seeder_ArrayFilter($seeds_tomastery, 'mastery_level', '<', '3');}

 if (sizeof($seeds_tomastery) > 0 )
 {
  foreach ($seeds_tomastery as $seed_toplant)
  {
  $toplant = $seed_toplant['to_mastery'];
  $plots_planted = $seed_toplant['plots_planted'];
  $mastery_count = $seed_toplant['mastery_count'];
  $masterymax = $seed_toplant['masterymax'];

   //Bushels x1
   if (($Seeder_settings['bushel_booster'] == 1) && ($seed_toplant['bushel_name'] != "NULL"))
   {
   $toplant = ceil(($toplant - $plots_planted)/ 2);

   }

   //seedpackage v1.1.0
   if (($seed_toplant['seedpackage_name'] != "NULL") && ($toplant > $seed_toplant['seedpackage_count']))
   {
   $toplant = $seed_toplant['seedpackage_count'];
   }

   if ($toplant > 0)
   {
   $strline .= $seed_toplant['name'].":".$toplant.";";
   $recs += 1;
   }

  }//foreach ($seeds_tomastery as $seed_toplant)

  if ($recs > 0)
  {
   $strline = substr($strline, 0, -1);
   if ($fh = fopen(F('seed.txt'), "w+"))
   {
   fwrite($fh, $strline);
   fclose($fh);
   AddLog2("Seeder_mastery> ".$recs." seeds to mastery");
   }else{Seeder_error("Seeder_mastery> open ".F('seed.txt'));}
  } else {AddLog2("Seeder_mastery> No Seeds to Mastery.");}


 } else {AddLog2("Seeder_mastery> Congratulations, All seeds Mastered!!!");} //if (sizeof($seeds_tomastery) > 0 )

unset($seeds_tomastery);unset($seed_toplant);

$T2 = time();
$T2 -= $T;
AddLog2("Seeder_mastery> end ".$T2." Secs.");
}
//========================================================================================================================
//Seeder_keep_planted
//========================================================================================================================
function Seeder_keep_planted()//added v1.1.5
{

$plots_array = array();
$all_plots = GetObjects('Plot');
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


$seedlist = Seeder_Read("seedlist");
$seed_list = array();
$changed_seed_list = false;

foreach ($seedlist as $key => $seed_value)
{
 if ($key == "default")
 {
 $seed_list[] = $seed_value[0].":Default";
 $changed_seed_list = true;
 AddLog2("Seeder_keep_planted> ".Units_GetRealnameByName($seed_value[0])." Default Seed");
 } else {
   
 $seed = $seed_value[0];
 $count = $seed_value[1];
 $planted = $plots_array[$seed]['count'];
 AddLog2("Seeder_keep_planted> ".Units_GetRealnameByName($seed_value[0])." Planted:".$planted."/".$count);

  if ($count > $planted)
  {
  $seed_list[] = $seed.":".($count - $planted);
  $changed_seed_list = true;
  }

 }
}

unset($seedlist);

if ($changed_seed_list)
{
$f = fopen(F('seed.txt'), "w+");
$seed_data = implode(';', $seed_list);
fwrite($f, $seed_data, strlen($seed_data));
fclose($f);
}

}
//========================================================================================================================
//Seeder_Booster!
//========================================================================================================================
function Seeder_Booster()//revised v1.1.4
{

 global $Seeder_settings, $Seeder_info;
 global $need_reload;

 $plot_list = GetObjects('Plot');
 $plots = array();

 foreach($plot_list as $plot)
 {
 if (($plot['state'] == 'grown') || ($plot['state'] == 'ripe'))
 $plots[] = $plot;
 }
 unset($plot_list);

 if (count($plots) > 0)
 {
//======================================
//Fertilize v1.1.0
//======================================
  if ($Seeder_settings['fertilize'] == 1)
  {
  Seeder_Fertilize();
  }
//======================================
 $plot_count = array();
 $timenow = time();
 $seeds = Seeder_Read("seeds");

  foreach ($plots as $plot)
  {
  $itemName = $plot['itemName'];
   if (!isset($plot_count[$itemName]['itemName']))
   {
   $plot_count[$itemName]['itemName'] = $itemName;
   $plot_count[$itemName]['realname'] = @$seeds[$itemName]['realname'];
   $plot_count[$itemName]['bushel_name'] = @$seeds[$itemName]['bushel_name'];
   $plot_count[$itemName]['bushel_code'] = @$seeds[$itemName]['bushel_code'];
   $plot_count[$itemName]['bushel_count'] = @$seeds[$itemName]['bushel_count'];
   $plot_count[$itemName]['count'] = 1;
   }
   else
   {
   $plot_count[$itemName]['count'] += 1;
   }
  }

  unset($seeds);
  
//======================================
//priority already Boosted
//======================================

  $harvest_boosted = 0;
  $harvest_more = 1;
  $BBushel_active = $Seeder_info['booster_crop'];

  if ($BBushel_active != "NULL")
  {
  $booster_time = $Seeder_info['booster_time'];
  $Btime_diff = $booster_time - $timenow;
   if ($Btime_diff > 0 )
   {
   $plots_boosted = Seeder_ArrayFilter($plots, 'itemName', '==', $BBushel_active);
    if (count($plots_boosted) > 0)
    {
    AddLog2("Seeder_Booster> Harvest already Boosted ".Units_GetRealnameByName($BBushel_active));
    Do_Farm_Work_Plots($plots_boosted, 'harvest');
    $harvest_boosted = 1;
    }
   }
  }

  if ($harvest_boosted = 1)
  {
  $plot_count = Seeder_ArrayFilter($plot_count, 'itemName', '!=', $BBushel_active);//update plots_count
   if (count($plot_count) > 0)
   {
   $harvest_more = 1;
   $plots = Seeder_ArrayFilter($plots, 'itemName', '!=', $BBushel_active);//update plots
   }
   else
   {
   $harvest_more = 0;//no more crops
   }
  }

//======================================
//more crops to harvest without booster
//======================================

  if ($harvest_more = 1)
  {

  //priority count of crops
  $plot_count = Seeder_ArrayOrder($plot_count, 'count', 'DESC');

   foreach ($plot_count as $plot)
   {
   $plot_booster = Seeder_ArrayFilter($plots, 'itemName', '==', $plot['itemName']);

    //bushel unavailable
    if ($plot['bushel_name'] == "NULL")
    {
    AddLog2("Seeder_Booster> Harvest unavailable Booster ".$plot['realname']);
    Do_Farm_Work_Plots($plot_booster, 'harvest');
    }
    
    //bushel available!
    else
    {
     //have bushel
     if ($plot['bushel_count'] > 0)
     {
     Seeder_useBushel($plot['bushel_name']);
     AddLog2("Seeder_Booster> Harvest Boosted ".$plot['realname']);
     Do_Farm_Work_Plots($plot_booster, 'harvest');
     }
     //don't have bushel
     else
     {
      AddLog2("Seeder_Booster> No ".$plot['bushel_name']." to use");
     //check market stall space
      $res_burn = Seeder_burnBushel();

      //priority buy
      AddLog2("Seeder_Booster> Try Buy Bushel ".$plot['bushel_name']);
      $res = Seeder_GetMarket($plot['bushel_code']);
      if ($res != 'OK')
      {
      AddLog2("Seeder_Booster> Harvest to Try find ".$plot['bushel']);
      $res = Seeder_Harvest($plot_booster);
       if ($res == 'OK')
       {
       AddLog2("Seeder_Booster> Restarting farm to Rapid Harvest");
       DoInit('');
       Seeder_Booster();
       break;//foreach ($plot_count as $plot)
       }
      }

     } //if ($bushels > 0)
     
    }//if ($bushel == "NULL")//bushel unavailable
    
   }//foreach ($plot_count as $plot)
   
  unset($plot_booster);
  }//if ($harvest_more = 1)// more crops to harvest without booster
  
 unset($plot_count);
 DoInit();
 }//if (count($plots) > 0)

 unset($plots);

}
//========================================================================================================================
//Seeder_HarvestBushel
//parser Do_Farm_Work mod
//========================================================================================================================
function Seeder_Harvest($plots)//revised v1.1.2
{

global $Seeder_settings, $Seeder_info;
$action = "harvest";

    global $need_reload;
    $px_Setopts = LoadSavedSettings();

    if ((!@$px_Setopts['bot_speed']) || (@$px_Setopts['bot_speed'] < 1))
        $px_Setopts['bot_speed'] = 1;

    if (@$px_Setopts['bot_speed'] > PARSER_MAX_SPEED)
        $px_Setopts['bot_speed'] = PARSER_MAX_SPEED;

    if ((@!$fuel) || (@$fuel < 0))
        $fuel = 0;

    $count = count($plots);
    if ($count > 0)
    {
    $T = time(true);
    AddLog2("Seeder_Harvest> start Harvest ".$count." crops");

        global $userId;
        $amf = new AMFObject("");
        $amf->_bodys[0] = new MessageBody();

        $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
        $amf->_bodys[0]->responseURI = '/1/onStatus';
        $amf->_bodys[0]->responseIndex = '/1';

        $amf->_bodys[0]->_value[0] = GetAMFHeaders();
        $amf->_bodys[0]->_value[2] = 0;
        $i = 0;

        foreach($plots as $plot)
        {

            $amf->_bodys[0]->_value[1][$i]['functionName'] = "WorldService.performAction";
            $amf->_bodys[0]->_value[1][$i]['params'][0] = $action;
            $amf->_bodys[0]->_value[1][$i]['sequence'] = GetSequense();

            $amf->_bodys[0]->_value[1][$i]['params'][1] = $plot;
            $amf->_bodys[0]->_value[1][$i]['params'][2] = array();

            if ($fuel > 0) {
                $amf->_bodys[0]->_value[1][$i]['params'][2][0]['energyCost'] = 1;
                $fuel--;
            } else {
                $amf->_bodys[0]->_value[1][$i]['params'][2][0]['energyCost'] = 0;
            }

            if (@!$plotsstring)
                $plotsstring = $plot['itemName'] . " " . GetPlotName($plot);
            else
                $plotsstring = $plotsstring . ", " . $plot['itemName'] . " " . GetPlotName($plot);

            if (@!$OKstring)
                $OKstring = $action . " " . $plot['itemName'] . " on plot " . GetPlotName($plot);
            else
                $OKstring = $OKstring . "\r\n" . $action . " " . $plot['itemName'] . " on plot " . GetPlotName($plot);

            $i++;

            if (($i == $px_Setopts['bot_speed']) || ($i >= $count))
            {
            AddLog2($action . " " . $plotsstring);

//======================================
    $serializer = new AMFSerializer();
    $result = $serializer->serialize($amf); // serialize the data
    $answer = Request('', $result);
    $amf2 = new AMFObject($answer);
    $deserializer2 = new AMFDeserializer($amf2->rawData); // deserialize the data
    $deserializer2->deserialize($amf2); // run the deserializer
    if (@$amf2->_bodys[0]->_value['errorType'] != 0) {
        if ($amf2->_bodys[0]->_value['errorData'] == "There is a new version of the farm game released") {
            AddLog2("New version of the game released");
            echo "\n*****\nGame version out of date\n*****\n";
            unlink('unit_check.txt');
            RestartBot();
        } else if ($amf2->_bodys[0]->_value['errorData'] == "token value failed") {
            AddLog2("Error: token value failed");
            AddLog2("You opened the game in another browser");
            AddLog2("Restart the game or wait for forced restart");
            echo "\n*****\nError: token value failed\nThis error is caused by opening the game in another browser\nRestart the bot or wait 15 seconds for forced restart.\n*****\n";
            RestartBot();
        } else if ($amf2->_bodys[0]->_value['errorData'] == "token too old") {
            AddLog2("Error: token too old");
            AddLog2("The session expired");
            AddLog2("Restart the game or wait for forced restart");
            echo "\n*****\nError: token too old\nThe session has expired\nRestart the bot or wait 15 seconds for forced restart.\n*****\n";
            RestartBot();
        } else if ($amf2->_bodys[0]->_value['errorType'] == 29) {
            AddLog2("Error: Server sequence was reset");
            AddLog2("Restart the game or wait for forced restart");
            echo "\n*****\nError: Server sequence was reset\nRestart the bot or wait 15 seconds for forced restart.\n*****\n";
            RestartBot();
        } else {
            echo "\n*****\nError: \n" . $amf2->_bodys[0]->_value['errorType'] . " " . $amf2->_bodys[0]->_value['errorData'] . "\n";
            $res = "Error: " . $amf2->_bodys[0]->_value['errorType'] . " " . $amf2->_bodys[0]->_value['errorData'];
        }
    } else if (!isset($amf2->_bodys[0]->_value['data'][0])) {
        echo "\n*****\nError:\n BAD AMF REPLY - Possible Server problem or farm badly out of sync\n*****\n";
        $res = "BAD AMF REPLY (OOS?)";
    } else if (isset($amf2->_bodys[0]->_value['data'][0]['errorType']) && ($amf2->_bodys[0]->_value['data'][0]['errorType'] == 0))
    {
//======================================
    #Do_Farm_Work_Plots($plot_booster, 'harvest');
    
    
for ($n = 0; $n <= $i; $n++)
{
 if (isset($amf2->_bodys[0]->_value['data'][$n]['data']['foundBushel']))
 {
  $foundBushelCode = $amf2->_bodys[0]->_value['data'][$n]['data']['foundBushel']['bushelCode'];
  $foundBushelName = Units_GetNameByCode($foundBushelCode);
  $foundSeed = Seeder_GetBushelCrop($foundBushelCode);
  $AddedToInventory = $amf2->_bodys[0]->_value['data'][$n]['data']['foundBushel']['bushelsAddedToInventory'];

   if ($AddedToInventory > 0)
   {
   $Seeder_info['bushels'] += $AddedToInventory;
   AddLog2("Seeder_Harvest> found ".$AddedToInventory." ".$foundBushelName);
   //check if boosted
    if ($Seeder_info['booster_crop'] != $foundSeed)
    {
    Seeder_useBushel($foundBushelName);
    return 'OK';
    }
   }//if ($AddedToInventory > 0)
 }//if (isset($amf2->_bodys[0]->_value['data'][$n]['data']['foundBushel']))
} //for ($n = 0; $n <= $i; $n++)

$res = 'OK';
//======================================
    } else {
        if (isset($amf2->_bodys[0]->_value['data'][0])) {
            $res = $amf2->_bodys[0]->_value['data'][0]['errorType'] . " " . $amf2->_bodys[0]->_value['data'][0]['errorData'];
        }
    }
//======================================
            AddLog2("result $res");
            $count -= $i;
            $i = 0;
            unset($amf->_bodys[0]->_value[1]);
            $need_reload = true;

             if ($res === 'OK')
             {
             AddLog($OKstring);
             }
             else
             {
              if ($res)
              {
              AddLog("Error: $res on " . $OKstring);
               if ((intval($res) == 29) || (strpos($res, 'BAD AMF') !== false))
               { // Server sequence was reset
               DoInit();
               }
              }
             }

            unset($plotsstring, $OKstring);
            }//if (($i == $px_Setopts['bot_speed']) || ($i >= $count))

        }//foreach($plots as $plot)

    $T2 = time();
    $T2 -= $T;
    AddLog2("Seeder_Harvest> end ".$T2." Secs.");
    }//if ($count > 0)
}
//========================================================================================================================
//Seeder_burnBushel
//========================================================================================================================
function Seeder_burnBushel()//revised v1.1.2
{

global $Seeder_info;

$bushels = $Seeder_info['bushels'];
$maxbushels = $Seeder_info['maxbushels'];
AddLog2("Seeder_burnBushel> market space ".$bushels."/".$maxbushels);

 if ($maxbushels <= $bushels)
 {
 $seeds = Seeder_Read("seeds");
 $seeds = Seeder_ArrayFilter($seeds, 'bushel_count', '>', "0");
 $seeds = Seeder_ArrayOrder($seeds, 'bushel_count', 'DESC');

  foreach ($seeds as $seed)
  {
  AddLog2("Seeder_burnBushel> Burn Bushel ".$seed['bushel_name']);
  $res = Seeder_useBushel($seed['bushel_name']);
   if (($res == 'OK') || ($res == '6'))
   {
   return $res;
   }
  }
 unset($seeds);
 }

}
//========================================================================================================================
//Seeder_buyBushel
//========================================================================================================================
function Seeder_buyBushel($ItemCode, $uid)//revised v1.1.2
{

global $Seeder_info;

$res = 0;
$px_time = time();
$amf = new AMFObject("");
$amf->_bodys[0] = new MessageBody();
$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
$amf->_bodys[0]->responseURI = '/1/onStatus';
$amf->_bodys[0]->responseIndex = '/1';
$amf->_bodys[0]->_value[0] = GetAMFHeaders();
$amf->_bodys[0]->_value[2] = 0;

$amf->_bodys[0]->_value[1][0]['params'][0] = $uid;
$amf->_bodys[0]->_value[1][0]['params'][1] = $ItemCode;
$amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
$amf->_bodys[0]->_value[1][0]['functionName'] = "CraftingService.onClaimMarketStallItem";//fixed!

$res = RequestAMF($amf);
AddLog2("Seeder_buyBushel> Buy ".Units_GetNameByCode($ItemCode)." from ".$uid." result: ".$res);

if (($res == 'OK') || ($res == '6'))
{
$Seeder_info['bushels'] += 1;
}

#SaveAuthParams();
return $res;

}
//========================================================================================================================
//Seeder_ClaimReward
//========================================================================================================================
function Seeder_ClaimReward()//revised v1.1.4
{

global $Seeder_settings, $Seeder_info;

$Seeder_info = Seeder_Read("info");
$pendingRewards = $Seeder_info['pendingRewards'];

 if ($pendingRewards > 12) {$pendingRewards = 12;}
 if ($pendingRewards > 0)
 {
 $type = $Seeder_settings['reward_type'];
 $res = 0;
 $px_time = time();
 $amf = new AMFObject("");
 $amf->_bodys[0] = new MessageBody();
 $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
 $amf->_bodys[0]->responseURI = '/1/onStatus';
 $amf->_bodys[0]->responseIndex = '/1';
 $amf->_bodys[0]->_value[0] = GetAMFHeaders();
 $amf->_bodys[0]->_value[2] = 0;

 $amf->_bodys[0]->_value[1][0]['params'][0] = $type;
 $amf->_bodys[0]->_value[1][0]['params'][1] = $pendingRewards;
 $amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
 $amf->_bodys[0]->_value[1][0]['functionName'] = "CraftingService.onClaimReward";

 $res = RequestAMF($amf);
 AddLog2("Seeder_ClaimReward> claim ".$pendingRewards." rewards result: ".$res);
 #SaveAuthParams();
 }
 else
 {
 AddLog2("Seeder_ClaimReward> no rewards to claim");
 }

}
//========================================================================================================================
//Seeder_useBushel
//========================================================================================================================
function Seeder_useBushel($ItemName)//revised v1.1.4
{

global $userId, $vCnt63000, $Seeder_info;

$res = 0;
$px_time = time();
$amf = new AMFObject("");
$amf->_bodys[0] = new MessageBody();
$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
$amf->_bodys[0]->responseURI = '/1/onStatus';
$amf->_bodys[0]->responseIndex = '/1';
$amf->_bodys[0]->_value[0] = GetAMFHeaders();
$amf->_bodys[0]->_value[2] = 0;

$amf->_bodys[0]->_value[1][0]['params'][0] = 'use';
$amf->_bodys[0]->_value[1][0]['params'][1]['direction'] = 0;
$amf->_bodys[0]->_value[1][0]['params'][1]['deleted'] = false;
$amf->_bodys[0]->_value[1][0]['params'][1]['tempId'] = -1;
$amf->_bodys[0]->_value[1][0]['params'][1]['itemName'] = $ItemName;
$amf->_bodys[0]->_value[1][0]['params'][1]['className'] = 'CBushel';// v1.0.7
$amf->_bodys[0]->_value[1][0]['params'][1]['id'] = $vCnt63000 ++;
$amf->_bodys[0]->_value[1][0]['params'][1]['position'] = array('x'=>0, 'y'=>0, 'z'=>0);
$amf->_bodys[0]->_value[1][0]['params'][2][0]['isGift'] = false;
$amf->_bodys[0]->_value[1][0]['params'][2][0]['targetUser'] = $userId;
$amf->_bodys[0]->_value[1][0]['params'][2][0]['isFree'] = false;
$amf->_bodys[0]->_value[1][0]['params'][2][0]['storageId'] = -4;
$amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
$amf->_bodys[0]->_value[1][0]['functionName'] = "WorldService.performAction";

$Seeder_id++;
$res = RequestAMF($amf);
AddLog2("Seeder_useBushel> use ".$ItemName." result:".$res );

if (($res == 'OK') || ($res == '6'))
{
$Seeder_info['booster_crop'] = Seeder_GetBushelCrop_byname($ItemName);
$Seeder_info['booster_time'] = time() + (2 * 60 * 60);//+ 2 hours;
$Seeder_info['bushels'] -= 1;
Seeder_Write($Seeder_info,"info");
AddLog2("Seeder_useBushel> Active Bushel ".$ItemName);
return 'OK';
} else {
return $res;
}

}
//========================================================================================================================
//Seeder_instantGrow
//========================================================================================================================
function Seeder_instantGrow()//revised v1.1.0
{

Do_Biplane_Instantgrow();

}
//========================================================================================================================
//Seeder_Fertilize
//========================================================================================================================
function Seeder_Fertilize()//fixed v1.1.1
{

 //eA = consume_fertilize_all
 $inconbox = unserialize(file_get_contents(F('inconbox.txt')));
 $fertilize_count = @$inconbox['eA'];
 if (!$fertilize_count)
 {
 AddLog2("Seeder_Fertilize> No Fertilizer in Giftbox");
 }else
 {

 global $userId, $vCnt63000, $Seeder_info;
 #if($vCnt63000 == "") {$vCnt63000 = 63000;}
 $res = 0;
 $px_time = time();
 $amf = new AMFObject("");
 $amf->_bodys[0] = new MessageBody();
 $amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
 $amf->_bodys[0]->responseURI = '/1/onStatus';
 $amf->_bodys[0]->responseIndex = '/1';
 $amf->_bodys[0]->_value[0] = GetAMFHeaders();
 $amf->_bodys[0]->_value[2] = 0;

 $amf->_bodys[0]->_value[1][0]['params'][0] = 'use';
 $amf->_bodys[0]->_value[1][0]['params'][1]['deleted'] = false;
 $amf->_bodys[0]->_value[1][0]['params'][1]['direction'] = 0;
 $amf->_bodys[0]->_value[1][0]['params'][1]['tempId'] = -1;
 $amf->_bodys[0]->_value[1][0]['params'][1]['itemName'] = 'consume_fertilize_all';
 $amf->_bodys[0]->_value[1][0]['params'][1]['id'] = $vCnt63000 ++;
 $amf->_bodys[0]->_value[1][0]['params'][1]['position'] = array('x'=>0, 'y'=>0, 'z'=>0);
 $amf->_bodys[0]->_value[1][0]['params'][1]['className'] = 'CFertilizeAll';

 $amf->_bodys[0]->_value[1][0]['params'][2]['storageId'] = -1;
 $amf->_bodys[0]->_value[1][0]['params'][2]['isFree'] = false;
 $amf->_bodys[0]->_value[1][0]['params'][2]['targetUser'] = $userId;
 $amf->_bodys[0]->_value[1][0]['params'][2]['isGift'] = true;

 $amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
 $amf->_bodys[0]->_value[1][0]['functionName'] = "WorldService.performAction";

 $res = RequestAMF($amf);
 if($res=='OK')
 {
   AddLog2("Seeder_Fertilize> Fertilized all plots");
 }
 else
 {
   AddLog2("Seeder_Fertilize> Error: ".$res." no plot to fertilize?");
   DoInit();
 }

# SaveAuthParams();
 }

}

//========================================================================================================================
//Seeder_mastery_greenhouse
//========================================================================================================================
function Seeder_mastery_greenhouse()//added v1.1.6
{
global $Seeder_settings, $Seeder_info;

$greenhouse = Seeder_Read("greenhouse");
$reload = 0;

$seeds_all = Seeder_Read("seeds");
$seeds = Seeder_ArrayFilter($seeds_all, 'isHybrid', '==', '1');//fix
$seeds = Seeder_ArrayFilter($seeds_all, 'seedpackages_to_mastery', '>', '0');
$seeds = Seeder_ArrayFilter($seeds, 'seedpackage_UnlockState', '<=', $Seeder_info['greenhouse_level'] );
$seeds = Seeder_ArrayOrder($seeds, $Seeder_settings['seeds_order'], $Seeder_settings['seeds_sort']);

if (count($seeds) > 0)
{

 foreach ($seeds as $seed)
 {
  $seeds_array[] = $seed;
 }

unset($seeds_all);unset($seeds);
//======================================
if (count($seeds_array) > 0 )
{
 $x = 0;
 for ($i = 0; $i < $Seeder_info['greenhouse_trays']; $i++)
 {
  if (!@$greenhouse['trays'][$i]['trayResult'])
  {
  Seeder_start_greenhouse($seeds_array[$x],$i);

  $seeds_array[$x]['seedpackages_to_mastery'] -= 50;
  if ($seeds_array[$x]['seedpackages_to_mastery'] <= 0) {$x += 1;}

  $reload = 1;
  }
 }
}
//======================================

if ($reload == 1) {DoInit();Seeder_loadWorld();}

} else {AddLog2("Seeder_mastery_greenhouse> Congratulations, All seeds packages done!!!");}//if (count($seeds) > 0)

}
//========================================================================================================================
//Seeder_default_greenhouse
//========================================================================================================================
function Seeder_default_greenhouse()//added v1.1.7
{
global $Seeder_settings, $Seeder_info;

$greenhouse = Seeder_Read("greenhouse");
$seeds_all = Seeder_Read("seeds");
$seeds_array[] = $seeds_all[$Seeder_settings['default_greenhouse']];
unset($seeds_all);

$reload = 0;

 for ($i = 0; $i < $Seeder_info['greenhouse_trays']; $i++)
 {
  if (!@$greenhouse['trays'][$i]['trayResult'])
  {
  Seeder_start_greenhouse($seeds_array[0],$i);
  $reload = 1;
  }
 }
//======================================

if ($reload == 1) {DoInit();Seeder_loadWorld();}

}
//========================================================================================================================
//Seeder_harvest_greenhouse
//========================================================================================================================
function Seeder_start_greenhouse($seeds_array,$tray)//added v1.1.6
{

global $Seeder_info;
$greenhouse = Seeder_Read("greenhouse");

$res = 0;
$px_time = time();
$amf = new AMFObject("");
$amf->_bodys[0] = new MessageBody();
$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
$amf->_bodys[0]->responseURI = '/1/onStatus';
$amf->_bodys[0]->responseIndex = '/1';
$amf->_bodys[0]->_value[0] = GetAMFHeaders();
$amf->_bodys[0]->_value[2] = 0;

$amf->_bodys[0]->_value[1][0]['params'][0] = $greenhouse['featureName'];
$amf->_bodys[0]->_value[1][0]['params'][1] = $tray;

$x = 0;
foreach ($seeds_array['genealogy'] as $gen)
{
$amf->_bodys[0]->_value[1][0]['params'][2][$x]['code'] = $gen['code'];
#$amf->_bodys[0]->_value[1][0]['params'][2][$x]['quantity'] = $seeds_array['seedpackages_to_mastery'];
$amf->_bodys[0]->_value[1][0]['params'][2][$x]['quantity'] = 50;
$x += 1;
}

$amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
$amf->_bodys[0]->_value[1][0]['functionName'] = "BreedingService.beginNewBreedingProject";

$res = RequestAMF($amf);
AddLog2("Seeder_start_greenhouse> Tray #".$tray." Start: ".$seeds_array['seedpackages_to_mastery']." ".$seeds_array['seedpackage_realname']." result: ".$res);

#DoInit();
#Seeder_loadWorld();

}
//========================================================================================================================
//Seeder_harvest_greenhouse
//========================================================================================================================
function Seeder_harvest_greenhouse()//added v1.1.6
{
global $Seeder_info;
$greenhouse = Seeder_Read("greenhouse");
$reload = 0;

for ($i = 0; $i < $Seeder_info['greenhouse_trays']; $i++)
{
 $seedpackage = @$greenhouse['trays'][$i]['trayResult'];
 if ($seedpackage)
 {
  $startTime = $greenhouse['trays'][$i]['tray']['startTime'];
  $endTime = ($startTime + $greenhouse['breedingDuration']);
  $Friends = @$greenhouse['trays'][$i]['tray']['helpingFriendIds'];
  if (count($Friends) > 0) {$endTime = $endTime - (count($Friends) * 24*60*60);}
  $TimeLeft = Seeder_TimeLeft(time(), $endTime);
  
  if ($TimeLeft == 0)
  {
  AddLog2("Seeder_harvest_greenhouse> tray #".$i.": ".Units_GetRealnameByCode($seedpackage)." - Ready!");
  Seeder_harvest_tray($i);
  $reload = 1;
  } else {  AddLog2("Seeder_harvest_greenhouse> tray #".$i.": ".Units_GetRealnameByCode($seedpackage)." - Harvest in ".$TimeLeft);}
  
 }
}

if ($reload == 1) {DoInit();Seeder_loadWorld();}
}
//========================================================================================================================
//Seeder_harvest_tray
//========================================================================================================================
function Seeder_harvest_tray($tray)//added v1.1.6
{

global $Seeder_info;
$greenhouse = Seeder_Read("greenhouse");

$res = 0;
$px_time = time();
$amf = new AMFObject("");
$amf->_bodys[0] = new MessageBody();
$amf->_bodys[0]->targetURI = 'FlashService.dispatchBatch';
$amf->_bodys[0]->responseURI = '/1/onStatus';
$amf->_bodys[0]->responseIndex = '/1';
$amf->_bodys[0]->_value[0] = GetAMFHeaders();
$amf->_bodys[0]->_value[2] = 0;

$amf->_bodys[0]->_value[1][0]['params'][0] = $greenhouse['featureName'];
$amf->_bodys[0]->_value[1][0]['params'][1] = $tray;
$amf->_bodys[0]->_value[1][0]['sequence'] = GetSequense();
$amf->_bodys[0]->_value[1][0]['functionName'] = "BreedingService.finishBreedingProject";

$res = RequestAMF($amf);
AddLog2("Seeder_harvest_tray> Harvested tray #".$tray.": ".Units_GetRealnameByCode($greenhouse['trays'][$tray]['trayResult'])." result: ".$res);

#DoInit();
#Seeder_loadWorld();

}
//========================================================================================================================
//Functions
//========================================================================================================================

function Sedeer_XMLToArray($xml,$flattenValues=true,$flattenAttributes = true,$flattenChildren=true,$valueKey='@value',$attributesKey='@attributes',$childrenKey='@children')//added 1.1.4

{
$return = array();

if(!($xml instanceof SimpleXMLElement)){return $return;}

$name = $xml->getName();
$_value = trim((string)$xml);
if(strlen($_value)==0){$_value = null;};

if($_value!==null)
 {
  if(!$flattenValues){$return[$valueKey] = $_value;} else{$return = $_value;}
 }

$children = array();
$first = true;

foreach($xml->children() as $elementName => $child)
{
$value = Sedeer_XMLToArray($child, $flattenValues, $flattenAttributes, $flattenChildren, $valueKey, $attributesKey, $childrenKey);

 if(isset($children[$elementName]))
 {
  if($first)
  {
  $temp = $children[$elementName];
  unset($children[$elementName]);
  $children[$elementName][] = $temp;
  $first=false;
  }
 $children[$elementName][] = $value;
 } else {$children[$elementName] = $value;}
}

if(count($children)>0)
{
 if(!$flattenChildren){$return[$childrenKey] = $children;}
 else{$return = array_merge($return,$children);}
}

$attributes = array();

foreach($xml->attributes() as $name=>$value)
{
$attributes[$name] = trim($value);
}

if(count($attributes)>0)
{
 if(!$flattenAttributes){$return[$attributesKey] = $attributes;}
 else{$return = array_merge($return, $attributes);}
}

return $return;

}
//======================================
function Seeder_replace($str)//revised v1.0.6
{
$str2 = str_replace("&amp;", "and", $str);
$str2 = str_replace("'", " ", $str2);
$str2 = str_replace("Cascadian Farmand#xAE;  ", "", $str2);//Organic Blueberries Fix
$str2 = str_replace("_Title", "", $str2);//Chinese Fix
$str2 = str_replace("bushelJob_", "", $str2);//Chinese Fix
$str2 = ltrim($str2);
$str2 = rtrim($str2);
return $str2;
}
//======================================
function Seeder_GetBushelCrop($code = "")//revised v1.1.2
{
$units = Units_GetByType('bushel',true);
 if ($code)
 {
 $resunits = array();
 foreach ($units as $unit)
 if ($unit['code'] == $code)
 return Units_GetNameByCode($unit['crop']);
 }
 else
 {
 return $code;
 }
unset($units);
}
//======================================
function Seeder_GetBushelCrop_byname($name = "")//revised v1.1.2
{
$units = Units_GetByType('bushel',true);
 if ($name)
 {
 $resunits = array();
 foreach ($units as $unit)
 if ($unit['name'] == $name)
 return Units_GetNameByCode($unit['crop']);
 }
 else
 {
 return $name;
 }
unset($units);
}
//======================================
function Seeder_GetCropBushel($code = "")//revised v1.1.2
{
$units = Units_GetByType('bushel',true);
 if ($code)
 {
 $resunits = array();
 foreach ($units as $unit)
 if ($unit['crop'] == $code)
 return Units_GetNameByCode($unit['code']);
 }
 else
 {
 return $code;
 }
unset($units);
}
//======================================
function Seeder_TimeLeft($start, $end)//added v1.1.4
{

$time_left = $end - $start;

if($time_left > 0)
{
		$days = floor($time_left / 86400);
		$time_left = $time_left - $days * 86400;
		$hours = floor($time_left / 3600);
		$time_left = $time_left - $hours * 3600;
		$minutes = floor($time_left / 60);
		//$seconds = $time_left - $minutes * 60;

} else {return 0;}

return ($days > 0 ? $days."d " : "").($hours > 0 ? $hours."h " : '').($minutes > 0 ? $minutes."m " : '');

}
//======================================
function Seeder_TimeZone($time)//added v1.1.4
{

$localOffset = date('Z');
$serverOffset = (-5 * 60 * 60);//(GMT -5:00) EST - Eastern Standard Time (U.S. & Canada)
$new_time = ($time - $serverOffset + $localOffset);

return $new_time;

}
//======================================
function Seeder_Put($array, $filename)//added v1.1.4
{
$save_str = serialize($array);
file_put_contents(Seeder_dbPath.PluginF($filename.".txt"),$save_str);
}
//======================================
function Seeder_Write($array, $filename)//revised v1.0.6
{
$save_str = serialize($array);
$f = fopen(Seeder_dbPath.PluginF($filename.".txt"), "w+");
fputs($f, $save_str, strlen($save_str));
fclose($f);
}
//======================================
function Seeder_Read($filename)//revised v1.0.6
{
return @unserialize(file_get_contents(Seeder_dbPath.PluginF($filename.".txt")));
}
//======================================
function Seeder_WriteDefault($array, $filename)//revised v1.0.6
{
$save_str = serialize($array);
$f = fopen(Seeder_dbPath.$filename.".txt", "w+");
fputs($f, $save_str, strlen($save_str));
fclose($f);
}
//======================================
function Seeder_ReadDefault($filename)//revised v1.0.6
{
return @unserialize(file_get_contents(Seeder_dbPath.$filename.".txt"));
}
//======================================
function Seeder_ArrayOrder($data, $field, $sort)//revised v1.0.6
{
 if (sizeof($data) > 0 )
 {
  $code = "return strnatcmp(\$a['$field'], \$b['$field']);";
  uasort($data, create_function('$a,$b', $code));
  if ($sort == "DESC") {$data = array_reverse($data);}
 }
  return $data;
}
//======================================
function Seeder_ArrayFilter($array, $index, $operator, $value)//revised v1.0.6
{
 if (sizeof($array) > 0 )
 {
  $newarray = array();
  foreach(array_keys($array) as $key)
  {
  $temp[$key] = $array[$key][$index];

   if ($operator == "!=") {if ($temp[$key] != $value) {$newarray[$key] = $array[$key];}}
   elseif ($operator == "<>") {if ($temp[$key] <> $value) {$newarray[$key] = $array[$key];}}
   elseif ($operator == ">") {if ($temp[$key] > $value) {$newarray[$key] = $array[$key];}}
   elseif ($operator == "<") {if ($temp[$key] < $value) {$newarray[$key] = $array[$key];}}
   elseif ($operator == ">=") {if ($temp[$key] >= $value) {$newarray[$key] = $array[$key];}}
   elseif ($operator == "<=") {if ($temp[$key] <= $value) {$newarray[$key] = $array[$key];}}
   else {if ($temp[$key] == $value) {$newarray[$key] = $array[$key];}}
  }
  return $newarray;
 }
}
//======================================
function Seeder_GetPlanted()//revised v1.1.2
{

 $plots = GetObjects('Plot');

 $array = array();
 foreach ($plots as $plot)
 {
  if (($plot['state'] == 'grown') || ($plot['state'] == 'ripe') || ($plot['state'] == 'planted'))
  {
   $name = $plot['itemName'];
   if (!isset($array[$name]['name']))
   {
   $array[$name]['name'] = $name;
   $array[$name]['realname'] = Units_GetRealnameByName($plot['itemName']);
   $array[$name]['count'] = 1;
   }
   else
   {
   $array[$name]['count'] += 1;
   }
  }
 }
 unset($plots);
 return $array;

}
//======================================
function Seeder_GetPlotsTime()//revised v1.1.2
{

 $plots = GetObjects('Plot');
 $plots = Seeder_ArrayFilter($plots, 'state', '!=', 'plowed');
 $plots = Seeder_ArrayFilter($plots, 'state', '!=', 'fallow');

 $array = array();
 foreach ($plots as $plot)
  {
   $plantTimeID = $plot['itemName']."-".($plot['plantTime']/1000);
   if (!isset($array[$plantTimeID]['plantTimeID']))
   {
   $array[$plantTimeID]['plantTimeID'] = $plantTimeID;
   $array[$plantTimeID]['plantTime'] = ($plot['plantTime']/1000);
   $array[$plantTimeID]['itemName'] = $plot['itemName'];
   $array[$plantTimeID]['realname'] = Units_GetRealnameByName($plot['itemName']);
   $array[$plantTimeID]['state'] = $plot['state'];
   $array[$plantTimeID]['count'] = 1;
   }
   else
   {
   $array[$plantTimeID]['count'] += 1;
   }
  }
 unset($plots);
 ksort($array);
 return $array;

}
//======================================
function Seeder_GetPlotsFree()//revised v1.1.2
{

 $plots = GetObjects('Plot');
 
 $array = array();
 foreach ($plots as $plot)
  {
   $state = $plot['state'];
   if (($state == "plowed") || ($state == "fallow"))
   {
    if (!isset($array[$state]['state']))
    {
    $array[$state]['state'] = $plot['state'];
    $array[$state]['itemName'] = $plot['itemName'];
    $array[$state]['count'] = 1;
    }
    else
    {
    $array[$state]['count'] += 1;
    }
   }
  }
 unset($plots);
 ksort($array);
 return $array;

}
//======================================
function Seeder_GetMarket($itemCode)//changed v1.1.2
{
$marketStall = Seeder_Read("marketStall");
$uids = array();

 foreach ($marketStall as $item)
 {
 $uid = number_format($item['uid'], 0, '', '');
  foreach($item['inventory'] as $inventory)
  {
   if ($inventory['itemCode'] == $itemCode)
   {
    if ($inventory['timeStamp'] >  time())
    {
    $res = Seeder_buyBushel($itemCode, $uid);
     if (($res == 'OK') || ($res == '6'))
     {
     break;
     return 'OK';
     }
    }
   }
  }
 }

}
//======================================
function Seeder_seed_genealogy($itemCode)//added v1.1.6
{


}
//======================================
function Seeder_userProfile()//added v1.1.6
{
$flashVars = file_get_contents(F('flashVars.txt'));
preg_match('/var g_userInfo = \{([^}]*)\}/sim', $flashVars, $flash);
preg_match_all('/"([^"]*)":"([^"]*)"/im', $flash[1], $fr);
$newarray = array_combine($fr[1], $fr[2]);

 foreach ($newarray as $key => $value)
 {
 $newarray[$key] = str_replace('\\/', '/', $value);
 }

return $newarray;

}
//======================================
function Seeder_CheckObject($itemName)//added v1.1.4
{
#$world = unserialize(file_get_contents(F('world.txt')));
#$objects = @$world['data'][0]['data']['userInfo']['world']['objectsArray'];
$objectsArray = @unserialize(file_get_contents(F('objects.txt')));

foreach ($objectsArray as $object)
if ($object['itemName'] == $itemName)
{
return 1;
}

return 0;
unset($objectsArray);
}
//======================================
function Seeder_worldtype()//added v1.1.8
{
$worldtype = @file_get_contents(F('worldtype.txt'));
if(strlen($worldtype) == 0) {$worldtype = 'farm';}
return $worldtype;
}
//======================================
function Seeder_worldname()//added v1.1.8
{
$worldtype = Seeder_worldtype();
if($worldtype == 'england') {$worldname = 'English Countryside';} else {$worldname = 'Farmville';}
return $worldname;

}
//======================================
function Seeder_error($msg)//revised v1.0.6
{
AddLog2("\n".'============= Seeder ERROR ============='."\n".$msg."\n".'============= Seeder ERROR =============');
}
//========================================================================================================================
?>
