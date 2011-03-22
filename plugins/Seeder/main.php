<?php
//========================================================================================================================
//Seeder main.php
//by N1n9u3m
//========================================================================================================================
error_reporting (E_ALL ^ E_NOTICE);
include 'functions/Seeder_loadWorld.php';
include 'functions/Seeder_functions.php';
include 'functions/Seeder_form.php';
include 'functions/Seeder_image.php';
include 'functions/Seeder_quest.php';
include 'functions/Seeder_trees.php';//added 1.1.6
include 'functions/Seeder_tabs.php';
define('Seeder_version','1.1.7b');//revised v1.1.7b
define('Seeder_parser','22120');
define('Bot_path',str_replace("\\", "/", getcwd()).'/');
define('Seeder_Path',Bot_path.'plugins/Seeder/');
define('Section_Path',Bot_path.'plugins/Sections/');//added v1.1.6
define('GiftBox_Path',Bot_path.'plugins/GiftBox/');//added v1.1.6
define('Seeder_dbPath',Seeder_Path.'database/');
define('Seeder_imgPath','/plugins/Seeder/images/');
define('Seeder_URL','/plugins/Seeder/main.php');
define('Seeder_imgURL','/plugins/Seeder/images/');
define('Seeder_date',date("Ymd"));

//========================================================================================================================
// Seeder_init
//========================================================================================================================
function Seeder_init()//revised v1.1.2
{

echo "Loaded Seeder v".Seeder_version." by n1n9u3m\r\n";
global $hooks;
global $this_plugin;

 if ((!PX_VER_PARSER) || (PX_VER_PARSER < Seeder_parser))
 {
 Seeder_error("Incorrect Parser Version"."\n"."Seeder needs parser v".Seeder_parser."\n"."Bot running parser v".PX_VER_PARSER."\n"."Seeder disabled.");
 } else {
 $hooks['after_load_settings'] = 'Seeder_after_load_settings';
 $hooks['before_harvest'] = 'Seeder_before_harvest';
 $hooks['before_planting'] = 'Seeder_before_planting';
 $hooks['after_missions'] = 'Seeder_after_missions';
 }

}
//========================================================================================================================
//Seeder_load_settings
//========================================================================================================================
function Seeder_after_load_settings()//revised v1.1.4
{

 AddLog2("Seeder_after_load_settings> start");
 global $Seeder_settings;
 $Seeder_settings = array();

 if (!file_exists(Seeder_dbPath."default_settings.txt")) {Seeder_MakeUserDefault();}

 $Seeder_default = Seeder_ReadDefault("default_settings");

 if (!file_exists(Seeder_dbPath.PluginF('settings.txt')))
 {
 $Seeder_settings = $Seeder_default;
 Seeder_Write($Seeder_settings,"settings");
 } else {
 $Seeder_settings = Seeder_Read("settings");
 }

list($userId, $flashRevision, $token, $sequence, $flashSessionKey, $xp, $energy) = explode(';', file_get_contents(F('params.txt')));
if ($Seeder_default['flashRevision'] <> $flashRevision)
{
 AddLog2("Seeder_load_settings> New game released!");
 Seeder_MakeQuests();//added 1.1.4
# Seeder_MakeSeeds();
 $Seeder_default['flashRevision'] = $flashRevision;
 Seeder_WriteDefault($Seeder_default,"default_settings");
}

 if (!file_exists(Seeder_dbPath.'quests.txt')) {Seeder_MakeQuests();}//fix 1.1.6
 else{
 $quests = Seeder_ReadDefault("quests");
 if ((!is_array($quests)) || (count($quests) == 0)) {Seeder_MakeQuests();}
 unset($quests);
 }

 AddLog2("Seeder_after_load_settings> end");
}
//========================================================================================================================
//Seeder_MakeUserDefault
//========================================================================================================================
function Seeder_MakeUserDefault()//revised v1.1.7a
{

 $Seeder_default = array();
 $Seeder_default['version'] = Seeder_version;
 $Seeder_default['flashRevision'] = 0;
 $Seeder_default['auto_mastery'] = 0;
 $Seeder_default['bushel_booster'] = 1;
 $Seeder_default['fertilize'] = 1;
 $Seeder_default['claim_reward'] = 1;
 $Seeder_default['reward_type'] = 3;//3 = xp
 $Seeder_default['seeds_order'] = "mastery_time";
 $Seeder_default['seeds_sort'] = "ASC";
 $Seeder_default['mastery_adjustment'] = 1;
 $Seeder_default['force_planting'] = 0;
 $Seeder_default['auto_coop'] = 0;
 $Seeder_default['coop_mode'] = "host";
 $Seeder_default['coop_plant'] = 1;
 $Seeder_default['coop_growTime'] = "DESC";
 $Seeder_default['end_job'] = 1;
 $Seeder_default['keep_planted'] = 0;
 $Seeder_default['auto_mastery_tree'] = 0;
 $Seeder_default['tree_cycles'] = 1;
 $Seeder_default['tree_speed'] = 8;
 $Seeder_default['timeformat'] = "m/d/y h:i a";
 $Seeder_default['show_tab'] = "seeds";
 $Seeder_default['show_subtab'] = "available";
 $Seeder_default['show_order'] = "realname";
 $Seeder_default['show_sort'] = "ASC";
 Seeder_WriteDefault($Seeder_default,"default_settings");

}

