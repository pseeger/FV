<?php
//========================================================================================================================
//Seeder_form.php
//by N1n9u3m
//========================================================================================================================
//Seeder_form
//========================================================================================================================
function Seeder_form()//revised 1.1.5
{
$timenow = time();
global $Seeder_settings, $Seeder_info;
$Seeder_settings = Seeder_Read("settings");
$Seeder_info = Seeder_Read("info");
$Seeder_msg = "&nbsp;";

//======================================
//submit form
//======================================
#print_r($_GET);
if (isset($_GET['save_action']))
{
//======================================
//Save Settings
//======================================
 if (isset($_GET['save_settings']))
 {
 if (@$_GET['auto_mastery']) {$auto_mastery = 1;} else {$auto_mastery = 0;}
 if (@$_GET['mastery_adjustment']) {$mastery_adjustment = 1;} else {$mastery_adjustment = 0;}
 if (@$_GET['force_planting']) {$force_planting = 1;} else {$force_planting = 0;}
 if (@$_GET['bushel_booster']) {$bushel_booster = 1;} else {$bushel_booster = 0;}
 if (@$_GET['default_settings']) {$default_settings = 1;} else {$default_settings = 0;}
 if (@$_GET['claim_reward']) {$claim_reward = 1;} else {$claim_reward = 0;}
 if (@$_GET['fertilize']) {$fertilize = 1;} else {$fertilize = 0;}
 if (@$_GET['end_job']) {$end_job = 1;} else {$end_job = 0;}
 if (@$_GET['auto_coop']) {$auto_coop = 1;} else {$auto_coop = 0;}
 if (@$_GET['coop_plant']) {$coop_plant = 1;} else {$coop_plant = 0;}
 if (@$_GET['keep_planted']) {$keep_planted = 1;} else {$keep_planted = 0;}
 if (@$_GET['mastery_greenhouse']) {$mastery_greenhouse = 1;} else {$mastery_greenhouse = 0;}
 if (@$_GET['harvest_greenhouse']) {$harvest_greenhouse = 1;} else {$harvest_greenhouse = 0;}

# if ($auto_coop == 1) {$auto_mastery = 0;}
# if ($auto_mastery == 1) {$auto_coop = 0;}

 $Seeder_settings['auto_mastery'] = $auto_mastery;
 $Seeder_settings['seeds_order'] = @$_GET['seeds_order'];
 $Seeder_settings['seeds_sort'] = @$_GET['seeds_sort'];
 $Seeder_settings['mastery_adjustment'] = $mastery_adjustment;
 $Seeder_settings['force_planting'] = $force_planting;
 $Seeder_settings['bushel_booster'] = $bushel_booster;
 $Seeder_settings['claim_reward'] = $claim_reward;
 $Seeder_settings['fertilize'] = $fertilize;
 $Seeder_settings['auto_coop'] = $auto_coop;
 $Seeder_settings['coop_mode'] = @$_GET['coop_mode'];
 $Seeder_settings['coop_host'] = @$_GET['coop_host'];
 $Seeder_settings['coop_growTime'] = @$_GET['coop_growTime'];
 $Seeder_settings['coop_follow'] = @$_GET['coop_follow'];
 $Seeder_settings['end_job'] = $end_job;
 $Seeder_settings['keep_planted'] = $keep_planted;
 $Seeder_settings['coop_plant'] = $coop_plant;
 $Seeder_settings['mastery_greenhouse'] = $mastery_greenhouse;
 $Seeder_settings['harvest_greenhouse'] = $harvest_greenhouse;
 $Seeder_settings['harvest_greenhouse'] = $harvest_greenhouse;
 if (@$_GET['default_greenhouse'] == "NULL") {unset($Seeder_settings['default_greenhouse']);} else {$Seeder_settings['default_greenhouse'] = @$_GET['default_greenhouse'];}

 $Seeder_settings['reward_type'] = @$_GET['reward_type'];
 $Seeder_settings['timeformat'] = @$_GET['timeformat'];

 Seeder_Write($Seeder_settings,"settings");

  if ($auto_mastery == 1)
  {
  Seeder_mastery();
  }

  if ($auto_coop == 1)
  {
  //Seeder_CoopJobs();
  if ($Seeder_settings['coop_plant'] == 1) {Seeder_plant_quest();}
  }

  Seeder_box("Seeder", "<b>Seeder Settings saved</b>", "info");
 }
//======================================
//instantGrow
//======================================
 if (isset($_GET['fly']) && @$_GET['fly'] != "0")
 {
 Seeder_instantGrow();
 DoInit();
 Seeder_loadWorld();
 Seeder_box("Seeder", "<b>Instant Grow applied!</b>", "info");
 }
//======================================
//Bushel Booster
//======================================
 if (isset($_GET['bushel']) && @$_GET['bushel'] != "0")
 {
 Seeder_useBushel($_GET['bushel']);
 DoInit();
 Seeder_loadWorld();
 Seeder_box("Seeder", "<b>Bushel Booster applied!</b>", "info");
 }
//======================================
//Load Word
//======================================
 if (isset($_GET['load_word']) && @$_GET['load_word'] != "0")
 {
 DoInit();
 Seeder_loadWorld();
 if ($Seeder_settings['auto_mastery'] == 1){Seeder_mastery();}
 if ($Seeder_settings['coop_plant'] == 1) {Seeder_plant_quest();}
 Seeder_box("Seeder", "<b>Farm updated</b>", "info");
 }
 //======================================
//Load Jobs
//======================================
 if (isset($_GET['load_jobs']) && @$_GET['load_jobs'] != "0")
 {
 Seeder_loadJobs();
 if ($Seeder_settings['coop_plant'] == 1) {Seeder_plant_quest();}
 Seeder_box("Seeder", "<b>Jobs updated</b>", "info");
 }
 //======================================
//End Job
//======================================
 if (isset($_GET['end_coop']) && @$_GET['end_coop'] != "0")
 {
 Seeder_end_quest();
 //Seeder_loadJobs();//include in end_quest
 Seeder_box("Seeder", "<b>Job Closed!</b>", "info");
 }
  //======================================
//Start Job
//======================================
 if (isset($_GET['start_job']) && @$_GET['start_job'] != "0")
 {
 Seeder_start_quest($_GET['start_job']);
 $Seeder_settings['coop_host'] = @$_GET['start_job'];
 Seeder_Write($Seeder_settings,"settings");

 if ($Seeder_settings['coop_plant'] == 1) {Seeder_plant_quest();}
// Seeder_loadJobs();//include in start_job
 Seeder_box("Seeder", "<b>Job Started!</b>", "info");
 }
 //======================================
//Harvest Tray
//======================================
 if (isset($_GET['harvest_tray']) && @$_GET['harvest_tray'] != "0")
 {
 Seeder_harvest_tray($_GET['harvest_tray'] - 1);
 DoInit();
 Seeder_loadWorld();

 Seeder_box("Seeder", "<b>Tray Harvested!</b>", "info");
 }
//======================================
//save keep_list//revised v1.1.5
//======================================
if (isset($_GET['clear_planting2']))
{
if (file_exists(F('seed.txt'))) {unlink(F('seed.txt'));}
}
//======================================

if (isset($_GET['clear_planting']))
{
$seedlist = array();
$changed_seedlist = true;
}

//======================================
if (isset($_GET['add_plant_s']))
{
$seedlist = Seeder_Read("seedlist");
$changed_seedlist = true;

$defaultseed = @$_GET['seed_default'];
$plant_count = @$_GET['plant_count'];
$item = @$_GET['plant_list'];

 if ($defaultseed)
 {
 $seedlist['default'][0] = $item;
 } else {
 $seedlist[$item][0] = $item;
 $seedlist[$item][1] = $plant_count;
 }
}
//======================================
if (isset($_GET['del_plant']))
{
$seedlist = Seeder_Read("seedlist");
$changed_seedlist = true;

 if (!is_array(@$_GET['seed_list']))
 {
 $del_plants = array();
 $del_plants[] = @$_GET['seed_list'];
 }
 else
 $del_plants = @$_GET['seed_list'];

 foreach ($del_plants as $del_plant_item)
 {
  if ($del_plant_item == "default")
  {
  unset($seedlist['default']);
  } else {
  unset($seedlist[$del_plant_item]);
  }
 }

}
//======================================
if ($changed_seedlist)
{
Seeder_Write($seedlist,"seedlist");
Seeder_box("Seeder", "<b>Keep planted list saved</b>", "info");
}
unset($seedlist);
//======================================


$seed_list = @explode(';', trim(file_get_contents(F('seed.txt'))));
if ((count($seed_list) == 1) && empty($seed_list[0]))
$seed_list = array();
$changed_seed_list = false;

if (isset($_GET['clear_planting2']))
{
$seed_list = array();
$changed_seed_list = true;
}

if (isset($_GET['add_plant_s2']))
{
$defaultseed = @$_GET['seed_default2'];
$plant_count = @$_GET['plant_count2'];
$item = @$_GET['plant_list2'];

if ($defaultseed) { $seed_list[] = "$item:Default"; }
else { $seed_list[] = "$item:$plant_count"; }
$changed_seed_list = true;
}

if (isset($_GET['del_plant2']))
{
 if (!is_array(@$_GET['seed_list2']))
 {
 $del_plants = array();
 $del_plants[] = @$_GET['seed_list2'];
 }
 else
 $del_plants = @$_GET['seed_list2'];

 $group_list = array();
 $last_item = @$seed_list[0];
 $item_count = 0;
 foreach ($seed_list as $seed_item)
  {
   if ($last_item != $seed_item)
   {
   $group_list[] = array('name'=>$last_item, 'count'=>$item_count);
   $last_item = $seed_item;
   $item_count = 1;
   }
   else
   {
   $item_count ++;
   }
  }

 if ($last_item)
 $group_list[] = array('name'=>$last_item, 'count'=>$item_count);
 rsort($del_plants);

 foreach ($del_plants as $del_plant)
 unset($group_list[$del_plant]);

 $seed_list = array();

 foreach ($group_list as $item)
 for ($i = 0; $i < $item['count']; $i ++)
 $seed_list[] = $item['name'];

 $changed_seed_list = true;
}

if ($changed_seed_list)
{
$f = fopen(F('seed.txt'), "w+");
$seed_data = implode(';', $seed_list);
fwrite($f, $seed_data, strlen($seed_data));
fclose($f);
//file_put_contents(F('seed.txt'),implode(';', $seed_list));
Seeder_box("Seeder", "<b>Seed list successfully saved</b>", "info");
}



}//if (isset($_GET['save_action']))

//======================================
//html
//======================================
?>
<html><head><title>Seeder FarmvilleBot Plugin</title>

<script language="javascript">

function ShowAddForm()
{
add_plant_div.style.display = "";
var mLIST=document.getElementById("plant_list");
document.getElementById("mastery_info").innerHTML = mLIST.options[mLIST.selectedIndex].mastery;
}

function ShowAddForm2()
{
add_plant_div2.style.display = "";
var mLIST=document.getElementById("plant_list2");
document.getElementById("mastery_info2").innerHTML = mLIST.options[mLIST.selectedIndex].mastery2;
}

function HideAddForm()
{
add_plant_div.style.display = "none";
}

function HideAddForm2()
{
add_plant_div2.style.display = "none";
}

function ShowStats()
{
stats_div.style.display = "";
}

function HideStats()
{
stats_div.style.display = "none";
}

function ChangeMasteryInfo()
{
var mLIST=document.getElementById("plant_list");
document.getElementById("mastery_info").innerHTML = mLIST.options[mLIST.selectedIndex].mastery;
}

function ChangeMasteryInfo2()
{
var mLIST=document.getElementById("plant_list2");
document.getElementById("mastery_info2").innerHTML = mLIST.options[mLIST.selectedIndex].mastery2;
}

function Update()
{
 if (confirm("Manual Update will take a few minutes\nErrors can happen if the bot is working\nclick OK and please wait..."))
 {
 document.getElementById("update_bt").disabled = true;
 document.getElementById("instantGrow_bt").disabled = true;
 document.getElementById("update_job_bt").disabled = true;
 document.getElementById("load_word").value = "1";
 main_form.submit();
 }
}

function Update_Job()
{
 if (confirm("Manual Update Job will take a few minutes\nErrors can happen if the bot is working\nclick OK and please wait..."))
 {
 document.getElementById("update_bt").disabled = true;
 document.getElementById("instantGrow_bt").disabled = true;
 document.getElementById("update_job_bt").disabled = true;
 document.getElementById("load_jobs").value = "1";
 main_form.submit();
 }
}

function Booster(item)
{
 if (confirm("Manual Booster will take a few minutes\nErrors can happen if the bot is working\nclick OK and please wait..."))
 {
 document.getElementById("bushel").value = item.name;
 document.getElementById("update_bt").disabled = true;
 document.getElementById("instantGrow_bt").disabled = true;
 document.getElementById("update_job_bt").disabled = true;
 item.disabled = true;
 main_form.submit();
 }
}

function js_instantGrow(item)
{
 if (confirm("Instant Grow "+item.iGrow_cost+"\nclick OK and please wait..."))
 {
 document.getElementById("fly").value = 1;
 document.getElementById("update_bt").disabled = true;
 document.getElementById("instantGrow_bt").disabled = true;
 document.getElementById("update_job_bt").disabled = true;
 item.disabled = true;
 main_form.submit();
 }
}

function Start_Job(item)
{
 if (confirm("Start Co-Op Job now!\n\nif the auto co-op and plant co-op are disabled, you must manually add the seeds\n\nclick OK and please wait..."))
 {
 document.getElementById("start_job").value = item.name;
 document.getElementById("update_bt").disabled = true;
 document.getElementById("instantGrow_bt").disabled = true;
 document.getElementById("update_job_bt").disabled = true;
 item.disabled = true;
 main_form.submit();
 }
}

function End_Job(item)
{
 if (item.value == 'Finish Co-op')
 {
 document.getElementById("end_coop").value = 1;
 document.getElementById("update_bt").disabled = true;
 document.getElementById("instantGrow_bt").disabled = true;
 document.getElementById("update_job_bt").disabled = true;
 item.disabled = true;
 main_form.submit();
 }
 else
 {
  if (confirm("If you "+item.value+" the Co-Op Job now,\n you will lose medals.\nclick OK and please wait..."))
  {
  document.getElementById("end_coop").value = 1;
  document.getElementById("update_bt").disabled = true;
  document.getElementById("instantGrow_bt").disabled = true;
  document.getElementById("update_job_bt").disabled = true;
  item.disabled = true;
  main_form.submit();
  }
 }
}

function Harvest_Tray(item)
{

 document.getElementById("harvest_tray").value = item;
 main_form.submit();

}
</script>
<style>

/* Forms */
form {
        margin-top: 0px;
        margin-left: 0px;
        margin-right: 0px;
        margin-bottom: 0px
}

input, select, textarea, fieldset {
        font-family: Tahoma,Arial,Helvetica,sans-serif;
        font-size:8pt;
        color: Black
}

input.off, textarea.off, select.off, off, option.off {
        font-family: Tahoma,Arial,Helvetica,sans-serif;
        font-size: 8pt;
        color: Gray
}

input.off2, textarea.off2, select.off2, off2, option.off2 {
        font-family: Tahoma,Arial,Helvetica,sans-serif;
        font-size: 8pt;
        color: Black;
        background-color: #C0C0C0
}

div.msgbox{
        position:absolute;
        border-top:1px solid #D4D0C8;
        border-bottom: 1px solid #404040;
        border-left: 1px solid #D4D0C8;
        border-right: 1px solid #404040;
        background-color: #D4D0C8
}

div.innerbox{
        width: 100%;
        height: 100%;
        border-top: 1px solid #FFFFFF;
        border-bottom: 1px solid #808080;
        border-left: 1px solid #FFFFFF;
        border-right: 1px solid #808080;
        padding: 2px
}

.menu {
        font-weight: bold;
        background-color: #D4D0C8;
        cursor: hand;
        padding: 2px 5px 2px 5px;
}

.menu-sel {
        font-weight: bold;
        color: #FFFFFF;
        background-color: #6666FF;
        cursor: hand;
        padding: 2px 5px 2px 5px;
}

.submenu {
        font-weight: bold;
        background-color: #D4D0C8;
        cursor: hand;
        padding: 2px 5px 2px 5px;
}

.submenu-sel {
        font-weight: bold;
        color: #FFFFFF;
        background-color: #0A246A;
        cursor: hand;
        padding: 2px 5px 2px 5px;
}

a {
text-decoration: none
}

</style>

</head>
<body topmargin="0" leftmargin="0" rightmargin="0" bottommargin="0" marginwidth="0" marginheight="0" text="#000000" bgcolor="#FFFFE1">

<div>
<font size='1' face='Tahoma'>
<!-------------------------------------- form submit -->
<form action="main.php" id="main_form" method="get">
<input type="hidden" name="save_action" value="1">
<input type="hidden" name="load_word" value="0">
<input type="hidden" name="bushel" value="0">
<input type="hidden" name="fly" value="0">
<input type="hidden" name="end_coop" value="0">
<input type="hidden" name="load_jobs" value="0">
<input type="hidden" name="start_job" value="0">
<input type="hidden" name="harvest_tray" value="0">

<table border="0" width="100%" cellspacing="0" cellpadding="2">
        <tr>
                <td align="center" height="30" bgcolor="#0A246A">
                <font size="2" face="Tahoma" color="#FFFFFF"><b>
                <a target="_blank" href="http://farmvillebot.net/forum/viewtopic.php?t=7164" style="text-decoration: none; color:white">
                Seeder Plugin v<?php echo Seeder_version;?></b>  by n1n9u3m
                </a></font></td>
        </tr>
</table>
<?php
//======================================
//load player info and settings
//======================================
if ((!PX_VER_PARSER) || (PX_VER_PARSER < Seeder_parser))
{
Seeder_box("Seeder Error", "<b>Incorrect Parser Version</b><br><br>Seeder needs parser v".Seeder_parser."<br>Bot running parser v".PX_VER_PARSER."<br>Seeder disabled.", "critical");
} else {

if (!file_exists(Seeder_dbPath.PluginF('info.txt')))
{
Seeder_box("Seeder Warning", "<b>User unknown, First time?</b><br><br>Please wait <b>Seeder load farm</b> and refresh plugin<br>or Restart FarmvilleBot", "warning");
} else {
//======================================
//Seeder load all array
//======================================
$crafting = Seeder_Read("crafting");
$pendingRewards =  $crafting['pendingRewards'];
if (count($pendingRewards) > 0)
 {
 foreach ($pendingRewards as $Reward)
 {
 $Rewards += $Reward['count'];
 }
}
unset($pendingRewards);unset($crafting);

?>
<table border="0" width="100%" cellspacing="0" cellpadding="2">
        <tr>
        <td align="left" height="16" bgcolor="#D4D0C8">
<!--------------------------------------------------------------------------------------------------------------------------------------------------------->
<!-- info bar -->
<table border='0' cellspacing='0' cellpadding='2'>
<tr>
<td align='left'><img border="0" width='18' src="http://www.wtzclock.com/images/flags/<?php echo strtolower($Seeder_info['geoip']);?>.gif"></td><td align='left'><font size='1' face='Tahoma'><b><?php echo $Seeder_info['name'];?></b> (<?php echo $Seeder_info['userId'];?>)</font></td>
<td align='left'><img border="0" width='18' src="<?php echo Seeder_ShowImage('/assets/consumables/consume_xp_icon.png');?>"></td><td align='left'><font size='1' face='Tahoma'><b><?php echo $Seeder_info['level'];?></b> (<?php echo $Seeder_info['xp'];?>)</font></td>
<td align='left'><img border="0" width='18' src="<?php echo Seeder_ShowImage('/assets/consumables/consume_coins_icon.png');?>"></td><td align='left'><font size='1' face='Tahoma'><b><?php echo $Seeder_info['gold'];?></b></font></td>
<td align='left'><img border="0" width='18' src="<?php echo Seeder_ShowImage('/assets/consumables/consume_cash_icon.png');?>"></td><td align='left'><font size='1' face='Tahoma'><b><?php echo $Seeder_info['cash'];?></b></font></td>
<td align='left'><img border="0" width='18' src="<?php echo Seeder_ShowImage('/assets/equipment/equip_gas_can_icon.png');?>"></td><td align='left'><font size='1' face='Tahoma'><b><?php echo $Seeder_info['energy'];?></b></font></td>
<td align='left' width='18'><input type="image" src="<?php echo Seeder_imgPath.'arrow.png';?>" border="0" name="show_stats" value="+" onclick="ShowStats();return false;" title="Show Player Stats"></font></td>
</tr>
</table>
<!--------------------------------------------------------------------------------------------------------------------------------------------------------->
        </td>
        </tr>
        <tr>
                <td align="left" valign="top">
<?php
$stats = Seeder_Read("stats");
$userProfile = Seeder_userProfile();
?>
<!--------------------------------------------------------------------------------------------------------------------------------------------------------->
<!-- stats_div start -->
<div id="stats_div" style="display:none;top:20%;left:50%;width:30em;height:6em;margin-top:-3em;margin-left:-15em;" class="msgbox">
<div class="innerbox">
<table border="0" width="100%" cellspacing="0" cellpadding="4">
        <tr>
        <td align="left" valign="top">
<!-- stats -->
<table border="1" width="100%" cellspacing="0" cellpadding="2">
        <tr>
        <td align="center" valign="top" colspan="3" bgcolor="#0A246A">
        <b>
        <font size="1" face="Tahoma" color="#FFFFFF">Player Stats</font></td>
        </tr>
        <tr>
        <td align="left">
         <table border="0" width="100%" cellspacing="0" cellpadding="2">
			<tr>
				<td align="left" width="50" valign="top"><font size='1' face='Tahoma'>
				<img border='0' src='<?php echo $userProfile['pic_square'];?>'></font></td>
				<td align="left" valign="top"><font size="1" face="Tahoma"><b><?php echo $userProfile['name'];?></b> (801379837)</font></td>
			</tr>
		 </table>
        </td>
        </tr>
        <tr>
        <td align="left">
        <font size="1" face="Tahoma">Level: <b><?php echo $Seeder_info['level'];?></b> / Xp: <b><?php echo $Seeder_info['xp'];?></b></font></td>
        </tr>
        <tr>
        <td align="left">
        <font size="1" face="Tahoma">Gold: <b><?php echo $Seeder_info['gold'];?></b></font></td>
        </tr>
        <tr>
        <td align="left">
        <font size="1" face="Tahoma">FV Cash: <b><?php echo $Seeder_info['cash'];?></b></font></td>
        </tr>
        <tr>
        <td align="left">
        <font size="1" face="Tahoma">Fuel: <b><?php echo $Seeder_info['energy'];?></b></font></td>
        </tr>
        <tr>
        <td align="left">
        <font size="1" face="Tahoma">Locale: <b><?php echo $Seeder_info['locale'];?></b> / GeoIP: <b><?php echo $Seeder_info['geoip'];?></b></font></td>
        </tr>
        <tr>
        <td align="left">
        <font size="1" face="Tahoma">Time Zone: <b><?php echo date_default_timezone_get();?></b> (GMT <?php echo date('P');?>)</font></td>
        </tr>
        <tr>
        <td align="left">
        <font size="1" face="Tahoma">Local Time: <b><?php echo date($Seeder_settings['timeformat'], time() );?></b> (<?php echo time() ;?>)</font></td>
        </tr>
        <tr>
        <td align="left">
        <font size="1" face="Tahoma">Market Stall: <b><?php echo $Seeder_info['MarketStallCount'];?></b>  / Bushels: <b><?php echo $Seeder_info['bushels'];?></b></font></td>
        </tr>
        <tr>
        <td align="left">
        <font size="1" face="Tahoma">Airplane 1stFly: <b><?php echo (($Seeder_info['firstAirplaneFly'] == 1)? "Yes":"<font color='green'>NO!</font>");?></b></font></td>
        </tr>
        <tr>
        <td align="left">
        <font size="1" face="Tahoma">Wither: <b><?php if ($Seeder_info['friendUnwithered'] == 1) {echo "<font color='green'>Unwithered!</font>";} else {echo (($Seeder_info['witherOn'] == 1)? "<font color='red'>ON</font>":"<font color='green'>OFF!</font>");}?></b></font></td>
        </tr>
        <tr>
        <td align="left">
        <font size="1" face="Tahoma">Collections : <b><?php echo (($stats['collections'] > 0)?$stats['collections']:'0')?></font></td>
        </tr>
        <tr>
        <td align="left">
        <font size="1" face="Tahoma">Buildings : <b><?php echo (($stats['buildings'] > 0)?$stats['buildings']:'0')?></font></td>
        </tr>
</table>
        </td>
        </tr>
        <tr>
        <td align="left" valign="top">
<!-- mastery -->
<table border="1" width="100%" cellspacing="0" cellpadding="2">
        <tr>
        <td align="center" valign="top" colspan="3" bgcolor="#0A246A">
        <b>
        <font size="1" face="Tahoma" color="#FFFFFF">Mastery : <?php echo (($stats['mastery']['total'] > 0)?$stats['mastery']['total']:'0')?></font></td>
        </tr>
        <tr>
                <td align="left">
        <font size="1" face="Tahoma">level 1: <b><?php echo (($stats['mastery']['level1'] > 0)?$stats['mastery']['level1']:'0')?></font></td>
                <td align="left">
        <font size="1" face="Tahoma">level 2: <b><?php echo (($stats['mastery']['level2'] > 0)?$stats['mastery']['level2']:'0')?></font></td>
                <td align="left">
        <font size="1" face="Tahoma">level 3: <b><?php echo (($stats['mastery']['level3'] > 0)?$stats['mastery']['level3']:'0')?></font></td>
        </tr>
</table>

        </td>
        </tr>

        <tr>
        <td align="left" valign="top">
<!-- ribbons -->
<table border="1" width="100%" cellspacing="0" cellpadding="2">
        <tr>
        <td align="center" valign="top" colspan="4" bgcolor="#0A246A">
        <b>
        <font size="1" face="Tahoma" color="#FFFFFF">Ribbons : <?php echo (($stats['ribbons']['total'] > 0)?$stats['ribbons']['total']:'0')?></font></td>
        </tr>
        <tr>
                <td align="left">
        <font size="1" face="Tahoma">blue: <b><?php echo (($stats['ribbons']['blue'] > 0)?$stats['ribbons']['blue']:'0')?></font></td>
                <td align="left">
        <font size="1" face="Tahoma">yellow: <b><?php echo (($stats['ribbons']['yellow'] > 0)?$stats['ribbons']['yellow']:'0')?></font></td>
                <td align="left">
        <font size="1" face="Tahoma">white: <b><?php echo (($stats['ribbons']['white'] > 0)?$stats['ribbons']['white']:'0')?></font></td>
                <td align="left">
        <font size="1" face="Tahoma">red: <b><?php echo (($stats['ribbons']['red'] > 0)?$stats['ribbons']['red']:'0')?></font></td>
        </tr>
</table>

        </td>
        </tr>
        <tr>
        <td align="left" valign="top">
<!-- medals -->
<table border="1" width="100%" cellspacing="0" cellpadding="2">
        <tr>
        <td align="center" valign="top" colspan="3" bgcolor="#0A246A">
        <b>
        <font size="1" face="Tahoma" color="#FFFFFF">Medals : <?php echo (($stats['medals']['total'] > 0)?$stats['medals']['total']:'0')?></font></td>
        </tr>
        <tr>
                <td align="left">
        <font size="1" face="Tahoma">gold: <b><?php echo (($stats['medals']['gold'] > 0)?$stats['medals']['gold']:'0')?></font></td>
                <td align="left">
        <font size="1" face="Tahoma">silver: <b><?php echo (($stats['medals']['silver'] > 0)?$stats['medals']['silver']:'0')?></font></td>
                <td align="left">
        <font size="1" face="Tahoma">bronze: <b><?php echo (($stats['medals']['bronze'] > 0)?$stats['medals']['bronze']:'0')?></font></td>
        </tr>
</table>

        </td>
        </tr>
        <tr>
                <td align="center" valign="top">
        <input type="button" value="Close" onclick="HideStats()" style="width: 75px;">
        </td>
        </tr>
        </table>
</div>
</div>

<!--------------------------------------------------------------------------------------------------------------------------------------------------------->
<!-- stats_div end -->
<?php
unset($stats);unset($userProfile);
?>
                <table border="0" width="100%" cellspacing="0" cellpadding="2">
                        <tr>
                                <td align="left" valign="top">
<!--------------------------------------------------------------------------------------------------------------------------------------------------------->
<!-- plots fieldset start -->

<fieldset style="height:140px; overflow-y:auto;">
<legend align="left">Plots in Farm: <?php echo $Seeder_info['plots'];?></legend>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
        <tr>
                <td>
<?php
if ($Seeder_info['plots'] > 0 )
{
 $plots = Seeder_GetPlotsTime();
?>
<table border='1' width='100%' cellspacing='0' cellpadding='2' bordercolor='#D4D0C8' style='border-collapse: collapse'>

<?php
//======================================
//used plots
//======================================

 $plots = Seeder_GetPlotsTime();
 $iGrow_cost = 0;

 if (sizeof($plots) > 0 )
 {
  $seeds_all = Seeder_Read("seeds");
  foreach ($plots as $plot)
  {
  $name = $plot['itemName'];
  $growTime = $seeds_all[$name]['growTime']; if (!$growTime) {$growTime = 0;}
  $iconurl = $seeds_all[$name]['iconurl'];
  $plantTime = $plot['plantTime'];
  $booster = "<img border='0' src='".Seeder_imgPath."space.png' width='16' height='16'>";
  $spercent = "";
  $state = "";

  $timeround = round(($timenow - $plantTime) / 3600, 2);
  $percent = round((($timeround / $growTime) * 100), 0);
   if ($percent >= 100){$spercent = "<b>100%</b>";}
   else if ($percent <= 0) {$spercent = "0%";}
   else if ($percent < 100 && $percent >= 90) {$spercent = "<b>".$percent."%</b>";}
   else {$spercent = $percent."%";}

  $Grow_p = (100 - $percent)/100;
  $Grow_cost = ($plot['count'] * $growTime * $Grow_p);
  $iGrow_cost += $Grow_cost;

  $toharvesthour = $growTime - $timeround;
  $toharvestmin = $toharvesthour * 60;
  $toharvestsec = $toharvesthour * 3600;
  $d = floor($toharvestmin / 1440);
  $h = floor(($toharvestmin - $d * 1440) / 60);
  $m = floor($toharvestmin - ($d * 1440) - ($h * 60));
  $timeharvest = ($d > 0 ? $d."d " : "").($h > 0 ? $h."h " : '').$m."m";
  @$dateharvest = date($Seeder_settings['timeformat'], time() + $toharvestsec);

  $toharvestabs = abs($toharvesthour);
  $randwither = $growTime * $Seeder_info['witherRandomRange'];
  $fullwither = $growTime * $Seeder_info['witherMultiplier'];

  if ($toharvesthour > 0)
  {
  $state = "ripe in ".$timeharvest." (".$dateharvest.")";
  }
  else
  {
  if ($toharvestabs < $randwither) {$state = "<font color='green'><b>Ready!</b></font>";}
  else if (($toharvestabs >= $randwither) && ($toharvestabs < $fullwither)) {$state = "<font color='red'><b>Randomly Withering</b></font>";}
  else if ($toharvestabs >= $fullwithertimez) {$state = "<font color='red'><b>Withered</b></font>";}
  else {$state = $plot['state'];}
  }

 //Booster!
 $bushel_name = @$seeds_all[$name]['bushel_name'];
 $bushel_count = @$seeds_all[$name]['bushel_count'];
 $booster_time = @$seeds_all[$name]['booster_time'];
 $Btime_diff = ($booster_time - $timenow);

  if ($bushel_name != "NULL")
  {
   if ($Btime_diff > 0 ) {
   $booster = "<img border='0' title='Bushel Boosted!' src='".Seeder_imgPath."bushelbooster.png' width='16' height='16'>";
   }
   else
   {
    if ($toharvestmin <= 120 && $toharvestabs < $fullwither){
    $booster = "<input type='button' ".(($bushel_count == 0)?"disabled title='You dont have bushels to Booster'":"title='Bushel Booster!'")." onclick='Booster(this);return false;' name='".$bushel_name."' value='".$bushel_count."' style='width:16px;height:16px;font-size:7pt;'>";
    }else {
    $booster = "<input type='button' disabled title='Bushel Booster not recommended in this crop stage' name='".$bushel_name."' value='".$bushel_count."' style='width:16px;height:16px;font-size:7pt;'>";
    }
   }
  }

 echo "<tr>"."\n";
 echo "<td align='right' width='25'><font size='1' face='Tahoma'>".$plot['count']."</font></td>"."\n";
 echo "<td align='center' valign='center' width='16'><img border='0' src='".Seeder_ShowImage($iconurl)."' width='16' height='16'></td>"."\n";
 echo "<td align='left'><font size='1' face='Tahoma'><b>".$plot['realname']."</b></font></td>"."\n";
 echo "<td align='center' valign='center' width='16'>".$booster."</td>"."\n";
 echo "<td align='center' width='40'><font size='1' face='Tahoma'>".$spercent."</font></td>"."\n";
 echo "<td align='left'><font size='1' face='Tahoma'>".$state."</font></td>"."\n";
 echo "</tr>";

 }//foreach ($plots as $plot)
unset($seeds_all);
}//if (sizeof($plots) > 0 )

unset($plots);

//======================================
//free plots
//======================================

$plots = Seeder_GetPlotsFree();

if (sizeof($plots) > 0 )
{
 foreach ($plots as $plot)
 {
 echo "<tr>"."\n";
 echo "<td align='right' width='25'><font size='1' face='Tahoma'>".$plot['count']."</font></td>"."\n";
 echo "<td align='center' valign='center' width='16'><img border='0' src='".Seeder_imgPath."space.png' width='16' height='16'></td>"."\n";
 echo "<td align='left'><font size='1' face='Tahoma'>".$plot['state']."</font></td>"."\n";
 echo "<td align='center' valign='center' width='16'><img border='0' src='".Seeder_imgPath."space.png' width='16' height='16'></td>"."\n";
 echo "<td align='center' width='40'><font size='1' face='Tahoma'>&nbsp;</font></td>"."\n";
 echo "<td align='left'><font size='1' face='Tahoma'>&nbsp;</font></td>"."\n";
 echo "</tr>";
 }//foreach ($plots as $plot)
}//if (sizeof($plots) > 0 )
unset($plots);

//======================================
//Instant Grow
//======================================

$sGrow_title = "Aply Instant Grow in Farm";
if ($iGrow_cost > 0)
{
 $iGrow_cost = Ceil($iGrow_cost/1000);
 $iGrow_cost = $iGrow_cost * 3;//unknow multiplier
}

if ($Seeder_info['firstAirplaneFly'] == 1)
{
$sGrow_cost = "will cost ".$iGrow_cost. " FV Cash\nthis calculation MAY BE WRONG, use at your own risk";
}
else
{
$sGrow_cost = "1st Fly is Free!\nthis calculation MAY BE WRONG, use at your own risk";
}

if (str_replace(".","",$Seeder_info['cash']) < $iGrow_cost)
{
$iGrow_cost = 0;
$sGrow_title = "No FV Cash to apply Instant Grow in Farm";
}
//======================================
?>
</table>
<?php } else {echo "<b><font size='1' face='tahoma' color='red'>No plots in farm</font></b><br>";} ?>
        </font></td>
        </tr>
        <tr>
                <td align="right">
        <input class="off" type="button" <?php echo (($iGrow_cost <= 0)?'disabled':'')?> name="instantGrow_bt" iGrow_cost="<?php echo $sGrow_cost; ?>" value="Biplane Fly!" onclick="js_instantGrow(this);return false;" title="<?php echo $sGrow_title; ?>" style="width:75px;">&nbsp;&nbsp;<input type="button" name="update_bt" value="Update Farm" onclick="Update();return false;" title="Update Farm" style="width:75px;">
        </td>
        </tr>
</table>
</fieldset>
<!--------------------------------------------------------------------------------------------------------------------------------------------------------->
<!-- plots fieldset end -->
                </td>
                                <td align="left" valign="top">
<!--------------------------------------------------------------------------------------------------------------------------------------------------------->
<!-- Seed List fieldset start -->

<fieldset style="height: 140px;overflow-y:auto;">
<legend align="left">Seed Lists</legend>
<table border="0" width="100%" cellspacing="0" cellpadding="2">
	<tr>
		<td align="left" width="50%" valign="top">
<input type='checkbox' <?php echo (($Seeder_settings['keep_planted'] == 1)?'checked':'')?> name='keep_planted' value='1'>
<font size="1" face="Tahoma"><b>Keep planted:</b>
<?php
if ($Seeder_settings['keep_planted'] == 1)
{
echo "<input type='button' name='add_plant' value='+' onclick='ShowAddForm()' title='add plants' style='width:20px;'>";
echo "<input type='submit' name='del_plant' value='-' title='delete selected' style='width:20px;'>";
echo "<input type='submit' name='clear_planting' value='Clear' title='clear seed list'>";
}
?>
<br>
<select class="seed_list" name="seed_list" multiple style="width:180px; height:98px; float:left;<?php echo (($Seeder_settings['keep_planted'] == 1)?'':';background-color:#D4D0C8')?>">
<?php
//======================================
//seed_list
//======================================
$seedlist = Seeder_Read("seedlist");

  if(is_array($seedlist)) foreach ($seedlist as $key => $seed_value)
  {
   if ($key == "default")
   {
   $default = "<option value='default'>".Units_GetRealnameByName($seed_value[0])." (default)</option>"."\n";
   } else {
   echo "<option value='".$seed_value[0]."'>".Units_GetRealnameByName($seed_value[0])." (".$seed_value[1].")</option>"."\n";
   }
  }
  echo $default;

unset($seedlist);
?>
</select>
                </font>
        </td>
		<td align="left" width="50%" valign="top">
<font size="1" face="Tahoma"><b>Bot Seed List:</b>
<?php
echo "<input type='submit' name='clear_planting2' value='Clear' title='clear boot seed list'>";
if (($Seeder_settings['auto_coop'] == 0) && ($Seeder_settings['auto_mastery'] == 0) && ($Seeder_settings['keep_planted'] == 0))
{
$show_seed_list = 1;
echo "<input type='button' name='add_plant2' value='+' onclick='ShowAddForm2()' title='add plants' style='width:20px;'>";
echo "<input type='submit' name='del_plant2' value='-' title='delete selected' style='width:20px;'>";
echo "<input type='submit' name='clear_planting2' value='Clear' title='clear seed list'><br>";
}
?>

<select class="seed_list" name="seed_list2" multiple style="width:180px; height:98px; float:left;<?php echo (($show_seed_list == 1)?'':';background-color:#D4D0C8')?>">
<?php
//======================================
//bot seed_list
//======================================
$seed_list = @explode(';', trim(file_get_contents(F('seed.txt'))));
$i = 0;
foreach ($seed_list as $seed_item)
{
$seeds_list = @explode(':', $seed_item);
if ($seeds_list[0])
echo '<option value="'.$i.'">'.Units_GetRealnameByName($seeds_list[0]).' ('.$seeds_list[1].')</option>'."\n";
$i++;
}
//======================================
?>
</select>
                </font>
        </td>
	</tr>
</table>
</fieldset>
                </td>
                        </tr>
                </table>
<!--------------------------------------------------------------------------------------------------------------------------------------------------------->
<!-- keep add_plant_div start -->
<div id="add_plant_div" style="display:none;top:20%;left:50%;width:260px;height:200px;margin-top:-3em;margin-left:-15em;" class="msgbox">
<div class="innerbox">
<table border="0" width="100%" cellspacing="0" cellpadding="4">
        <tr>
                <td bgcolor="#0A246A"><b><font face="Tahoma" size="2" color="#FFFFFF">Edit Keep planted List</font></b>
                </td>
        </tr>
        <tr>
        <td align="left" valign="top">
        <font size="1" face="Tahoma">Seed :
<select name="plant_list" style="width:240px" onchange="ChangeMasteryInfo()">
<?php
//======================================
$seeds_available = Seeder_available();
$seeds_available = Seeder_ArrayOrder($seeds_available, "realname", "ASC");

foreach ($seeds_available as $seed)
{

if ($seed['plots_planted'] > 0) {$plots_planted = "Planted: <b><font color=red>".$seed['plots_planted']."</font></b>";} else {$plots_planted = "Planted: 0";}
if ($seed['seedpackage_name'] != "NULL")
 {
 if ($seed['seedpackage_count'] > 0)
 {$seedpackage = "<br>Seed Packages: <b><font color=blue>Plant only ".$seed['seedpackage_count']."</font></b>";} else {$seedpackage = "<br>Seed Packages: <b><font color=red>No seed Packages</font></b>";}
 } else {$seedpackage = "<br>&nbsp;";}

 if ($seed['masterymax'] > 0)
 {
  if ($seed['tomastery'] <= 0)
  {
  echo "<option value='".$seed['name']."' mastery='".$plots_planted."<br> Mastery: <b><font color=red>Mastered!</font></b>".$seedpackage."'>".$seed['realname']."</option>"."\n";
  }
  else
  {
  echo "<option value='".$seed['name']."' mastery='".$plots_planted."<br> Mastery: ".$seed['mastery_count']."/".$seed['masterymax']." Plant: <b>".$seed['to_mastery']."</b> more!".$seedpackage."'>".$seed['realname']."</option>"."\n";
  }
 }
 else
 {
 echo "<option value='".$seed['name']."' mastery='".$plots_planted."<br> Mastery: No Mastery".$seedpackage."'>".$seed['realname']."</option>"."\n";
 }
}//foreach ($seeds_available as $seed)
//======================================
?>
</select>
        </font>
        </td>
        </tr>
        <tr>
                <td align="left" valign="top">
        <font size="1" face="Tahoma">
        <div id="mastery_info"></div>
        </font>
        </td>
        </tr>
        <tr>
                <td align="left" valign="top">
        <font size="1" face="Tahoma">
        Plant: <input type="text" name="plant_count" size="3" value="<?php echo $Seeder_info['plots'];?>">&nbsp;
        <input type='checkbox' name='seed_default' value='1'> Default Seed
		</font></td>
        </tr>
        <tr>
                <td align="left">
                <font size="1" face="Tahoma">
        <b>Seeder Seed List Help:</b><br><br>
        <b>1o</b> Seeder Plant <b>Co-Op</b> seeds (if enabled and without 100% planted).<br>
        <b>2o</b> Seeder Plant <b>Auto Mastery</b> seeds (if enabled and have seeds available to mastery).<br>
        <b>3o</b> Seeder Plant amount seeds from <b>Keep planted list.</b><br>
        <b>Keep planted:</b> includes count of Seeds Planted.<br>
        enable or disable the keep list and <b>click the save button.</b><br>
        </font>
        </td>
        </tr>
        <tr>
                <td align="center">
                <font size="1" face="Tahoma">
        <input type="submit" name="add_plant_s" value="Add" style="width: 75px;">&nbsp;<input type="button" value="Cancel" onclick="HideAddForm()" style="width: 75px;">
        </font>
        </td>
        </tr>
</table>
</div>
</div>

<!--------------------------------------------------------------------------------------------------------------------------------------------------------->
<!-- keep add_plant_div end -->
<!--------------------------------------start add_plant_div-->
<div id="add_plant_div2" style="display:none;top:20%;left:50%;width:240px;height:180px;margin-top:-3em;margin-left:-15em;" class="msgbox">
<div class="innerbox">
<table border="0" width="100%" cellspacing="0" cellpadding="4">
        <tr>
                <td bgcolor="#0A246A"><b><font face="Tahoma" size="2" color="#FFFFFF">Manual Add Seed</font></b>
                </td>
        </tr>
        <tr>
        <td align="left" valign="top">
        <font size="2" face="Tahoma">Seed :
<select name="plant_list2" style="width:220px" onchange="ChangeMasteryInfo2()">
<?php
foreach ($seeds_available as $seed)
{

if ($seed['plots_planted'] > 0) {$plots_planted = "Planted: <b><font color=red>".$seed['plots_planted']."</font></b>";} else {$plots_planted = "Planted: 0";}
if ($seed['seedpackage_name'] != "NULL")
 {
 if ($seed['seedpackage_count'] > 0)
 {$seedpackage = "<br>Seed Packages: <b><font color=blue>Plant only ".$seed['seedpackage_count']."</font></b>";} else {$seedpackage = "<br>Seed Packages: <b><font color=red>No seed Packages</font></b>";}
 } else {$seedpackage = "<br>&nbsp;";}

 if ($seed['masterymax'] > 0)
 {
  if ($seed['tomastery'] <= 0)
  {
  echo "<option value='".$seed['name']."' mastery2='".$plots_planted."<br> Mastery: <b><font color=red>Mastered!</font></b>".$seedpackage."'>".$seed['realname']."</option>"."\n";
  }
  else
  {
  echo "<option value='".$seed['name']."' mastery2='".$plots_planted."<br> Mastery: ".$seed['mastery_count']."/".$seed['masterymax']." Plant: <b>".$seed['to_mastery']."</b> more!".$seedpackage."'>".$seed['realname']."</option>"."\n";
  }
 }
 else
 {
 echo "<option value='".$seed['name']."' mastery2='".$plots_planted."<br> Mastery: No Mastery".$seedpackage."'>".$seed['realname']."</option>"."\n";
 }
}//foreach ($seeds_available as $seed)
unset($seeds_available);
?>
</select>
        </font>
        </td>
        </tr>
        <tr>
                <td align="left" valign="top">
        <font size="2" face="Tahoma">
        <div id="mastery_info2"></div>
        </font>
        </td>
        </tr>
        <tr>
                <td align="left" valign="top">
        <font size="2" face="Tahoma">
        Plant: <input type="text" name="plant_count2" size="3" value="<?php echo $Seeder_info['plots'];?>">&nbsp;<input type="checkbox" name="seed_default2" value="1" title="Plant this seed when no other seed is listed"> Default Seed
        </font>
        </td>
        </tr>
        <tr>
                <td align="center">
                <font size="2" face="Tahoma">
        <input type="submit" name="add_plant_s2" value="Add" style="width: 75px;">&nbsp;<input type="button" value="Cancel" onclick="HideAddForm2()" style="width: 75px;">
        </font>
        </td>
        </tr>
</table>
</div>
</div>
<!--------------------------------------end add_plant_div-->
                </td>
        </tr>
<!--------------------------------------------------------------------------------------------------------------------------------------------------------->
<!-- co-op start -->
        <tr>
                <td align="left">
<fieldset>
<legend align="left">Active Co-Op Job</legend>
<?php
//======================================
//co-op
//======================================

$quests_available = Seeder_quests_available();
$quest_active = Seeder_Read("activeMission");
#print_r($quest_active);
$coop_id = $quest_active['id'];

if ((is_array($quest_active)) && ($coop_id != "active_mission_id_none"))
{
$quests_all = Seeder_ReadDefault("quests");
$quest_active_data = $quests_all[$coop_id];
#print_r($quest_active_data);
$completeType = $quest_active['completeType'];
 if ($completeType == 'in_progress') {$completeType = "In Progress";}
 elseif ($completeType == 'gold') {$completeType = "<font color='blue'>Gold!</font>";}
 elseif ($completeType == 'silver') {$completeType = "<font color='blue'>Silver!</font>";}
 elseif ($completeType == 'gold') {$completeType = "Bronze!";}
 else {$completeType = "<font size='1' face='Tahoma'> - ".$completeType."</font>";}

//gold
$gold_timeLimit = ($quest_active_data['completionRequirements'][2]['timeLimit']) * (23 * 60 * 60);
$gold_end = ($quest_active['startTime'] + $gold_timeLimit);
if ($gold_end < time())
{
$gold_state = "<font color='red'><b>Expired!</b></font>";
} else {
$gold_state = "end in ".Seeder_TimeLeft(time(),$gold_end) ." (".date($Seeder_settings['timeformat'],Seeder_TimeZone($gold_end)).")";
}
$gold_timeLimit = Seeder_TimeLeft(time(),(time() + $gold_timeLimit));

//silver
$silver_timeLimit = ($quest_active_data['completionRequirements'][1]['timeLimit']) * (23 * 60 * 60);
$silver_end = ($quest_active['startTime'] + $silver_timeLimit);
if ($silver_end < time())
{
$silver_state = "<font color='red'><b>Expired!</b></font>";
} else {
$silver_state = "end in ".Seeder_TimeLeft(time(),$silver_end) ." (".date($Seeder_settings['timeformat'],Seeder_TimeZone($silver_end)).")";
}
$silver_timeLimit = Seeder_TimeLeft(time(),(time() + $silver_timeLimit));

//bronze
$bronze_timeLimit = ($quest_active_data['completionRequirements'][0]['timeLimit']) * (23 * 60 * 60);
$bronze_end = ($quest_active['startTime'] + $bronze_timeLimit);
if ($bronze_end < time())
{
$bronze_state = "<font color='red'><b>Job Failed!</b></font>";
} else {
$bronze_state = "end in ".Seeder_TimeLeft(time(),$bronze_end) ." (".date($Seeder_settings['timeformat'],Seeder_TimeZone($bronze_end)).")";
}
$bronze_timeLimit = Seeder_TimeLeft(time(),(time() + $bronze_timeLimit));
?>

<table border="0" width="100%" cellspacing="0" cellpadding="2">

<tr>
<td align="center" valign="top" width="48"><img border="0" src="<?php echo Seeder_ShowImage($quest_active_data['icon']['url']);?>" width="48">
</td>
<td align="left" valign="top"><font size="2" face="Tahoma"><b><?php echo $quest_active_data['realname'];?></b></font><br>
<font size='1' face='Tahoma'>
Status : <b><?php echo $completeType;?></b><br>
Start : <b><?php echo date($Seeder_settings['timeformat'],$quest_active['startTime']);?></b><br>
</font>
<input class="<?php echo (($quest_active['isComplete'] <= 0)?'off':'on')?>" type="button" name="end_quest_bt" value="<?php echo (($quest_active['isComplete'] == 1)?'Finish Co-op':(($quest_active['isOwner'] == 1)?'Interrupt Co-Op':'Abandon Co-Op'))?>" onclick="End_Job(this);return false;" style="width:100px;"> <input type="button" name="update_job_bt" value="Update Job" onclick="Update_Job();return false;" title="Update Job" style="width:100px;">
</td>
<?php
if ($completeType == 'In Progress')
{
?>
<td align="left" valign="top">
 <table border="1" width="100%" cellspacing="0" cellpadding="2" bordercolor='#D4D0C8' style='border-collapse: collapse'>
 <tr>
 <td align="left" valign="top"><font size="1" face="Tahoma"><b>Gold</b></font><br>
 </td>
 <td align="left" valign="top"><font size="1" face="Tahoma"><b><?php echo $gold_timeLimit;?></b></font><br>
 </td>
 <td align="left" valign="top"><font size="1" face="Tahoma"><?php echo $gold_state;?></font><br>
 </td>
 </tr>
 <tr>
 <td align="left" valign="top"><font size="1" face="Tahoma"><b>Silver</b></font><br>
 </td>
 <td align="left" valign="top"><font size="1" face="Tahoma"><b><?php echo $silver_timeLimit;?></b></font><br>
 </td>
 <td align="left" valign="top"><font size="1" face="Tahoma"><?php echo $silver_state;?></font><br>
 </td>
 </tr>
 <tr>
 <td align="left" valign="top"><font size="1" face="Tahoma"><b>Bronze</b></font><br>
 </td>
 <td align="left" valign="top"><font size="1" face="Tahoma"><b><?php echo $bronze_timeLimit;?></b></font><br>
 </td>
 <td align="left" valign="top"><font size="1" face="Tahoma"><?php echo $bronze_state;?></font><br>
 </td>
 </tr>
 </table>

</td>
<?php
}
?>
<td align="left" valign="top">
 <table border="1" width="100%" cellspacing="0" cellpadding="2" bordercolor='#D4D0C8' style='border-collapse: collapse'>
<?php
//currentProgress
$requirement = $quest_active_data['completionRequirements'][2]['requirement'];//gold only
$req_count = count($requirement);

for ($i = 0; $i < $req_count; $i++)
{
$name = $requirement[$i]['type'];
$action = $requirement[$i]['action'];
$many = $requirement[$i]['many'];
$req_progress = $quest_active['currentProgress'][$i]['progress'];//#ERROR???

#$req_percent = round((($req_progress / $many) * 100), 0);
$req_percent = floor(($req_progress / $many) * 100);
if ($req_percent >= 100){$s_req_percent = "<font color='blue'>100%</font>";}
else if ($req_percent <= 0) {$s_req_percent = "<font color='red'>0%</font>";}
else if ($req_percent < 100 && $req_percent >= 90) {$s_req_percent = "<font color='orange'>".$req_percent."%";}
else {$s_req_percent = $req_percent."%";}

echo "<tr>"."\n";
echo "<td align='center' width='16'><img border='0' src='".Seeder_ShowImagebyName($name)."' width='16'></td>"."\n";
echo "<td><font size='1' face='Tahoma'><b>".Units_GetRealnameByName($name)."</b></font></td>"."\n";
echo "<td><font size='1' face='Tahoma'><b>".$action."</b></font></td>"."\n";
echo "<td><font size='1' face='Tahoma'>".$req_progress."/".$many." (".($many - $req_progress).")</font></td>"."\n";
echo "<td align='center' width='40'><font size='1' face='Tahoma'><b>".$s_req_percent."</b></font></td>"."\n";
echo "</tr>"."\n";
}

?>
 </table>

</td>
<td align="left" valign="top">
 <table border="1" width="100%" cellspacing="0" cellpadding="2" bordercolor='#D4D0C8' style='border-collapse: collapse'>
<?php
//friends
foreach($quest_active['friends'] as $friend)
{
 if ($friend['isHost'] == 1) {$name = "<b>".$friend['name']."</b>";} else {$name = $friend['name'];}
echo "<tr>"."\n";
echo "<td><font size='1' face='Tahoma'>".$name." (".$friend['uid'].")</font></td>"."\n";
echo "<td align='center' width='40'><font size='1' face='Tahoma'>".round($friend['percentHelp'], 0)."%</font></td>"."\n";
echo "</tr>"."\n";
}
?>
 </table>

</td>
</tr>
</table>
<?php
unset($quest_active_data);unset($quests_all);

} else { //if ($quest_active['id'] == "active_mission_id_none")
echo "<b><font size='1' color='red'>No Co-op Job active</font></b><br>";
echo "<input type='button' name='update_job_bt' value='Update Jobs' onclick='Update_Job();return false;' title='Update Co-Op Jobs' style='width:100px;'>";
}
unset($quest_active);
?>
</font>
</fieldset>
        </td>
        </tr>
<!--------------------------------------------------------------------------------------------------------------------------------------------------------->
<!-- Seeder Settings start -->
        <tr>
                <td align="left">
<fieldset>
<legend align="left">Seeder Settings</legend>
<table border="0" width="100%" cellspacing="0" cellpadding="0">
<tr>
<td>
 <table border="0" width="100%" cellspacing="0" cellpadding="2">
 <tr>
 <td align="left" valign="top"><font size="1" face="Tahoma">
 Bushel Booster:
<input type='checkbox' name='bushel_booster' <?php echo (($Seeder_settings['bushel_booster'] == 1)?'checked':'')?> value='1' title='Seeder use Bushels to Booster and Harvest your crops!'></font>
 </td>
 <td align="left" valign="top"><font size="1" face="Tahoma">
 Fertilize All: </font>
 <input type='checkbox' name="fertilize" <?php echo (($Seeder_settings['fertilize'] == 1)?'checked':'')?> value="1" title="Fertilize crops before harvest" style="width: 20px">
 </font>
 </td>
 <td align="left" valign="top"><font size="1" face="Tahoma">
 Claim Reward:
 <input type="checkbox" name="claim_reward" <?php echo (($Seeder_settings['claim_reward'] == 1)?'checked':'')?> value="1" title="Seeder will claim your Reward from Market">
 <select size="1" name="reward_type" title="Reward Type">
 <option <?php echo (($Seeder_settings['reward_type'] == 1)?'selected':'')?> value="1">Coins</option>
 <option <?php echo (($Seeder_settings['reward_type'] == 2)?'selected':'')?> value="2">Fuel</option>
 <option <?php echo (($Seeder_settings['reward_type'] == 3)?'selected':'')?> value="3">XP</option>
 </select> (<?php echo $Seeder_info['pendingRewards'] ?> Rewards)</font>
 </td>
 <td align="left" valign="top"><font size="1" face="Tahoma">
 Date format: </font>
 <select size="1" name="timeformat" title="your time format">
 <option <?php echo (($Seeder_settings['timeformat'] == "d/m/y H:i")?'selected':'')?> value="d/m/y H:i">d/m/y 24-hour</option>
 <option <?php echo (($Seeder_settings['timeformat'] == "m/d/y h:i a")?'selected':'')?> value="m/d/y h:i a">m/d/y 12-hour</option>
 </select>
 </td>
 </tr>
 </table>
</td>
</tr>
<tr>
<td>
 <table border="0" width="100%" cellspacing="0" cellpadding="2">
 <tr>
 <td><font size="1" face="Tahoma">
 Auto mastery: </font>
 <input type="checkbox" name="auto_mastery" <?php echo (($Seeder_settings['auto_mastery'] == 1)?'checked':'')?> value="1"></td>
 <td><font size="1" face="Tahoma">
 Mastery Order: </font>
 <select size="1" name="seeds_order" title="Order to choose seeds Mastery Time ASC is recomended">
 <option <?php echo (($Seeder_settings['seeds_order'] == "profit_time")?'selected':'')?> value="profit_time">Profit Hour</option>
 <option <?php echo (($Seeder_settings['seeds_order'] == "xp_time")?'selected':'')?> value="xp_time">Xp Hour</option>
 <option <?php echo (($Seeder_settings['seeds_order'] == "growTime")?'selected':'')?> value="growTime">Grow Time</option>
 <option <?php echo (($Seeder_settings['seeds_order'] == "mastery_time")?'selected':'')?> value="mastery_time">Mastery Time</option>
 <option <?php echo (($Seeder_settings['seeds_order'] == "requiredLevel")?'selected':'')?> value="requiredLevel">Required Level</option>
 <option <?php echo (($Seeder_settings['seeds_order'] == "limitedEnd")?'selected':'')?> value="limitedEnd">Limited</option>
 </select>

 <select size="1" name="seeds_sort">
 <option <?php echo (($Seeder_settings['seeds_sort'] == "ASC")?'selected':'')?> value="ASC">ASC</option>
 <option <?php echo (($Seeder_settings['seeds_sort'] == "DESC")?'selected':'')?> value="DESC">DESC</option>
 </select>
 </td>
 <td><font size="1" face="Tahoma">
 Mastery Adjustment: </font>
 <input type="checkbox" name="mastery_adjustment" <?php echo (($Seeder_settings['mastery_adjustment'] == 1)?'checked':'')?> value="1" title="Ignore full mastery earned before the new Mastery Adjustment by Z*">
 </td>
 <td><font size="1" face="Tahoma">
 Force Planting:
 <input type="checkbox" name="force_planting" <?php echo (($Seeder_settings['force_planting'] == 1)?'checked':'')?> value="1" title="Ignore Seeds Filters. Errors can happen."></font>
 </td>
 </tr>
 </table>
</td>
</tr>
<tr>
<td>

<font size='1' face='Tahoma'>
 <table border="0" width="100%" cellspacing="0" cellpadding="2">
 <tr>
 <td align="left" valign="top"><font size="1">Auto Co-Op</font><font size='1' face='Tahoma'>:
 <input type="checkbox" name="auto_coop" <?php echo (($Seeder_settings['auto_coop'] == 1)?'checked':'')?> value="1">
            </font>
 </td>
 <td align="left" valign="top">
<font size='1' face='Tahoma'>
 Co-Op Mode:
 <select size="1" name="coop_mode">
	<option <?php echo (($Seeder_settings['coop_mode'] == "host")?'selected':'')?> value="host">host</option>
	<option <?php echo (($Seeder_settings['coop_mode'] == "guest")?'selected':'')?> value="guest">guest</option>
</select>
 </td>
                                                <td align="left" valign="top">
<font size='1' face='Tahoma'>
 Host Job

<?php
echo "<select size='1' name='coop_host'>"."\n";

$check = @$quests_available[$Seeder_settings['coop_host']];
if ($check)
{
echo "<option value='0'>_____ selected _____</option>"."\n";
echo "<option selected value='".$Seeder_settings['coop_host']."'>".Seeder_GetQuestRealname($Seeder_settings['coop_host'])."</option>"."\n";
}

$quests_available = Seeder_ArrayOrder($quests_available, 'realname', 'ASC');

$coop_hosts = Seeder_ArrayFilter($quests_available, 'type', '!=', 'crafting');
echo "<option value='0'></option>"."\n";
echo "<option value='0'>______ basic _______</option>"."\n";
foreach($coop_hosts as $coop_host)
{
echo "<option value='".$coop_host['id']."'>".Seeder_replace($coop_host['realname'])."</option>"."\n";
}
unset($coop_hosts);

$coop_hosts = Seeder_ArrayFilter($quests_available, 'type', '==', 'crafting');
echo "<option value='0'></option>"."\n";
echo "<option value='0'>_____ crafting _____</option>"."\n";
foreach($coop_hosts as $coop_host)
{
echo "<option value='".$coop_host['id']."'>".Seeder_replace($coop_host['realname'])."</option>"."\n";
}
unset($coop_hosts);

echo "</select>"."\n";
?>


	</td>
                                                </tr>
                </table>
                                                </td>
                                        </tr>
<tr>
<td>

<font size='1' face='Tahoma'>
 <table border="0" width="100%" cellspacing="0" cellpadding="2">
 <tr>
                                                <td align="left" valign="top">
<font size="1" face="Tahoma">Follow
  <select size="1" name="coop_follow">
<?php
if ($Seeder_settings['coop_follow'])
{
echo "<option selected value='".$Seeder_settings['coop_follow']."'>".$Seeder_settings['coop_follow']."</option>"."\n";
}
$neighbors = unserialize(file_get_contents(F('neighbors.txt')));
foreach ($neighbors as $uid )
{
 echo "<option value='".$uid."'>".$uid."</option>"."\n";
}
?>
</select>
</font></td>
                                                <td align="left" valign="top">
<font size='1' face='Tahoma'>
Close Complete:
 <input type="checkbox" name="end_job" <?php echo (($Seeder_settings['end_job'] == 1)?'checked':'')?> value="1">
            </font></td>
                                                <td align="left" valign="top">
<font size='1' face='Tahoma'>
Plant:
 <input type="checkbox" name="coop_plant" <?php echo (($Seeder_settings['coop_plant'] == 1)?'checked':'')?> value="1">
            </font></td>
                                                <td align="left" valign="top">
<font size='1' face='Tahoma'>
 Grow Time Order
  <select size="1" name="coop_growTime" style="font-family: Tahoma,Arial,Helvetica,sans-serif; font-size: 8pt; color: Black">
<option <?php echo (($Seeder_settings['coop_growTime'] == "DESC")?'selected':'')?> value="DESC">DESC</option>
<option <?php echo (($Seeder_settings['coop_growTime'] == "ASC")?'selected':'')?> value="ASC">ASC</option>
</select>

												</td>
                                                <td align="right" valign="top">
&nbsp;</td>
                </table>

</td>
</tr>


<tr>
<td>

<font size='1' face='Tahoma'>
 <table border="0" width="100%" cellspacing="0" cellpadding="2">
 <tr>
                                                <td align="left" valign="top">
<font size="1">Auto Mastery Greenhouse:
 <input type="checkbox" name="mastery_greenhouse" <?php echo (($Seeder_settings['mastery_greenhouse'] == 1)?'checked':'')?> value="1"></font></td>
                                                <td align="left" valign="top">
<font size="1">Harvest Greenhouse:
 <input type="checkbox" name="harvest_greenhouse" <?php echo (($Seeder_settings['harvest_greenhouse'] == 1)?'checked':'')?> value="1"></font></td>
                                                <td align="left" valign="top">
<font size="1" face="Tahoma">Default:
  <select size="1" name="default_greenhouse">
<?php
if ($Seeder_settings['default_greenhouse'])
{
echo "<option selected value='".$Seeder_settings['default_greenhouse']."'>".Units_GetRealnameByName($Seeder_settings['default_greenhouse'])."</option>"."\n";
echo "<option value='NULL'>Empty</option>"."\n";
} else {
echo "<option selected value='NULL'>Empty</option>"."\n";
}

$greenhouse_seeds = Seeder_Read("seeds");
$greenhouse_seeds = Seeder_ArrayFilter($greenhouse_seeds, 'isHybrid', '==', '1');
$greenhouse_seeds = Seeder_ArrayFilter($greenhouse_seeds, 'seedpackage_UnlockState', '<=', $Seeder_info['greenhouse_level'] );
foreach ($greenhouse_seeds as $greenhouse_seed)
{
 echo "<option value='".$greenhouse_seed['name']."'>".$greenhouse_seed['realname']."</option>"."\n";
}
?>
</select>
</font></td>
                                                <td align="left" valign="top">

 <font size="1">&nbsp;</font></td>
                                                <td align="right" valign="top">
<font size="1" face="Tahoma">
<input style="width:75px; font-family:Tahoma,Arial,Helvetica,sans-serif; font-size:8pt; color:Black" type="submit" value="Save" name="save_settings" title="Save Seeder Settings"></font></td>
                </table>

</td>
</tr>


</table>
</font>
</fieldset>
        </td>
        </tr>
<!--------------------------------------------------------------------------------------------------------------------------------------------------------->
<!-- Seeder Settings end -->

        <tr>
                <td align="left" valign="top">
<font size="1" face="Tahoma">

<?php
//======================================
//show_tab
//======================================
if (!isset($Seeder_settings['show_tab'])) {$Seeder_settings['show_tab'] = "seeds";}
if (!isset($Seeder_settings['show_subtab'])) {$Seeder_settings['show_subtab'] = "available";}

if (isset($_GET['show_tab']))
{
$Seeder_settings['show_tab'] = @$_GET['show_tab'];
$Seeder_settings['show_subtab'] = @$_GET['show_subtab'];
Seeder_Write($Seeder_settings,"settings");
}

echo Seeder_tabs($Seeder_settings['show_tab'],$Seeder_settings['show_subtab']);
//save for post
echo "<input type='hidden' name='show_tab' value='".$Seeder_settings['show_tab']."'>";
echo "<input type='hidden' name='show_subtab' value='".$Seeder_settings['show_subtab']."'>";
?>
</td></tr></table>
</font>
</div>
<?php
############Debug Area############





############Debug Area############

$T2 = time();
$T2 -= $timenow;
$msg = $T2." Secs.";
echo "<table border='0' width='100%' cellspacing='0' cellpadding='2'><tr><td align='right' height='10' bgcolor='#D4D0C8'><font size='1' face='Tahoma'>";
echo "Seeder Plugin - time to load ".$msg."</font></td></tr></table>";
AddLog2("Seeder_form> end ".$msg);
?>
</form>
</body></html>
<?php
  }//if ((!PX_VER_PARSER) || (PX_VER_PARSER < Seeder_parser))
 }//if (!file_exists(Seeder_dbPath.PluginF('info.txt')))
}//function Seeder_form()
//========================================================================================================================
//Seeder_box
//========================================================================================================================
function Seeder_box($title, $msg, $icon)
{
?>
<div id="msgbox" class="msgbox" style="top:30%;left:50%;width:30em;height:6em;margin-top:-3em;margin-left:-15em;">
<div class="innerbox">
<table border="0" width="100%" cellspacing="0" cellpadding="4">
        <tr>
                <td bgcolor="#0A246A"><b><font face="Tahoma" size="2" color="#FFFFFF"><?php echo $title;?></font></b>
                </td>
        </tr>
        <tr>
                <td align="center">
                <table border="0" width="100%" cellspacing="0" cellpadding="2">
                        <tr>
                                <td align="left" width="32" valign="top"><img border="0" src="<?php echo Seeder_imgPath.'msgbox_'.$icon.'.png';?>" width="32" height="32"></td>
                                <td align="left" valign="top"><font face="Tahoma" size="2"><?php echo $msg;?></font></td>
                        </tr>
                </table>
                </td>
        </tr>
        <tr>
                <td align="center"><input type="button" name="close_bt" value="Close" onclick="document.getElementById('msgbox').style.display = 'none';" title="Close Box" style="width:75px;"></td>
        </tr>
</table>
</div>
</div>
<?php
}//function Seeder_box
//========================================================================================================================
?>
