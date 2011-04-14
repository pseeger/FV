<?php
//========================================================================================================================
//Seeder_tabs.php
//by N1n9u3m
//========================================================================================================================
//Seeder_tabs
//========================================================================================================================
function Seeder_tabs($show_tab,$show_subtab)//added 1.1.4
{
global $Seeder_settings, $Seeder_info;
$worldtype = Seeder_worldtype();
$menu_onmouse = " onmouseover=this.className='menu-sel' onmouseout=this.className='menu'";
$submenu_onmouse = " onmouseover=this.className='submenu-sel' onmouseout=this.className='submenu'";

if (isset($_GET['show_order']))
{
$Seeder_settings['show_order'] = @$_GET['show_order'];
$Seeder_settings['show_sort'] = @$_GET['show_sort'];
Seeder_Write($Seeder_settings,"settings");
}
?>
<!--------------------------------------------------------------------------------------------------------------------------------------------------------->
<!-- Tabs start -->
<?php
//======================================
//show_tab
//======================================
?>

<table border="0" cellpadding="0" cellspacing="2">
 <tr height="20">
  <td <?php echo (($show_tab == "seeds")? "class='menu-sel'":"class='menu'".$menu_onmouse)?> onClick="window.location.href='main.php?show_tab=seeds&show_subtab=available'" align="center">
  <font face="Tahoma" size="1"><b>Seeds</b></font></td>
  <td <?php echo (($show_tab == "trees")? "class='menu-sel'":"class='menu'".$menu_onmouse)?> onClick="window.location.href='main.php?show_tab=trees&show_subtab=available'" align="center">
  <font face="Tahoma" size="1"><b>Trees</b></font></td>
<?php
if ($worldtype == 'farm')
{
?>
  <td <?php echo (($show_tab == "jobs")? "class='menu-sel'":"class='menu'".$menu_onmouse)?> onClick="window.location.href='main.php?show_tab=jobs&show_subtab=basic'" align="center">
  <font face="Tahoma" size="1"><b>Co-Op Jobs</b></font></td>
  <td <?php echo (($show_tab == "greenhouse")? "class='menu-sel'":"class='menu'".$menu_onmouse)?> onClick="window.location.href='main.php?show_tab=greenhouse&show_subtab=trays'" align="center">
  <font face="Tahoma" size="1"><b>Greenhouse</b></font></td>
<?php
//if ($worldtype == 'farm')
}
?>
  <td align="right">
  <font face="Tahoma" size="1"><b>&nbsp;Sort:
 <select size="1" name="show_order" onchange='this.form.submit()'>
<?php
//======================================
if ($show_tab == "seeds") {
//======================================
?>
 <option <?php echo (($Seeder_settings['show_order'] == "realname")?'selected':'')?> value="realname">Name</option>
 <option <?php echo (($Seeder_settings['show_order'] == "requiredLevel")?'selected':'')?> value="requiredLevel">Required level</option>
 <option <?php echo (($Seeder_settings['show_order'] == "limitedEndTimestamp")?'selected':'')?> value="limitedEndTimestamp">Limited end</option>

 <option <?php echo (($Seeder_settings['show_order'] == "creationDateTimestamp")?'selected':'')?> value="creationDateTimestamp">Creation Date</option>
 <option <?php echo (($Seeder_settings['show_order'] == "growTime")?'selected':'')?> value="growTime">Grow Time</option>
 <option <?php echo (($Seeder_settings['show_order'] == "plantXp")?'selected':'')?> value="plantXp">Xp</option>
 <option <?php echo (($Seeder_settings['show_order'] == "coinYield")?'selected':'')?> value="coinYield">Coin yield</option>
 <option <?php echo (($Seeder_settings['show_order'] == "profit")?'selected':'')?> value="profit">Profit</option>
 <option <?php echo (($Seeder_settings['show_order'] == "profit_time")?'selected':'')?> value="profit_time">Profit/Time</option>
 <option <?php echo (($Seeder_settings['show_order'] == "xp_time")?'selected':'')?> value="xp_time">Xp/Time</option>
 <option <?php echo (($Seeder_settings['show_order'] == "mastery_count")?'selected':'')?> value="mastery_count">Mastery count</option>
 <option <?php echo (($Seeder_settings['show_order'] == "to_mastery")?'selected':'')?> value="to_mastery">To Mastery count</option>
 <option <?php echo (($Seeder_settings['show_order'] == "reqs")?'selected':'')?> value="reqs">Requirements</option>
<?php
}
//======================================
if (($show_tab == "jobs") && ($worldtype == 'farm')) {
//======================================
?>
 <option <?php echo (($Seeder_settings['show_order'] == "realname")?'selected':'')?> value="realname">Name</option>
 <option <?php echo (($Seeder_settings['show_order'] == "requiredLevel")?'selected':'')?> value="requiredLevel">Required level</option>
 <option <?php echo (($Seeder_settings['show_order'] == "limitedEndTimestamp")?'selected':'')?> value="limitedEndTimestamp">Limited end</option>

 <option <?php echo (($Seeder_settings['show_order'] == "id")?'selected':'')?> value="id">Creation Date</option>
 <option <?php echo (($Seeder_settings['show_order'] == "requiredJoinLevel")?'selected':'')?> value="requiredJoinLevel">Required Join Level</option>
 <option <?php echo (($Seeder_settings['show_order'] == "score_growTime")?'selected':'')?> value="score_growTime">Max Grow Time</option>
 <option <?php echo (($Seeder_settings['show_order'] == "score_plots")?'selected':'')?> value="profit">Total Plots</option>
 <option <?php echo (($Seeder_settings['show_order'] == "score_coinYield")?'selected':'')?> value="score_coinYield">Coin yield</option>
 <option <?php echo (($Seeder_settings['show_order'] == "score_plantXp")?'selected':'')?> value="score_plantXp">Xp</option>
<?php
}
//======================================
if (($show_tab == "greenhouse") && ($worldtype == 'farm')) {
//======================================
?>
 <option <?php echo (($Seeder_settings['show_order'] == "tray")?'selected':'')?> value="realname">Tray</option>
<?php
}
if ($show_tab == "trees") {
//======================================
?>
 <option <?php echo (($Seeder_settings['show_order'] == "creationDateTimestamp")?'selected':'')?> value="creationDateTimestamp">Creation Date</option>
 <option <?php echo (($Seeder_settings['show_order'] == "growTime")?'selected':'')?> value="plantXp">Grow Time</option>
 <option <?php echo (($Seeder_settings['show_order'] == "coinYield")?'selected':'')?> value="coinYield">Coin yield</option>
 <option <?php echo (($Seeder_settings['show_order'] == "profit_time")?'selected':'')?> value="profit_time">Profit/Time</option>
 <option <?php echo (($Seeder_settings['show_order'] == "profit_orchard")?'selected':'')?> value="profit_orchard">Profit/Orchard</option>
 <option <?php echo (($Seeder_settings['show_order'] == "mastery_count")?'selected':'')?> value="mastery_count">Mastery count</option>
 <option <?php echo (($Seeder_settings['show_order'] == "to_mastery")?'selected':'')?> value="to_mastery">To Mastery count</option>
<?php
}
//======================================
?>
 </select>
 <select size="1" name="show_sort" onchange='this.form.submit()'>
 <option <?php echo (($Seeder_settings['show_sort'] == "ASC")?'selected':'')?> value="ASC">ASC</option>
 <option <?php echo (($Seeder_settings['show_sort'] == "DESC")?'selected':'')?> value="DESC">DESC</option>
 </select>
  </b></font></td>

 </tr>
</table>

<?php
//========================================================================================================================
//Seeds
//======================================
if ($show_tab == "seeds") {
//======================================
?>

<table border="0" cellpadding="0" cellspacing="2">
 <tr height="20">
  <td <?php echo (($show_subtab == "available")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=seeds&show_subtab=available'" align="center">
  <font face="Tahoma" size="1">Available</font></td>
  <td <?php echo (($show_subtab == "to_mastery")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=seeds&show_subtab=to_mastery'" align="center">
  <font face="Tahoma" size="1">To Mastery</font></td>
  <td <?php echo (($show_subtab == "mastered")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=seeds&show_subtab=mastered'" align="center">
  <font face="Tahoma" size="1">Mastered</font></td>
  <td <?php echo (($show_subtab == "buyable")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=seeds&show_subtab=buyable'" align="center">
  <font face="Tahoma" size="1">Not buyable</font></td>
  <td <?php echo (($show_subtab == "limited")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=seeds&show_subtab=limited'" align="center">
  <font face="Tahoma" size="1">Limited</font></td>
  <td <?php echo (($show_subtab == "limitedLocale")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=seeds&show_subtab=limitedLocale'" align="center">
  <font face="Tahoma" size="1">Limited Locale</font></td>
  <td <?php echo (($show_subtab == "requiredLevel")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=seeds&show_subtab=requiredLevel'" align="center">
  <font face="Tahoma" size="1">Required Level</font></td>
  <td <?php echo (($show_subtab == "license")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=seeds&show_subtab=license'" align="center">
  <font face="Tahoma" size="1">Required License</font></td>
  <td <?php echo (($show_subtab == "requirements")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=seeds&show_subtab=requirements'" align="center">
  <font face="Tahoma" size="1">Required Mastery</font></td>
  <td <?php echo (($show_subtab == "seedpackage")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=seeds&show_subtab=seedpackage'" align="center">
  <font face="Tahoma" size="1">Seed Package</font></td>
  <td <?php echo (($show_subtab == "isHybrid")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=seeds&show_subtab=isHybrid'" align="center">
  <font face="Tahoma" size="1">Hybrid</font></td>
  <td <?php echo (($show_subtab == "bushels")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=seeds&show_subtab=bushels'" align="center">
  <font face="Tahoma" size="1">Bushels</font></td>
 </tr>
</table>

<?php
$seeds_all = Seeder_Read("seeds");
$seeds_available = Seeder_available();
$mastery_counters = unserialize(file_get_contents(F('cropmasterycount.txt')));

if ($show_subtab == "available") //available
 {
 $seeds = $seeds_available;
 }
if ($show_subtab == "to_mastery") //All seeds
 {
 $seeds = Seeder_ArrayFilter($seeds_all, 'masterymax', '>', '0');
 $seeds = Seeder_ArrayFilter($seeds, 'to_mastery', '>', '0');
 if ($Seeder_settings['mastery_adjustment'] == 0) {$seeds = Seeder_ArrayFilter($seeds, 'mastery_level', '<', '3');}
 }
if ($show_subtab == "mastered") //All seeds
 {
 $seeds = Seeder_ArrayFilter($seeds_all, 'masterymax', '>', '0');
  if ($Seeder_settings['mastery_adjustment'] == 0) {$seeds = Seeder_ArrayFilter($seeds, 'mastery_level', '==', '3');}
  else {$seeds = Seeder_ArrayFilter($seeds, 'to_mastery', '<=', '0');}
 }
if ($show_subtab == "buyable") //All seeds
 {
 $seeds = Seeder_ArrayFilter($seeds_all, 'buyable', '==', '0');
 }
if ($show_subtab == "limited") //All seeds
 {
 $seeds = Seeder_ArrayFilter($seeds_all, 'limitedEnd', '!=', 'NULL'); //All seeds
 }
if ($show_subtab == "limitedLocale") //All seeds
 {
 $seeds = Seeder_ArrayFilter($seeds_all, 'limitedLocale', '!=', 'NULL'); //All seeds
 }
if ($show_subtab == "license") //All seeds
 {
 $seeds = Seeder_ArrayFilter($seeds_all, 'license', '!=', 'NULL'); //All seeds
 }

if ($show_subtab == "requiredLevel")  //All seeds
 {
 $seeds = Seeder_ArrayFilter($seeds_all, 'requiredLevel', '>', $Seeder_info['level']); //All seeds
 }
if ($show_subtab == "requirements") //All seeds
 {
 $seeds = Seeder_ArrayFilter($seeds_all, 'reqs', '>', '0'); //All seeds
 }
if ($show_subtab == "seedpackage") //All seeds
 {
 $seeds = Seeder_ArrayFilter($seeds_all, 'seedpackage_name', '!=', 'NULL'); //All seeds
 }
if ($show_subtab == "isHybrid") //All seeds
 {
 $seeds = Seeder_ArrayFilter($seeds_all, 'isHybrid', '==', 1); //All seeds
 }
if ($show_subtab == "bushels") //All seeds
 {
 $seeds = Seeder_ArrayFilter($seeds_all, 'bushel_count', '>', '0'); //All seeds
 }
?>

<table border="1" width="100%" cellspacing="0" cellpadding="2" bordercolor="#D4D0C8" style="border-collapse: collapse">

<?php
if (sizeof($seeds) > 0 )
{
 $seeds = Seeder_ArrayOrder($seeds, $Seeder_settings['show_order'], $Seeder_settings['show_sort']);
 foreach ($seeds as $seed)
 {
 if ($bgcolor == "#FFFFFF") {$bgcolor = "#F4F4ED";} else {$bgcolor = "#FFFFFF";}
?>
<tr bgcolor="<?php echo $bgcolor;?>">
<td align="center"  valign="top" width="48">
 <table border="0" width="100%" cellspacing="0" cellpadding="0">
 <tr><td align="center"><img border="0" src="<?php echo (($show_subtab == "bushels")? Seeder_ShowImage($seed['bushel_iconurl']):Seeder_ShowImage($seed['iconurl']))?>" width="48"></td></tr>
 <tr><td align="center" bgcolor="#8E6F4A"><img border="0" src="<?php echo (($seed['masterymax'] > 0)? Seeder_imgPath.$seed['mastery_level']."_star.png":Seeder_imgPath."space.png")?>" width="48" height="16"></td></tr>
 </table>
</td>

<td align="left" valign="top"><font size="2" face="Tahoma">
<b><?php echo $seed['realname']. (($seed['booster_time'] > 0)? " <img border='0' title='Bushel Boosted!' src='".Seeder_imgPath."bushelbooster.png' width='16' height='16'>":"");?></b></font>
<font size="1" face="Tahoma"><br>
Name: <b><?php echo $seed['name'];?></b> Code: <b><?php echo $seed['code'];?></b><br>
<?php echo (($seed['creationDate'] != "NULL")? "Creation Date: <b>".$seed['creationDate']."</b><br>":"")?>
<?php echo (($seed['buyable'] == 0)? "<b><font color='red'>Not Buyable</font></b><br>":"")?>
<?php echo (($seed['limitedEnd'] != "NULL")? "Limited : <font color='".((($seed['limitedEndTimestamp'] > time()) && ($seed['limitedStartTimestamp'] < time()))?"blue":"red")."'><b>".$seed['limitedStart']." to ".$seed['limitedEnd']."</b></font><br>":"")?>
<?php echo (($seed['unlock'] != "NULL")? "Unlock: <font color='red'><b>".$seed['unlock']."</b></font><br>":"")?>
<?php echo (($seed['license'] != "NULL")? "License: ".(($seed['licensed'] == 1)?"<font color='blue'><b>Licensed</b> (".$seed['license'].")":"<font color='red'><b>Locked</b> (".$seed['license'].")")."</font><br>":"")?>
<?php echo (($seed['limitedLocale'] != "NULL")? "Limited Locale: <font color='".(($Seeder_info['locale'] == $seed['limitedLocale'])?"blue":"red")."'><b>".$seed['limitedLocale']."</b></font><br>":"")?>
<?php echo "Required Level: <font color='".(($seed['requiredLevel'] <= $Seeder_info['level'])?"black":"red")."'><b>".$seed['requiredLevel']."</b></font><br>";?>
Cost: <b><?php echo $seed['cost'];?></b><br>
Xp: <b><?php echo $seed['plantXp'];?></b><br>
</font></td>

<td align="left" valign="top"><font size="1" face="Tahoma">
Grow Time: <b><?php echo $seed['growTime'];?>hs</b><br>
Coin Yield: <b><?php echo $seed['coinYield'];?></b><br>
Profit: <b><?php echo $seed['profit'];?></b><br>
Profit/Hour: <b><?php echo $seed['profit_time'];?></b><br>
Xp/Hour: <b><?php echo $seed['xp_time'];?></b><br>
</font></td>

<td align="left" valign="top"><font size="1" face="Tahoma">
Harvested: <b><?php echo $seed['mastery_count'];?></b><br>
<?php echo (($seed['plots_planted'] > 0)? "Planted: <font color='red'><b>".$seed['plots_planted']."</b></font><br>":"")?>
<?php echo (($seed['seedpackage_name'] != "NULL")? "Seed Package : <font color='".(($seed['seedpackage_count'] > 0)?"blue":"red")."'><b>".$seed['seedpackage_count']."</b></font><br>Green House : <b>".$seed['seedpackage_tray']."</b><br>Packages to Mastery : <b>".$seed['seedpackages_to_mastery']."</b><br>":"")?>
<?php
if ($seed['masterymax'] > 0)
{
#$tobooster = round((($seed['to_mastery'] - $seed['plots_planted'])/ 2), 0); if ($tobooster < 0) {$tobooster = 0;}
$tobooster = ceil(($seed['to_mastery'] - $seed['plots_planted'])/ 2); if ($tobooster < 0) {$tobooster = 0;}

echo "Mastery: <b>".$seed['masterymax']."</b><br>";
echo "Mastery Level: <b>".(($seed['mastery_level'] == 3)? "<font color='red'>Mastered!</font>":$seed['mastery_level'])."</b><br>";
echo "To Mastery: <b>".$seed['to_mastery']."</b><br>";

} else {
echo "Mastery: <b>No Mastery</b><br>";
$tobooster = 0;
}//if ($seed['masterymax'] > 0)
?>
</font></td>

<td align="left" valign="top"><font size="1" face="Tahoma">
<?php
if ($seed['bushel_name'] != "NULL")
{

echo "Bushels: <b>".$seed['bushel_count']."</b><br>";
echo ((($seed['masterymax'] > 0) && ($tobooster > 0))? "To Mastery Boosted: <b>".$tobooster."</b><br>":"");
echo (($seed['booster_time'] > 0)? "Booster Time: <b>".Seeder_TimeLeft(time(),$seed['booster_time'])."</b><br>":"");

} else {echo "Bushels: <b>Unavailable</b><br>";}//if ($seed['bushel_name'] != "NULL")

if ($seed['reqs'] > 0)//fix 1.1.6
//if (($seed['reqs'] > 0) && ($seed['isHybrid'] == 0))//fix 1.1.6
{
 $name = $seed['name'];
 $reqs = $seed['requirements'];
 $reqs_count = count($reqs);
 for ($i = 0; $i < $reqs_count; $i++)
 {
  if (($seed['requirements'][$i] != "farm") && ($seed['requirements'][$i] != "england"))
  {
   $mastery_req =  @$mastery_counters[Units_GetCodeByName($reqs[$i])];
   if ($mastery_req <> 2)
   {
   echo "<font color='red'>Required Mastery : <b>".Units_GetRealnameByName($reqs[$i])."</b></font><br>";
   } else {
   echo "Required Mastery : <b>".Units_GetRealnameByName($reqs[$i])."</b><br>";
   }
  }
  if ($seed['requirements'][$i] == "england")
  {
   echo "<font color='red'>Required : <b>English Countryside</b></font><br>";
  }
 }
 unset($reqs);
}

if ($seed['isHybrid'] == 1)//added 1.1.6
{
 echo "<b>Hybrid</b><br><table border='1' width='100%' cellspacing='0' cellpadding='2' bordercolor='#D4D0C8' style='border-collapse: collapse'>"."\n";

 foreach ($seed['genealogy'] as $gen)
 {
 echo "<tr>"."\n";
 echo "<td align='center' width='16' valign='top'><img border='0' src='".$gen['iconurl']."' width='16' height='16'></td>";
 echo "<td align='left' width='16' valign='top'><font size='1' face='Tahoma'>".$gen['quantity']."</font></td>";
 echo "<td align='left' valign='top'><font size='1' face='Tahoma'><b>".$gen['realname']."</b></font></td>";
 echo "</tr>"."\n";
 }

 echo "</table>"."\n";
}
?>
</font></td>
</tr>

<?php
}//foreach ($seeds as $seed)

} else { //if (sizeof($seeds) > 0 )
echo "<tr bgcolor='#FFFFFF'><td align='left' valign='top'><font size='2' face='Tahoma'><b>No ".(($show_subtab == "bushels")?"bushels":"seeds")." found.</b></font><br></td></tr>"."\n";
}
?>
</table>
<?php
unset($seeds_all);unset($seeds_available);unset($mastery_counters);

}// if ($show_tab == "seeds")
//======================================
//Co-Op jobs
//========================================================================================================================
if ($show_tab == "jobs") {
//======================================
?>

<table border="0" cellpadding="0" cellspacing="2">
 <tr height="20">
  <td <?php echo (($show_subtab == "basic")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=jobs&show_subtab=basic'" align="center">
  <font face="Tahoma" size="1">Basic Jobs</font></td>
  <td <?php echo (($show_subtab == "crafting")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=jobs&show_subtab=crafting'" align="center">
  <font face="Tahoma" size="1">Crafting Jobs</font></td>
  <td <?php echo (($show_subtab == "limited")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=jobs&show_subtab=limited'" align="center">
  <font face="Tahoma" size="1">Limited</font></td>
  <td <?php echo (($show_subtab == "requiredLevel")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=jobs&show_subtab=requiredLevel'" align="center">
  <font face="Tahoma" size="1">Required Level</font></td>
  <td <?php echo (($show_subtab == "license")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=jobs&show_subtab=license'" align="center">
  <font face="Tahoma" size="1">Required License</font></td>
  <td <?php echo (($show_subtab == "requiredHostItemName")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=jobs&show_subtab=requiredHostItemName'" align="center">
  <font face="Tahoma" size="1">Required Host Buiding</font></td>
 </tr>
</table>

<?php
$quests_all = Seeder_ReadDefault("quests");
$quests_available = Seeder_quests_available();
$quest_active = Seeder_Read("activeMission");
if ((is_array($quest_active)) && ($coop_id != "active_mission_id_none"))
{
$quest_active_id = $quest_active['id'];
} else {
$quest_active_id = 0;
}
unset($quest_active);

if ($show_subtab == "basic")  //available
 {
 $quests = Seeder_ArrayFilter($quests_available, 'type', '==', 'basic');
 }
if ($show_subtab == "crafting")  //available
 {
 $quests = Seeder_ArrayFilter($quests_available, 'type', '==', 'crafting');
 if ($Seeder_info['craftingbakery'] == 0) {$quests = Seeder_ArrayFilter($quests, 'requiredHostItemName', '<>', 'craftingbakery');}
 if ($Seeder_info['craftingspa'] == 0) {$quests = Seeder_ArrayFilter($quests, 'requiredHostItemName', '<>', 'craftingspa');}
 if ($Seeder_info['craftingwinery'] == 0) {$quests = Seeder_ArrayFilter($quests, 'requiredHostItemName', '<>', 'craftingwinery');}
 }
if ($show_subtab == "limited")  //All jobs
 {
 $quests = Seeder_ArrayFilter($quests_all, 'limitedEnd', '!=', 'NULL');
 }
if ($show_subtab == "requiredLevel")  //All jobs
 {
 $quests = Seeder_ArrayFilter($quests_all, 'requiredLevel', '>', $Seeder_info['level']);
 }

if ($show_subtab == "requiredHostItemName")  //All jobs
 {
 $quests = Seeder_ArrayFilter($quests_all, 'type', '==', 'crafting');
 if ($Seeder_info['craftingbakery'] == 1) {$quests = Seeder_ArrayFilter($quests, 'requiredHostItemName', '<>', 'craftingbakery');}
 if ($Seeder_info['craftingspa'] == 1) {$quests = Seeder_ArrayFilter($quests, 'requiredHostItemName', '<>', 'craftingspa');}
 if ($Seeder_info['craftingwinery'] == 1) {$quests = Seeder_ArrayFilter($quests, 'requiredHostItemName', '<>', 'craftingwinery');}
 }

if ($show_subtab == "license")  //All jobs
 {
 $quests = Seeder_ArrayFilter($quests_all, 'jobUtilHandler', '==', "SMLicenseMissionHandler");
 }


?>

<table border="1" width="100%" cellspacing="0" cellpadding="2" bordercolor="#D4D0C8" style="border-collapse: collapse">

<?php
if (sizeof($quests) > 0 )
{
$quests = Seeder_ArrayOrder($quests, $Seeder_settings['show_order'], $Seeder_settings['show_sort']);
foreach ($quests as $quest)
{

if ($bgcolor == "#FFFFFF") {$bgcolor = "#F4F4ED";} else {$bgcolor = "#FFFFFF";}

?>
<tr bgcolor="<?php echo $bgcolor;?>">
 <td align="center" valign="top" width="48">
 <img border="0" src="<?php echo Seeder_ShowImage($quest['icon']['url']);?>" width="48"><br>
 <?php
if (($show_subtab == "basic") || ($show_subtab == "crafting"))
{
echo (($quest_active_id == 0)?"":"<input class='on' type='button' name='".$quest['id']."' value='Start' onclick='Start_Job(this);return false;' style='width:48px'>");
}
?>
 </td>

 <td align="left" valign="top"><font size="2" face="Tahoma">
 <b><?php echo $quest['realname']. (($quest['id'] == $quest_active_id)? " <img border='0' title='Co-Op Active!' src='".Seeder_imgPath."bushelbooster.png' width='16' height='16'>":"");?></b></font>
 <font size="1" face="Tahoma"><br>
 Type: <b><?php echo $quest['type'];?></b><br>
 <?php echo "Required Level: <font color='".(($quest['requiredLevel'] <= $Seeder_info['level'])?"black":"red")."'><b>".$quest['requiredLevel']."</b></font><br>";?>
 <?php echo "Required Join Level: <font color='".(($quest['requiredJoinLevel'] <= $Seeder_info['level'])?"black":"red")."'><b>".$quest['requiredJoinLevel']."</b></font><br>";?>
 <?php echo ((!$quest['requiredHostItemName'])? "":"Required Host Item: <font color='".(($Seeder_info[$quest['requiredHostItemName']] == 1)?"black":"red")."'><b>".Units_GetRealnameByName($quest['requiredHostItemName'])."</b></font><br>" )?>
<?php echo (($quest['limitedEnd'] != "NULL")? "Limited : <font color='".((($quest['limitedEndTimestamp'] > time()) && ($quest['limitedStartTimestamp'] < time()))?"blue":"red")."'><b>".$quest['limitedStart']." to ".$quest['limitedEnd']."</b></font><br>":"")?>
 Max Grow Time: <b><?php echo $quest['score_growTime'];?>hs</b><br>
 Total Plots: <b><?php echo $quest['score_plots'];?></b><br>

 </font></td>

<td align="left" valign="top"><font size="1" face="Tahoma">

 <table border="1" width="100%" cellspacing="0" cellpadding="2" bordercolor='#D4D0C8' style='border-collapse: collapse'>
 <?php
 $req = $quest['completionRequirements'];
 $req_count = (count($req) - 1);

 for ($i = $req_count; $i >= 0; $i--)
 {
# $timeLimit = round(($req[$i]['timeLimit']) * 23, 0);
 $timeLimit = ($req[$i]['timeLimit']) * (23 * 60 * 60);
 $timeLimit = Seeder_TimeLeft(time(),(time() + $timeLimit));
 $reward = "NULL";

 if ($req[$i]['reward']['item'])
 {
 $reward = $req[$i]['reward']['item'];
 }
 if ($req[$i]['reward'][0]['recipeId'])
 {
 $reward = Units_GetNameByCode($req[$i]['reward'][0]['recipeId']);
 }

  echo "<tr>"."\n";
  echo "<td align='left' valign='top' width='16'>".(($reward == "NULL")?"<img border='0' src='".Seeder_imgPath."space.png' width='16'><br>":"<img border='0' src='". Seeder_ShowImagebyName($reward) ."' width='16'><br>")."</td>"."\n";
  echo "<td align='left' valign='top' width='25%'><font size='1' face='Tahoma'><b>".$req[$i]['name']."</b></font></td>"."\n";
  echo "<td align='left' valign='top' width='25%'><font size='1' face='Tahoma'><b>".$timeLimit."</b></font></td>"."\n";
  echo "<td align='left' valign='top' width='25%'><font size='1' face='Tahoma'>Xp: <b>".$req[$i]['reward']['experience']."</b></font></td>"."\n";
  echo "<td align='left' valign='top' width='25%'><font size='1' face='Tahoma'>Coins: <b>".$req[$i]['reward']['coins']."</b></font></td>"."\n";
  echo "<tr>"."\n";

}// for ($i = $req_count; $i >= 0; $i--)
?>
 </table>
 </font></td>

<td align="left" valign="top"><font size="1" face="Tahoma">

 <table border="1" width="100%" cellspacing="0" cellpadding="2" bordercolor='#D4D0C8' style='border-collapse: collapse'>
 <?php
 $reqs = $quest['completionRequirements'][0]['requirement'];
 $reqs_count = (count($reqs) - 1);

 for ($i = $reqs_count; $i >= 0; $i--)
 {
 if ($reqs[$i]['action'] == "seed")
  {
  echo "<tr>"."\n";
  echo "<td align='center' width='16'><img border='0' src='".Seeder_ShowImagebyName($reqs[$i]['type'])."' width='16'></td>"."\n";
  echo "<td width='50%'><font size='1' face='Tahoma'><b>".Units_GetRealnameByName($reqs[$i]['type'])."</b></font></td>"."\n";
  echo "<td width='50%'><font size='1' face='Tahoma'>".$reqs[$i]['many']."</font></td>"."\n";
  echo "</tr>"."\n";
  }
 }
?>
 </table>
 </font></td>

</tr>
<?php
}//foreach ($quests as $quest)

} else { //if (sizeof($quests) > 0 )
echo "<tr bgcolor='#FFFFFF'><td align='left' valign='top'><font size='2' face='Tahoma'><b>No Co-Op Job found.</b></font><br></td></tr>"."\n";
}
?>
 </table>
</td></tr>
<?php
unset($quests_all);unset($quests_available);
}//if ($show_tab == "jobs")
//======================================
//Green House
//========================================================================================================================
if ($show_tab == "greenhouse") {
//======================================
?>

<table border="0" cellpadding="0" cellspacing="2">
 <tr height="20">
  <td <?php echo (($show_subtab == "trays")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=greenhouse&show_subtab=trays'" align="center">
  <font face="Tahoma" size="1">Trays</font></td>
  <td <?php echo (($show_subtab == "genealogy")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=greenhouse&show_subtab=genealogy'" align="center">
  <font face="Tahoma" size="1">Genealogy</font></td>
 </tr>
</table>

<?php
$greenhouse = Seeder_Read("greenhouse");
$seeds_all = Seeder_Read("seeds");
$seeds_all = Seeder_ArrayFilter($seeds_all, 'seedpackage_code', '!=', 'NULL');

foreach ($seeds_all as $seed)
{
$seedpackage_code = $seed['seedpackage_code'];
$seeds[$seedpackage_code] = $seed;
}
unset($seeds_all);

#print_r($greenhouse);

if ($show_subtab == "trays")
{
?>

<table border="1" width="100%" cellspacing="0" cellpadding="2" bordercolor="#D4D0C8" style="border-collapse: collapse">

<?php
if ($Seeder_info['greenhouse'] == 1)
{

for ($i = 0; $i < $Seeder_info['greenhouse_trays']; $i++)
{

$tray = ($i + 1);
$seedpackage = @$greenhouse['trays'][$i]['trayResult'];

if ($seedpackage)
{
$empty = 0;
$Friends = $greenhouse['trays'][$i]['tray']['helpingFriendIds'];
$startTime = $greenhouse['trays'][$i]['tray']['startTime'];
$endTime = ($startTime + $greenhouse['breedingDuration']);

 if (count($Friends) > 0) {$endTime = $endTime - (count($Friends) * 24*60*60);}
 $TimeLeft = Seeder_TimeLeft(time(), $endTime);
 if ($TimeLeft == 0)
 {
 $TimeLeft = "<font color='red'>Ready</font>";
 } else {
 $TimeLeft = $TimeLeft ." (".date($Seeder_settings['timeformat'],Seeder_TimeZone($endTime)).")";
 }

} else {
$empty = 1;
}

#print_r($tray);

if ($bgcolor == "#FFFFFF") {$bgcolor = "#F4F4ED";} else {$bgcolor = "#FFFFFF";}
?>
<tr bgcolor="<?php echo $bgcolor;?>">
 <td align="center" valign="top" width="48">
<?php
if ($empty == 0)
{
?>
 <table border="0" width="100%" cellspacing="0" cellpadding="0">
 <tr><td align="center"><img border="0" src="<?php echo Seeder_ShowImage($seeds[$seedpackage]['iconurl'])?>" width="48"></td></tr>
 <tr><td align="center" bgcolor="#8E6F4A"><img border="0" src="<?php echo (($seeds[$seedpackage]['masterymax'] > 0)? Seeder_imgPath.$seeds[$seedpackage]['mastery_level']."_star.png":Seeder_imgPath."space.png")?>" width="48" height="16"></td></tr>
 </table>
<?php
} else {echo "&nbsp;";}
?>
 </td>

 <td align="left" valign="top"><font size="1" face="Tahoma">
 <b>Tray: <?php echo $tray;?></b><br>
<?php
if ($empty == 0)
{
?>
 <font size="2" face="Tahoma"><b><?php echo $seeds[$seedpackage]['seedpackage_realname'];?></b></font>
 <font size="1" face="Tahoma"><br><b><?php echo $seeds[$seedpackage]['seedpackage_name']." (".$seedpackage.")";?></b>
 <font size="1" face="Tahoma"><br>Harvest: <b><?php echo $TimeLeft;?></b>
<?php
} else {echo "Empty";}
?>
 </font></td>

<td align="left" valign="top"><font size="1" face="Tahoma">
<?php
if ($empty == 0)
{
 echo "Item Ingredients: <br><table border='1' width='100%' cellspacing='0' cellpadding='2' bordercolor='#D4D0C8' style='border-collapse: collapse'>"."\n";

 foreach ($greenhouse['trays'][$i]['tray']['itemIngredients'] as $ing)
 {
 echo "<tr>"."\n";
 echo "<td align='center' width='16' valign='top'><img border='0' src='".Seeder_ShowImagebyCode($ing['code'])."' width='16' height='16'></td>";
 echo "<td align='left' width='16' valign='top'><font size='1' face='Tahoma'>".$ing['quantity']."</font></td>";
 echo "<td align='left' valign='top'><font size='1' face='Tahoma'><b>".Units_GetRealnameByCode($ing['code'])."</b></font></td>";
 echo "</tr>"."\n";
 }

 echo "</table>"."\n";
}
?>

 </font></td>

<td align="left" valign="top"><font size="1" face="Tahoma">
 <?php
if (($empty == 0) && (count($Friends) > 0))
{
 echo "Helping Friends :<br><table border='1' width='100%' cellspacing='0' cellpadding='2' bordercolor='#D4D0C8' style='border-collapse: collapse'>"."\n";

 foreach ($Friends as $Friend)
 {
 echo "<tr>"."\n";
 echo "<td align='left' valign='top'><font size='1' face='Tahoma'><b>".number_format($Friend, 0, '', '')."</b></font></td>"."\n";
 echo "</tr>"."\n";
 }

 echo "</table>"."\n";
}
?>

 </font></td>

<td align="left" valign="top"><font size="1" face="Tahoma">
 <?php
if ($empty == 0)
{
 if (($endTime <= time()) || (count($Friends) >= 3))
 {
 echo "<input type='button' name='harvest_tray_".($i + 1)."' value='Harvest Tray' onclick='Harvest_Tray(".($i + 1).");return false;' title='Harvest Tray' style='width:100px;'>"."\n";
 } else {
 echo "<input type='button' name='harvest_tray_".($i + 1)."' value='Harvest Tray' disabled title='Harvest Tray' style='width:100px;'>"."\n";
 }

}
?>

 </font></td>
 
</tr>
<?php
}//for ($i = $Seeder_info['greenhouse_trays']; $i >= 0; $i++)

} else { //if ($Seeder_info['greenhouse'] == 1)
echo "<tr bgcolor='#FFFFFF'><td align='left' valign='top'><font size='2' face='Tahoma'><b>No Greenhouse found.</b></font><br></td></tr>"."\n";
}
?>
 </table>
</td></tr>
<?php
}//if ($show_subtab == "trays")
//======================================
if ($show_subtab == "genealogy")
{
?>

<table border="1" width="100%" cellspacing="0" cellpadding="2" bordercolor="#D4D0C8" style="border-collapse: collapse">

<?php

foreach ($greenhouse['genealogy'] as $gen)
{

#print_r($gen);

if ($bgcolor == "#FFFFFF") {$bgcolor = "#F4F4ED";} else {$bgcolor = "#FFFFFF";}
$seedpackage = $gen['itemCode'];
?>
<tr bgcolor="<?php echo $bgcolor;?>">
 <td align="center" valign="top" width="48">

 <table border="0" width="100%" cellspacing="0" cellpadding="0">
 <tr><td align="center"><img border="0" src="<?php echo Seeder_ShowImage($seeds[$seedpackage]['iconurl'])?>" width="48"></td></tr>
 <tr><td align="center" bgcolor="#8E6F4A"><img border="0" src="<?php echo (($seeds[$seedpackage]['masterymax'] > 0)? Seeder_imgPath.$seeds[$seedpackage]['mastery_level']."_star.png":Seeder_imgPath."space.png")?>" width="48" height="16"></td></tr>
 </table>

 </td>

 <td align="left" valign="top"><font size="1" face="Tahoma">

 <font size="2" face="Tahoma"><b><?php echo $seeds[$seedpackage]['seedpackage_realname'];?></b></font>
 <font size="1" face="Tahoma"><br><b><?php echo $seeds[$seedpackage]['seedpackage_name']." (".$seedpackage.")";?></b>
 <font size="1" face="Tahoma"><br>Green House Level: <b><?php echo $gen['startingUnlockState'];?></b>

 </font></td>

<td align="left" valign="top"><font size="1" face="Tahoma">
<?php

 echo "Item Ingredients: <br><table border='1' width='100%' cellspacing='0' cellpadding='2' bordercolor='#D4D0C8' style='border-collapse: collapse'>"."\n";

 foreach ($gen['ingredient'] as $ing)
 {
 echo "<tr>"."\n";
 echo "<td align='center' width='16' valign='top'><img border='0' src='".Seeder_ShowImagebyCode($ing['code'])."' width='16' height='16'></td>"."\n";
 echo "<td align='left' width='16' valign='top'><font size='1' face='Tahoma'>".$ing['quantity']."</font></td>"."\n";
 echo "<td align='left' valign='top'><font size='1' face='Tahoma'><b>".Units_GetRealnameByCode($ing['code'])."</b></font></td>"."\n";
 echo "</tr>"."\n";
 }

 echo "</table>"."\n";

?>

 </font></td>

</tr>
<?php
}//foreach ($greenhouse['genealogy'] as $gen)

?>
 </table>
</td></tr>
<?php
}//if ($show_subtab == "genealogy")


unset($greenhouse);unset($seeds);
}//if ($show_tab == "greenhouse")
//======================================
//======================================
//Trees
//======================================
if ($show_tab == "trees") {
//======================================
?>

<table border="0" cellpadding="0" cellspacing="2">
 <tr height="20">
  <td <?php echo (($show_subtab == "available")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=trees&show_subtab=available'" align="center">
  <font face="Tahoma" size="1">Available</font></td>
  <td <?php echo (($show_subtab == "to_mastery")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=trees&show_subtab=to_mastery'" align="center">
  <font face="Tahoma" size="1">To Mastery</font></td>
  <td <?php echo (($show_subtab == "mastered")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=trees&show_subtab=mastered'" align="center">
  <font face="Tahoma" size="1">Mastered</font></td>
  <td <?php echo (($show_subtab == "coins")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=trees&show_subtab=coins'" align="center">
  <font face="Tahoma" size="1">Buyable Coins</font></td>
  <td <?php echo (($show_subtab == "cash")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=trees&show_subtab=cash'" align="center">
  <font face="Tahoma" size="1">Buyable Cash</font></td>
  <td <?php echo (($show_subtab == "giftable")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=trees&show_subtab=giftable'" align="center">
  <font face="Tahoma" size="1">Giftable</font></td>
  <td <?php echo (($show_subtab == "reserved")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=trees&show_subtab=reserved'" align="center">
  <font face="Tahoma" size="1">Reserved</font></td>
  <td <?php echo (($show_subtab == "limited")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=trees&show_subtab=limited'" align="center">
  <font face="Tahoma" size="1">Limited</font></td>
  <td <?php echo (($show_subtab == "locked")? "class='submenu-sel'":"class='submenu'".$submenu_onmouse)?> onClick="window.location.href='main.php?show_tab=trees&show_subtab=locked'" align="center">
  <font face="Tahoma" size="1">Locked</font></td>
 </tr>
</table>

<?php
$trees = Seeder_MakeTrees();
#print_r($trees);

if ($show_subtab == "available")
 {
  $trees = Seeder_ArrayFilter($trees, 'limitedStartTimestamp', '<=', time());
  $trees = Seeder_ArrayFilter($trees, 'limitedEndTimestamp', '>=', time());
  $trees = Seeder_ArrayFilter($trees, 'requiredLevel', '<=', $Seeder_info['level']);
  $trees = Seeder_ArrayFilter($trees, 'locked', '==', "NULL");
 }
if ($show_subtab == "to_mastery")
 {
  $trees = Seeder_ArrayFilter($trees, 'masterymax', '>', 0);
  $trees = Seeder_ArrayFilter($trees, 'to_mastery', '>', 0);
 }
if ($show_subtab == "mastered")
 {
  $trees = Seeder_ArrayFilter($trees, 'masterymax', '>', 0);
  $trees = Seeder_ArrayFilter($trees, 'to_mastery', '==', 0);
 }
if ($show_subtab == "coins")
 {
  $trees = Seeder_ArrayFilter($trees, 'buyable', '==', "true");
  $trees = Seeder_ArrayFilter($trees, 'market', '<>', "cash");
 }
if ($show_subtab == "cash")
 {
  $trees = Seeder_ArrayFilter($trees, 'buyable', '==', "true");
  $trees = Seeder_ArrayFilter($trees, 'market', '==', "cash");
 }
if ($show_subtab == "giftable")
 {
  $trees = Seeder_ArrayFilter($trees, 'giftable', '==', "true");
 }
if ($show_subtab == "reserved")
 {
  $trees = Seeder_ArrayFilter($trees, 'reserved', '==', 1);
 }
if ($show_subtab == "limited")
 {
  $trees = Seeder_ArrayFilter($trees, 'limitedEnd', '!=', "NULL");
 }
if ($show_subtab == "locked")
 {
  $trees = Seeder_ArrayFilter($trees, 'locked', '!=', "NULL");
 }
?>

<table border="1" width="100%" cellspacing="0" cellpadding="2" bordercolor="#D4D0C8" style="border-collapse: collapse">

<?php
if (sizeof($trees) > 0 )
{
 #print_r($trees);
 $trees = Seeder_ArrayOrder($trees, $Seeder_settings['show_order'], $Seeder_settings['show_sort']);
 foreach ($trees as $tree)
 {
 if ($bgcolor == "#FFFFFF") {$bgcolor = "#F4F4ED";} else {$bgcolor = "#FFFFFF";}

?>
<tr bgcolor="<?php echo $bgcolor;?>">
<td align="center"  valign="top" width="48">
 <table border="0" width="100%" cellspacing="0" cellpadding="0">
 <tr><td align="center"><img border="0" src="<?php echo Seeder_ShowImage($tree['iconurl'])?>" width="48"></td></tr>
 <tr><td align="center" bgcolor="#8E6F4A"><img border="0" src="<?php echo (($tree['mastery'] == true)? Seeder_imgPath.$tree['mastery_level']."_star.png":Seeder_imgPath."space.png")?>" width="48" height="16"></td></tr>
 </table>
</td>

<td align="left" valign="top"><font size="2" face="Tahoma">
<b><?php echo $tree['realname'];?></b></font>
<font size="1" face="Tahoma"><br>
<?php echo (($tree['creationDate'] != "NULL")? "Creation Date: <b>".$tree['creationDate']."</b><br>":"")?>
Buyable: <b><?php echo $tree['buyable'];?></b><br>
Giftable: <b><?php echo $tree['giftable'];?></b><br>
<?php echo (($tree['market'] != "NULL")? "Market: <b>".$tree['market']."</b><br>":"")?>
<?php echo (($tree['cash'])? "Cash: <b>".$tree['cash']."</b><br>":"")?>
Cost: <b><?php echo $tree['cost'];?></b><br>
<?php echo "Required Level: <font color='".(($tree['requiredLevel'] <= $Seeder_info['level'])?"black":"red")."'><b>".$tree['requiredLevel']."</b></font><br>";?>
<?php echo (($tree['limitedEnd'] != "NULL")? "Limited : <font color='".((($tree['limitedEndTimestamp'] > time()) && ($tree['limitedStartTimestamp'] < time()))?"blue":"red")."'><b>".$tree['limitedStart']." to ".$tree['limitedEnd']."</b></font><br>":"")?>
<?php echo (($tree['locked'] != "NULL")? "Locked : <font color='red'><b>".$tree['locked']."</b></font><br>":"")?>
</font></td>

<td align="left" valign="top"><font size="1" face="Tahoma">
Grow Time: <b><?php echo Seeder_TimeLeft(time(),($tree['growTime'] * 60 * 60) + time());?></b><br>
Coin Yield: <b><?php echo $tree['coinYield'];?></b><br>
Profit/Hour: <b><?php echo $tree['profit_time'];?></b><br>
Profit/Orchard: <b><?php echo $tree['profit_orchard'];?></b><br>
</font></td>
<td align="left" valign="top"><font size="1" face="Tahoma">
Harvested: <b><?php echo $tree['mastery_count'];?></b><br>
<?php
if ($tree['masterymax'] > 0)
{
echo "Mastery: <b>".$tree['masterymax']."</b><br>";
echo "Mastery Level: <b>".(($tree['mastery_level'] == 3)? "<font color='red'>Mastered!</font>":$tree['mastery_level'])."</b><br>";
echo "To Mastery: <b>".$tree['to_mastery']."</b><br>";

} else {
echo "Mastery: <b>No Mastery</b><br>";
}//if ($tree['masterymax'] > 0)
?>
</font></td>

<td align="left" valign="top"><font size="1" face="Tahoma">
<?php
if ($tree['nextLevel'])
{
echo "<table border='0' width='100%' cellspacing='0' cellpadding='2'>";
echo "<tr><td align='center' valign='top' width='48'><img border='0' src='".Seeder_ShowImagebyCode($tree['nextLevel'])."' width='48'></td></tr>";
echo "<tr><td valign='top'><font size='1' face='Tahoma'>Next Level: <b>".Units_GetRealnameByCode($tree['nextLevel'])."</b></font></td></tr>";
echo "</table>";
} else {echo "Next Level: <b>Unavailable</b><br>";}//if ($tree['nextLevel'])
?>
</font></td>
</tr>

<?php
}//foreach ($trees as $tree)

} else { //if (sizeof($trees) > 0 )
echo "<tr bgcolor='#FFFFFF'><td align='left' valign='top'><font size='2' face='Tahoma'><b>No ".(($show_subtab == "bushels")?"bushels":"trees")." found.</b></font><br></td></tr>"."\n";
}
?>
</table>
<?php
unset($trees_all);unset($mastery_counters);unset($mastery_levels);
}//if ($show_tab == "trees")
//======================================
?>
</table>
<?php


}//function Seeder_tabs
//========================================================================================================================
?>