//========================================================================================================================
//Seeder_before_harvest
//========================================================================================================================
function Seeder_before_harvest()//revised v1.1.2
{

 AddLog2("Seeder_before_harvest> start");
 global $Seeder_settings, $Seeder_info;

  if (($Seeder_settings['bushel_booster'] == 1) || ($Seeder_settings['claim_reward'] == 1))
  {
  Seeder_loadWorld();
  }
//  if (($Seeder_settings['bushel_booster'] == 1) || ($Seeder_info['MarketStallCount'] > 0))
  if ($Seeder_settings['bushel_booster'] == 1)
  {
  AddLog2("Seeder_before_harvest> bushel_booster enabled");
  Seeder_Booster();
  }
//  if (($Seeder_settings['claim_reward'] == 1) || ($Seeder_info['MarketStallCount'] > 0))
  if ($Seeder_settings['claim_reward'] == 1)
  {
  AddLog2("Seeder_before_harvest> claim_reward enabled");
  Seeder_ClaimReward();
  }
 AddLog2("Seeder_before_harvest> end");
}
//========================================================================================================================
//Seeder_before_planting
//========================================================================================================================
function Seeder_before_planting()//revised v1.1.5
{
 AddLog2("Seeder_before_planting> start");
 global $Seeder_settings;

 DoInit();
 Seeder_loadWorld();

 if ($Seeder_settings['harvest_greenhouse'] == 1)
 {
 AddLog2("Seeder_before_planting> harvest greenhouse enabled");
 Seeder_harvest_greenhouse();
 }

 if ($Seeder_settings['mastery_greenhouse'] == 1)
 {
 AddLog2("Seeder_before_planting> mastery greenhouse enabled");
 Seeder_mastery_greenhouse();
 }
 
 if ($Seeder_settings['default_greenhouse'])
 {
 AddLog2("Seeder_before_planting> greenhouse default seed enabled");
 Seeder_default_greenhouse();
 }
 
 if ($Seeder_settings['keep_planted'] == 1)
 {
 AddLog2("Seeder_before_planting> keep planted enabled");
 Seeder_keep_planted();
 }
 
 if ($Seeder_settings['auto_mastery'] == 1)
 {
 AddLog2("Seeder_before_planting> auto mastery enabled");
 Seeder_mastery();
 }

 if ($Seeder_settings['end_job'] == 1)
 {
 AddLog2("Seeder_before_planting> end job enabled");
 Seeder_end_quest();
 }

 if ($Seeder_settings['auto_coop'] == 1)
 {
 AddLog2("Seeder_before_planting> Coop Jobs enabled");
 Seeder_CoopJobs();
 }

 AddLog2("Seeder_before_planting> end");
}
//========================================================================================================================
//Seeder_before_planting
//========================================================================================================================
function Seeder_after_missions()//revised v1.1.2
{

 global $Seeder_settings;
 AddLog2("Seeder_after_missions> start");
 DoInit();
 Seeder_loadWorld();

 AddLog2("Seeder_after_missions> end");
 
}
//========================================================================================================================
?>

