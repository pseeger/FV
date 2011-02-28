<?php
 define("PX_VER_SETTINGS", '22124');
 define("PX_DATE_SETTINGS", '2011-02-27');

 define('settings_URL', '/plugins/Settings/main.php');

//------------------------------------------------------------------------------
// settings_init
//------------------------------------------------------------------------------
function Settings_init()
 {
  global $hooks;
  global $this_plugin;

  echo "in settings_init !!!\r\n";

  if (PX_VER_PARSER != PX_VER_SETTINGS)
    echo "\r\n******\r\nERROR: PX's updated settings version (". PX_VER_SETTINGS .") doesn't match parser version (".PX_VER_PARSER.")\r\n******\r\n";
}


//--------------------------
// CreateDefaultSettings()
//
// Create an default settings array and pass to SaveSettings()
//--------------------------------------------------------------


function CreateDefaultSettings() {
  $dset = array();

  $dset['version'] = PX_VER_SETTINGS;
  $dset['e_harvest'] = 0;
  $dset['e_biplane'] = 0;
  $dset['e_h_animal'] = 0;
  $dset['e_h_tree'] = 0;
  $dset['e_h_arborist'] = 0;
  $dset['e_h_arborist_at'] = 75;
  $dset['e_h_arborist_min'] = 5;
  $dset['e_seed'] = 0;
  $dset['e_seed_keep'] = 0;
  $dset['e_hoe'] = 0;
  $dset['e_combine'] = 0;
  $dset['H_Animal']['cow'] = 0;
  $dset['H_Animal']['turkeybaby'] = 0;
  $dset['H_Animal']['turkeybabybourbon'] = 0;
  $dset['H_Animal']['uglyduck'] = 0;
  $dset['H_Animal']['chicken'] = 0;
  $dset['H_Animal']['cat_'] = 0;
  $dset['e_h_farmhands'] = 0;
  $dset['e_h_farmhands_at'] = 75;
  $dset['e_h_farmhands_min'] = 5;
  $dset['e_h_building'] = 0;
  $dset['e_h_building_coop'] = 0;
  $dset['e_h_building_dairy'] = 0;
  $dset['e_h_building_horse'] = 0;
  $dset['e_h_building_nursery'] = 0;
  $dset['e_h_building_bees'] = 0;
  $dset['e_h_building_hauntedhouse'] = 0;
  $dset['e_h_building_trough'] = 0;
  $dset['e_h_building_orchard'] = 0;
  $dset['e_h_building_turkeyroost'] = 0;
  $dset['e_h_building_wworkshop'] = 0;
  $dset['e_h_building_snowman'] = 0;
  $dset['e_h_building_duckpond'] = 0;
  $dset['e_h_building_ccastle'] = 0;
  $dset['e_h_building_lcottage'] = 0;
  $dset['e_gzip'] = 1;
  $dset['farm_server'] = 0;
  $dset['bot_speed'] = 8;
  $dset['fuel_plow'] = 0;
  $dset['fuel_place'] = 0;
  $dset['fuel_harvest'] = 0;
  $dset['fuel_combine'] = 0;
  $dset['not_plugin'] = '';
  $dset['lonlyanimals'] = 1;
  $dset['wanderinganimals'] = 1;
  $dset['e_harvest_spec'] = 0;
  $dset['spec_crop'] = '';
  $dset['spec_crop_quantity'] = 0;
  $dset['acceptneighborhelp'] = 1;
  SaveSettings($dset);

 return $dset;
}


//--------------------------------------------------------------
// SaveSettings(array)
//
// Save the supplied settings array into FBID_settings.txt
//--------------------------------------------------------------

function SaveSettings($settings) {
 $set2 = array();
  $settings['version'] = PX_VER_SETTINGS;
  foreach ($settings as $key => $sopt)
   {
    if (count($settings[$key]) > 1)
     {
       $multi = '';
       foreach ($settings[$key] as $name => $check)
        $multi .= "$name:$check:";

       $multi = substr($multi, 0, -1); //rip the last : off
       $set2[] = "$key:LIST:$multi";
   }
   else
   {
   $set2[] = "$key:$sopt";
  }
 }
 file_put_contents(F('settings.txt'),implode(';', $set2));
}

//--------------------------------------------------------------
// LoadSavedSettings()
//
// Read FBID_settings.txt if exists, call CreateDefaultSettings if not or if version is incorrect
//--------------------------------------------------------------
function LoadSavedSettings() {
  $px_Setopts = array();

  if (file_exists(F('settings.txt'))) {
    $set_read_list = @explode(';', trim(file_get_contents(F('settings.txt'))));

    foreach ($set_read_list as $setting_option) {
      $set_name = @explode(':', $setting_option);

      if (count($set_name) > 2) { //we have a settings 'list'
        $liststart = explode(':', $setting_option,3);
        $listopt = explode(':', $liststart[2]);
        $tired = count($listopt);
        for ($i=0; $i < $tired; $i=$i+2) {
          $tired2 = $i+1;
          $px_Setopts[$liststart[0]][$listopt[$i]] = $listopt[$tired2];
        }
      } else {
         $px_Setopts[$set_name[0]] = $set_name[1];
      }
    }
    if($px_Setopts['version']<>PX_VER_SETTINGS) {
      $px_Setopts['version'] = PX_VER_SETTINGS;
      unlink('sqlite_check.txt');
    }
  } else {
    $px_Setopts = CreateDefaultSettings();
  }
  return $px_Setopts;
}

// ------------------------------------------------
// FV_Server - set farmville server
//-------------------------------------------------
function FV_Server($set)
{
  if ($set == "fbdotcom")
    {
      unlink('farmclient.txt');
      unlink('farmserver.txt');
      echo "Restart Bot to change server<br/>";
    }
  else if ($set == "fvdotcom")
    {
      echo "Restart Bot to change server<br/> If you have problems delete farmserver.txt and farmclient.txt to go back to default<br/>";
      $fv_client = 'www.farmville.com/index.php';
      $fv_server = 'www.farmville.com;http://www.farmville.com/flashservices/gateway.php';

      file_put_contents('farmclient.txt',$fv_client);
      file_put_contents('farmserver.txt',$fv_server);
    }
}


//------------------------------------------------------------------------------
// settings_form
//------------------------------------------------------------------------------
 function Settings_form()
  {
  global $this_plugin;
  global $userId;
  //print_r($_GET);

  if (isset($_GET['save_action']))
   {

   $seed_list = @explode(';', trim(file_get_contents(F('seed.txt'))));
   if ((count($seed_list) == 1) && empty($seed_list[0]))
     $seed_list = array();
   $changed_seed_list = false;



   //FarmFIX

   $my_farm_is_fucked_up = @$_GET['farmfix'];

   if ($my_farm_is_fucked_up != 1) {
       if (file_exists('farmfix.txt')) {
          unlink('farmfix.txt');
       }
    }
   else if ($my_farm_is_fucked_up == 1) {
       if (!file_exists('farmfix.txt')) {
         file_put_contents('farmfix.txt','.');
       }
    }

   //parser/php kill
   $auto_kill_parser = @$_GET['auto_kill_parser'];

   if ($auto_kill_parser != 1) {
       if (file_exists('auto_kill_parser.txt')) { unlink('auto_kill_parser.txt'); }
    }
   else if ($auto_kill_parser == 1) {
       if (!file_exists('auto_kill_parser.txt')) {
         file_put_contents('auto_kill_parser.txt','.');
       }
    }
   //parser kill

   $changed_settings = false;

   $px_Setopts = LoadSavedSettings();

   $px_Setopts['e_harvest'] = @$_GET['harvest'];
   $px_Setopts['e_biplane'] = @$_GET['e_biplane'];
   $px_Setopts['e_h_animal'] = @$_GET['harvest_animals'];
   $px_Setopts['e_h_tree'] = @$_GET['harvest_trees'];
   $px_Setopts['e_h_arborist'] = @$_GET['harvest_arborist'];
   $px_Setopts['e_h_arborist_at'] = @$_GET['harvest_arborist_at'];
   $px_Setopts['e_h_arborist_min'] = @$_GET['harvest_arborist_min'];
   $px_Setopts['e_seed'] = @$_GET['planting'];
   $px_Setopts['e_seed_keep'] = @$_GET['seed_keep'];
   $px_Setopts['e_hoe'] = @$_GET['hoe'];
   $px_Setopts['e_combine'] = @$_GET['combine'];
   $px_Setopts['H_Animal']['cow'] = @$_GET['harvest_cow'];
   $px_Setopts['H_Animal']['turkeybaby'] = @$_GET['harvest_turkeybaby'];
   $px_Setopts['H_Animal']['turkeybabybourbon'] = @$_GET['harvest_turkeybabybourbon'];
   $px_Setopts['H_Animal']['uglyduck'] = @$_GET['harvest_uglyduck'];
   $px_Setopts['H_Animal']['chicken'] = @$_GET['harvest_chicken'];
   $px_Setopts['H_Animal']['cat_'] = @$_GET['harvest_cats'];
   $px_Setopts['e_h_farmhands'] = @$_GET['harvest_farmhands'];
   $px_Setopts['e_h_farmhands_at'] = @$_GET['harvest_farmhands_at'];
   $px_Setopts['e_h_farmhands_min'] = @$_GET['harvest_farmhands_min'];
   $px_Setopts['e_h_building'] = @$_GET['harvest_building'];
   $px_Setopts['e_h_building_coop'] = @$_GET['harvest_building_coop'];
   $px_Setopts['e_h_building_dairy'] = @$_GET['harvest_building_dairy'];
   $px_Setopts['e_h_building_horse'] = @$_GET['harvest_building_horse'];
   $px_Setopts['e_h_building_nursery'] = @$_GET['harvest_building_nursery'];
   $px_Setopts['e_h_building_bees'] = @$_GET['harvest_building_bees'];
   $px_Setopts['e_h_building_pigs'] = @$_GET['harvest_building_pigs'];
   $px_Setopts['e_h_building_hauntedhouse'] = @$_GET['harvest_building_hauntedhouse'];
   $px_Setopts['e_h_building_trough'] = @$_GET['harvest_building_trough'];
   $px_Setopts['e_h_building_orchard'] = @$_GET['harvest_building_orchard'];
   $px_Setopts['e_h_building_turkeyroost'] = @$_GET['harvest_building_turkeyroost'];
   $px_Setopts['e_h_building_wworkshop'] = @$_GET['harvest_building_wworkshop'];
   $px_Setopts['e_h_building_snowman'] = @$_GET['harvest_building_snowman'];
   $px_Setopts['e_h_building_duckpond'] = @$_GET['harvest_building_duckpond'];
   $px_Setopts['e_h_building_ccastle'] = @$_GET['harvest_building_ccastle'];
   $px_Setopts['e_h_building_lcottage'] = @$_GET['harvest_building_lcottage'];
   $px_Setopts['e_gzip'] = @$_GET['gzip'];
   $px_Setopts['bot_speed'] = @$_GET['bot_speed'];
   $px_Setopts['fuel_plow'] = @$_GET['fuel_plow'];
   $px_Setopts['fuel_place'] = @$_GET['fuel_place'];
   $px_Setopts['fuel_harvest'] = @$_GET['fuel_harvest'];
   $px_Setopts['fuel_combine'] = @$_GET['fuel_combine'];
   $px_Setopts['lonlyanimals'] = @$_GET['lonlyanimals'];
   $px_Setopts['wanderinganimals'] = @$_GET['wanderinganimals'];
   $px_Setopts['acceptneighborhelp'] = @$_GET['acceptneighborhelp'];
   $px_Setopts['acceptgifts'] = @$_GET['acceptgifts'];
   $px_Setopts['acceptgifts_sendback'] = @$_GET['acceptgifts_sendback'];
   $px_Setopts['acceptgifts_twice'] = @$_GET['acceptgifts_twice'];
   $px_Setopts['acceptgifts_num'] = @$_GET['acceptgifts_num'];
   $px_Setopts['sendgifts'] = @$_GET['sendgifts'];
   $px_Setopts['e_harvest_spec'] = @$_GET['harvest_spec'];
   $px_Setopts['spec_crop'] = @$_GET['spec_crop'];
   $px_Setopts['spec_crop_quantity'] = @$_GET['spec_crop_quantity'];
   if($px_Setopts['e_h_animal']=='') {
     $px_Setopts['e_h_farmhands'] = '';
     $px_Setopts['H_Animal']['cow'] = '';
     $px_Setopts['H_Animal']['turkeybaby'] = '';
     $px_Setopts['H_Animal']['turkeybabybourbon'] = '';
     $px_Setopts['H_Animal']['uglyduck'] = '';
     $px_Setopts['H_Animal']['chicken'] = '';
     $px_Setopts['H_Animal']['cat_'] = '';
   }
   if($px_Setopts['e_h_building']=='') {
     $px_Setopts['e_h_building_coop'] = '';
     $px_Setopts['e_h_building_dairy'] = '';
     $px_Setopts['e_h_building_horse'] = '';
     $px_Setopts['e_h_building_nursery'] = '';
     $px_Setopts['e_h_building_bees'] = '';
     $px_Setopts['e_h_building_pigs'] = '';
     $px_Setopts['e_h_building_hauntedhouse'] = '';
     $px_Setopts['e_h_building_trough'] = '';
     $px_Setopts['e_h_building_orchard'] = '';
     $px_Setopts['e_h_building_turkeyroost'] = '';
     $px_Setopts['e_h_building_wworkshop'] = '';
     $px_Setopts['e_h_building_snowman'] = '';
     $px_Setopts['e_h_building_duckpond'] = '';
     $px_Setopts['e_h_building_ccastle'] = '';
     $px_Setopts['e_h_building_lcottage'] = '';
   }
   if($px_Setopts['e_combine']=='1') {
     $px_Setopts['e_seed'] = '';
   }

    if(@$_GET['nr_parser']==1) {
        if (!file_exists('notrun_parser.txt')) {
            file_put_contents('notrun_parser.txt','.');
        }
    } else {
        if (file_exists('notrun_parser.txt')) {
            unlink('notrun_parser.txt');
        }
    }
    if(@$_GET['nr_parser_a']==1) {
        if (!file_exists('notrun_parser_'.$userId.'.txt')) {
            file_put_contents('notrun_parser_'.$userId.'.txt','.');
        }
    } else {
        if (file_exists('notrun_parser_'.$userId.'.txt')) {
            unlink('notrun_parser_'.$userId.'.txt');
        }
    }
    if(@strlen($_GET['timezone'])>1) {
      file_put_contents('timezone.txt',@$_GET['timezone']);
    }

    $dir = 'plugins';
    $dh = opendir($dir);

    if ($dh) {
        while (($file = readdir($dh)) !== false) {
            if (is_dir($dir . '/' . $file)) {
                if ($file != '.' && $file != '..') {
                    if(@$_GET['nr_p_'.$file]==1) {
                        if (!file_exists('notrun_plugin_'.$file.'.txt')) {
                            file_put_contents('notrun_plugin_'.$file.'.txt','.');
                        }
                    } else {
                        if (file_exists('notrun_plugin_'.$file.'.txt')) {
                            unlink('notrun_plugin_'.$file.'.txt');
                        }
                    }
                    if(@$_GET['nr_p_'.$file.'_a']==1) {
                        if (!file_exists('notrun_plugin_'.$file.'_'.$userId.'.txt')) {
                            file_put_contents('notrun_plugin_'.$file.'_'.$userId.'.txt','.');
                        }
                    } else {
                        if (file_exists('notrun_plugin_'.$file.'_'.$userId.'.txt')) {
                            unlink('notrun_plugin_'.$file.'_'.$userId.'.txt');
                        }
                    }
                }
            }
        }
        closedir($dh);
    }

  if (@$_GET['farm_server'] != 1)
  {
    if (@$px_Setopts['farm_server'] == 1)
      {
        FV_Server('fbdotcom');
        $px_Setopts['farm_server'] = 0;
      }
  }
  else if (@$_GET['farm_server'] == 1)
  {
    if ($px_Setopts['farm_server'] == 0)
      {
        FV_Server('fvdotcom');
        $px_Setopts['farm_server'] = 1;
      }
  }

   $changed_settings = true;

   if (isset($_GET['clear_planting']))
    {
    $seed_list = array();
    $changed_seed_list = true;
    }

   if (isset($_GET['add_plant_s']))
    {
    $defaultseed = @$_GET['seed_default'];
    $plant_count = @$_GET['plant_count'];
    $item = @$_GET['plant_list'];

    if ($defaultseed) { $seed_list[] = "$item:Default"; }
    else { $seed_list[] = "$item:$plant_count"; }
    $changed_seed_list = true;

    }

   if (isset($_GET['del_plant']))
    {
    if (!is_array(@$_GET['seed_list']))
     {
     $del_plants = array();
     $del_plants[] = @$_GET['seed_list'];
     }
    else
     $del_plants = @$_GET['seed_list'];

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

   if ($changed_seed_list) {
     file_put_contents(F('seed.txt'),implode(';', $seed_list));
   }

   file_put_contents(F('sendgifts.txt'),trim($_GET['sendgiftsto']));
   if ($changed_settings) {
    SaveSettings($px_Setopts);
   }
  }

  ?>
  <html>
   <head>
    <style type="text/css">body {background-color:#808080;}select.seed_list {color:white;border: 2px solid #365A37;background-color:#808080;}</style>

    <script language="javascript">
      function ShowAddForm() {
        add_plant_div.style.display = "";
        ChangeMasteryInfo()
      }
      function HideAddForm() { add_plant_div.style.display = "none"; }
      function Submit() { main_form.submit(); }
      function ChangeMasteryInfo() {
        var mLIST=document.getElementById("plant_list");
        document.getElementById("mastery_info").innerHTML = mLIST.options[mLIST.selectedIndex].mastery;
        document.getElementById("plant_count").value = mLIST.options[mLIST.selectedIndex].amount;
      }
    </script>
   </head>
   <body>

  <?php
   $seed_list = @explode(';', trim(file_get_contents(F('seed.txt'))));

        foreach($seed_list as $one_seed_string) {
            $one_seed_array = @explode(':', $one_seed_string);
            if($one_seed_array[1]=='Default') {
                $seed_default=$one_seed_array[0];
            } else {
                if($last_seed==$one_seed_array[0]) {
                    $last_seed_string=array_pop($seed_list_new);
                    $last_seed_array = @explode(':', $last_seed_string);
                    $seed_list_new[]=$one_seed_array[0].':'.($one_seed_array[1]+$last_seed_array[1]);
                } else {
                    $seed_list_new[]=$one_seed_string;
                }
                $last_seed=$one_seed_array[0];
            }
        }
        if(isset($seed_default)) $seed_list_new[]=$seed_default.':Default';
        $seed_list=$seed_list_new;
        unset($seed_list_new);

   $mastercount = @unserialize(file_get_contents(F('cropmastery.txt')));

   $px_Setopts = LoadSavedSettings();


   //FarmFIX
   if (file_exists('farmfix.txt')) { $my_farm_is_fucked_up = 1; }
   else { $my_farm_is_fucked_up = 0; }
   //FarmFIX

   if (file_exists('auto_kill_parser.txt')) { $auto_kill_parser = 1; }
   else { $auto_kill_parser = 0; }

   echo '<form action="'.settings_URL.'" id="main_form">';
   echo '<input type="hidden" name="save_action" value="1" />';
   echo '<tr><td colspan=3><center><input type="button" onclick="Submit()" name="save" style="width:200px;" value="Save Changes"/></center></td></tr>';
   echo '<table><tr>';
   echo '<td valign="top">';
   echo '<span style="color:white; background-color:blue;">You are using settings v'.PX_VER_SETTINGS.' and parser v'.PX_VER_PARSER.' </span><br/><br/>';
   echo '<div><nobr>Server:  ';
   echo '(<input type="radio" name="farm_server" value="0" '.($px_Setopts['farm_server']?'':'checked').' title="facebook.com or custom url in file"/>Facebook.com)  ';
   echo '(<input type="radio" name="farm_server" value="1" '.($px_Setopts['farm_server']?'checked':'').' title="farmville.com" />Farmville.com)</div><br/>';
   echo '</nobr><div>Timezone: <select name="timezone" title="your time zone">';
     $timezonefile = './timezone.txt';
     if (file_exists($timezonefile)) {
       $timezone = trim(file_get_contents($timezonefile));
     } else {
       $timezone = 'America/Los_Angeles';
     }
     echo '<option selected value=',$timezone,'>',$timezone,'</option>';
     $timezonelist =  timezone_identifiers_list();
     foreach ($timezonelist as $data) {
       echo '<option value=',$data,'>',$data,'</option>';
     }
   echo '</select></div><br/>';
   echo '<div><input type="checkbox" name="auto_kill_parser" value="1" '.($auto_kill_parser?'checked':'').' title="Automatically kill stuck php.exe?" /> Automatically kill php.exe? (Recommended)<br/></div>';
   echo '<div><input type="checkbox" name="farmfix" value="1" '.($my_farm_is_fucked_up?'checked':'').' title="This might let your farm load if its full of superplots. TRY IT. Otherwise this isnt needed" /> Split object file (Might help if farm wont load.<br/>Not if getting error 500)</div>';
   echo '<div><input type="checkbox" name="gzip" value="1" '.($px_Setopts['e_gzip']?'checked':'').' title="if this checkbox is selected, we will use gzip compression. " /> Enable gzip (Recommended)<br/>If you get Error 500 after enabling this disable it.<br/>Otherwise this will speed up loading</div>';
   echo '<br/>';



   echo '<div>';
   echo '<small>Bot/Account</small><br/>';
   echo '<input type="checkbox" name="nr_parser" value="1" '.(file_exists('notrun_parser.txt')?'checked':'').'>/';
   echo '<input type="checkbox" name="nr_parser_a" value="1" '.(file_exists('notrun_parser_'.$userId.'.txt')?'checked':'').'>';
   echo '&nbsp;Pause Bot (at start of next cycle)<br/>';
   echo '</div><br/>';
   echo 'Switch off following Plugins (on next Cycle)<br/>';
   echo '&nbsp;&nbsp;<small>Bot/Account</small><br/>';
    $dir = 'plugins';
    $dh = opendir($dir);

    if ($dh) {
        while (($file = readdir($dh)) !== false) {
            if (is_dir($dir . '/' . $file)) {
                if ($file != '.' && $file != '..' && $file != 'Settings') {
                    $vContent=explode("\n",file_get_contents($dir . '/' . $file . '/info.txt'));
                    echo '(';
                    echo '<input type="checkbox" name="nr_p_',$file,'" value="1" '.(file_exists('notrun_plugin_'.$file.'.txt')?'checked':'').'>/';
                    echo '<input type="checkbox" name="nr_p_',$file,'_a" value="1" '.(file_exists('notrun_plugin_'.$file.'_'.$userId.'.txt')?'checked':'').'>';
                    echo '&nbsp;',$file,' v',$vContent[1],'<!-- by ',$vContent[2],'-->)<br/>';
                }
            }
        }
        closedir($dh);
    }
   echo '</div>';
   echo '<br><br><br><center><b>Do you like my work?<br>';
   echo '<a target="_blank" style="text-decoration:none; color:black;" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7G9AYZSR99M5C"><img src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" alt="Donate"></a><br>';
   echo 'Thanks. ralphm2004</center></b>';

   echo '</td><td>&nbsp;&nbsp;&nbsp;</td><td valign="top">';
   echo '<div>';
   echo '(<input type="checkbox" name="harvest" value="1" '.($px_Setopts['e_harvest']?'checked':'').' title="if this checkbox is selected, the bot will automatically harvest crops from plots of land" /> Harvest Crops)  ';
   echo '(<input type="checkbox" name="hoe" value="1" '.($px_Setopts['e_hoe']?'checked':'').' title="if this checkbox is selected, the bot will automatically plow withered and fallow land" /> Plow)  ';
   echo '(<input type="checkbox" name="planting" value="1" '.($px_Setopts['e_seed']?'checked':'').' title="if this checkbox is selected, the bot will automatically plant crops" /> Plant) ';
   echo '(<input type="checkbox" name="combine" value="1" '.($px_Setopts['e_combine']?'checked':'').' title="if this checkbox is selected, the bot will automatically harvest/plow/plant crops" /> Combine) <br/><hr>';
   echo '(<input type="checkbox" name="harvest_spec" value="1" '.($px_Setopts['e_harvest_spec']?'checked':'').' title="if this checkbox is selected, the bot will harvest specific crops only from plots of land" /> Harvest Specific Crops)   ';
   echo ':   <input type="text" name="spec_crop_quantity" size="3" value="'.$px_Setopts['spec_crop_quantity'].'">   ';

   echo ':  <select class="" name="spec_crop"/>';
   $vSeeds=Units_GetByType('seed');

   $arrHarvestCropOpt = array();
   foreach($vSeeds as $unit) {
      if ($unit['type'] == 'seed') {
         $arrHarvestCropOpt[] = htmlentities($unit['name']);
      }
   }
   asort($arrHarvestCropOpt);
   foreach($arrHarvestCropOpt as $opt) {
      echo '   <option value="'.$opt.'"';
      if ($px_Setopts['spec_crop'] == $opt) echo ' selected';
      echo '>'.$opt.'</option>';
   }
   echo '  </select><hr><br/>';

   echo '</div>';
   echo '<div>Seed list:';
   echo '<input type="button" name="add_plant" value="+" style="width:32px; height:22px; margin-left:15px;" onclick="ShowAddForm()" title="add plants"/>';
   echo '<input type="submit" name="del_plant" value="-" style="width:32px; height:22px; margin-left:25px;" title="delete selected"/>';
   echo '<input type="submit" name="clear_planting" value="Clear" style="width:45px; height:22px; margin-left:35px;" /><br/>';


   echo '<select class="seed_list" name="seed_list" multiple style="width:250px; height: 150px;" />';

   $i = 0;
   $px_seeds_found_default = 0;
   foreach ($seed_list as $seed_item)
    {
     $px_seeds_list = @explode(':', $seed_item);

     if ($px_seeds_list[0])
     echo '   <option value="'.$i.'">'.htmlentities($px_seeds_list[0]).' ('.$px_seeds_list[1].')</option>';
     $i++;
    if (@isset($px_seeds_list[1]) && $px_seeds_list[1] == "Default") { $px_seeds_found_default = 1; }

    }

   echo '</select><br/>';
   echo '(<input type="checkbox" name="seed_keep" value="1" '.($px_Setopts['e_seed_keep']?'checked':'').' title="if this checkbox is selected, the bot will leave the seed in the list" /> keep seed in list after seeding) ';
   echo '</div><br/>';
   echo '<div style="display: none; background-color:white; text-align: center; border: solid 1 blue; position: absolute; margin: 0 0 0 0; padding: 8px;" id="add_plant_div">';
   echo '  <select name="plant_list" style="font-family: Courier" onchange="ChangeMasteryInfo()">';

  #$units = @unserialize(file_get_contents(('units.txt')));
  $vSeeds=Units_GetByType('seed');

  foreach($vSeeds as $unit) {

      $px_mastchk_code = $unit['code'];

      if (@$mastercount[$px_mastchk_code] && @$unit['masterymax']) {
          if ($mastercount[$px_mastchk_code] >= $unit['masterymax']) {
              $vMasteryInfo='Mastered!';
              $vAmount=0;
              $vSelectName.='';
              $vCntPlanted='--- Ma';
              $vCntMatery='stered';
              $vCntSeed='! --- ';
              $vList=2;
          } else {
              $vMasteryInfo='Mastery: '.$mastercount[$px_mastchk_code].'/'.$unit['masterymax'].' Plant: '. ($unit['masterymax'] - $mastercount[$px_mastchk_code]). ' more!';
              $vAmount=($unit['masterymax'] - $mastercount[$px_mastchk_code]);
              $vSelectName.='';
              $vCntPlanted=$mastercount[$px_mastchk_code].' ';
              while(strlen($vCntPlanted)<6) $vCntPlanted=' '.$vCntPlanted;

              $vCntMatery=$unit['masterymax'].' ';
              while(strlen($vCntMatery)<6) $vCntMatery=' '.$vCntMatery;
              $vCntSeed=$vAmount.' ';
              while(strlen($vCntSeed)<6) $vCntSeed=' '.$vCntSeed;
              $vList=1;
          }

      } else if (@$unit['masterymax']) {
          $vMasteryInfo='Mastery: 0/'.$unit['masterymax'].'!';
          $vAmount=$unit['masterymax'];
          $vSelectName.='';
          $vCntPlanted='    0 ';
          $vCntMatery=$unit['masterymax'].' ';
          while(strlen($vCntMatery)<6) $vCntMatery=' '.$vCntMatery;
          $vCntSeed=$vAmount.' ';
          while(strlen($vCntSeed)<6) $vCntSeed=' '.$vCntSeed;
          $vList=1;
      } else {
          $vMasteryInfo='No Mastery!';
          $vAmount=0;
          $vSelectName.='';
          $vCntPlanted='-- No ';
          $vCntMatery='Master';
          $vCntSeed='y! -- ';
          $vList=3;
      }

      if ($unit['growTime'] < 1) { $vGrowTime = round($unit['growTime']*23).'h '; }
      else if ($unit['growTime'] >= 1) { $vGrowTime = round($unit['growTime']).'d '; }
      while(strlen($vGrowTime)<4) $vGrowTime=' '.$vGrowTime;

      $vRealName=isset($unit['realname'])?($unit['name']=='blueberryorganic'?'Organic Blueberries':($unit['realname'].' ('.$unit['name'].')')):$unit['name'];

      $vSelectName=str_replace(' ','&nbsp;',$vGrowTime.$vCntPlanted.$vCntMatery.$vCntSeed.$vRealName);

      $vOptionArray[$vList][$vRealName]='<option value="'.htmlentities($unit['name']).'" mastery="'.$vMasteryInfo.'" amount="'.$vAmount.'">'.$vSelectName.'</option>';
  }
  echo '<option value="" mastery="" amount="">',str_replace(' ','&nbsp;','GrT Seed. Mast. Plant Name'),'</option>';
  asort($vOptionArray[1]);
  asort($vOptionArray[2]);
  asort($vOptionArray[3]);
  foreach($vOptionArray[1] as $vOption) echo $vOption;
  foreach($vOptionArray[2] as $vOption) echo $vOption;
  foreach($vOptionArray[3] as $vOption) echo $vOption;
   echo '  </select>';
   echo '<br/>';
   echo '<div style="color:#FF0000" id="mastery_info"/></div><br/>';
   echo 'Plant: <input type="text" name="plant_count" size="3" value=""/><br/><br/>';

   if (!$px_seeds_found_default)
     echo '<input type="checkbox" name="seed_default" value="1" title="Plant this seed when no other seed is listed" /> Default Seed<br/><br/>';

   echo '<input type="submit" name="add_plant_s" value="Add" style="width: 75px;"/>';
   echo '&nbsp;&nbsp;<input type="button" value="Cancel" onclick="HideAddForm()" style="width: 75px;"/>';
   echo '</div>';


   echo '<div>';
   echo '<nobr>[<input type="checkbox" name="acceptneighborhelp" value="1" '.($px_Setopts['acceptneighborhelp']?'checked':'').' title="if the checkbox is selected, the bot will accept neighbors help." /> Accept Neighbors Help</nobr><br/>';
   echo '<nobr>(<input type="checkbox" name="acceptgifts" value="1" '.($px_Setopts['acceptgifts']?'checked':'').' title="if the checkbox is selected, the bot will accept gifts/requests." /> Accept Gifts/Requests)</nobr>&nbsp; ';
   echo '<nobr>(<select name="acceptgifts_num"><option value="'.$px_Setopts['acceptgifts_num'].'" selected="selected">'.$px_Setopts['acceptgifts_num'].'</option><option value="1">1</option><option value="5">5</option><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option><option value="200">200</option></select> at once</nobr>)&nbsp; ';
   echo '<nobr>(<input type="checkbox" name="acceptgifts_twice" value="1" '.($px_Setopts['acceptgifts_twice']?'checked':'').' title="if the checkbox is selected, the bot will try to accept the gift twice." /> Try to accept the Gift twice)</nobr>&nbsp; ';
   echo '<nobr>(<input type="checkbox" name="acceptgifts_sendback" value="1" '.($px_Setopts['acceptgifts_sendback']?'checked':'').' title="if the checkbox is selected, the bot will send thankyou-gifts." /> Send ThankYou-Gift)]</nobr><br><br>';
   echo '<nobr>(<input type="checkbox" name="sendgifts" value="1" '.($px_Setopts['sendgifts']?'checked':'').' title="if the checkbox is selected, the bot will sendgifts gifts." /> Send Gifts) &nbsp;&nbsp; <i>(FBID;giftcode per line)</i></nobr>';
   echo '<br/>';
   echo '<textarea cols=50 rows=10 name="sendgiftsto">',@trim(file_get_contents(F('sendgifts.txt'))),'</textarea>';
   echo '<br/><br/>';

   echo '[<nobr><input type="checkbox" name="harvest_trees" value="1" '.($px_Setopts['e_h_tree']?'checked':'').' title="if the checkbox is selected, the bot will harvest from trees" /> Harvest Trees</nobr><br/>';
   echo '<nobr>(<input type="checkbox" name="harvest_arborist" value="1" '.($px_Setopts['e_h_arborist']?'checked':'').' title="if the checkbox is selected, the bot will harvest from trees with arborist" /> with Arborist, ';
   echo 'if more then <select name="harvest_arborist_at"><option value="'.$px_Setopts['e_h_arborist_at'].'" selected="selected">'.$px_Setopts['e_h_arborist_at'].'%</option><option value="1">1%</option><option value="25">25%</option><option value="50">50%</option><option value="75">75%</option><option value="95%">95%</option><option value="100%">100%</option></select> ready)</nobr>&nbsp; ';
   echo '<nobr>(but leave <input type="text" name="harvest_arborist_min" size="3" value="'.$px_Setopts['e_h_arborist_min'].'"> arborist in giftbox)]</nobr><br/>';

   echo '<br/>';
   echo '<nobr>[<input type="checkbox" name="harvest_building" value="1" '.($px_Setopts['e_h_building']?'checked':'').' title="if the checkbox is selected, the bot will get products from buildings" /> Harvest Buildings</nobr><br/>';
   echo '<nobr>(<input type="checkbox" name="harvest_building_coop" value="1" '.($px_Setopts['e_h_building_coop']?'checked':'').' title="if the checkbox is selected, the bot will get products from Chicken Coops" /> Chicken Coop)</nobr> ';
   echo '<nobr>(<input type="checkbox" name="harvest_building_dairy" value="1" '.($px_Setopts['e_h_building_dairy']?'checked':'').' title="if the checkbox is selected, the bot will get products from Dairy Farms" /> Dairy Farm)</nobr> ';
   echo '<nobr>(<input type="checkbox" name="harvest_building_horse" value="1" '.($px_Setopts['e_h_building_horse']?'checked':'').' title="if the checkbox is selected, the bot will get products from Horse Stable" /> Horse Stable)</nobr> ';
   echo '<nobr>(<input type="checkbox" name="harvest_building_nursery" value="1" '.($px_Setopts['e_h_building_nursery']?'checked':'').' title="if the checkbox is selected, the bot will get products from Nursery Barn" /> Nursery Barn)</nobr> ';
   echo '<nobr>(<input type="checkbox" name="harvest_building_bees" value="1" '.($px_Setopts['e_h_building_bees']?'checked':'').' title="if the checkbox is selected, the bot will get products from the Beehive Building" /> Beehive Building)</nobr> ';
   echo '<nobr>(<input type="checkbox" name="harvest_building_pigs" value="1" '.($px_Setopts['e_h_building_pigs']?'checked':'').' title="if the checkbox is selected, the bot will get products from the Pigpen Building" /> Pigpen Building)</nobr> ';
   echo '<nobr>(<input type="checkbox" name="harvest_building_hauntedhouse" value="1" '.($px_Setopts['e_h_building_hauntedhouse']?'checked':'').' title="if the checkbox is selected, the bot will get candy from the Haunted House" /> Haunted House)</nobr> ';
   echo '<nobr>(<input type="checkbox" name="harvest_building_trough" value="1" '.($px_Setopts['e_h_building_trough']?'checked':'').' title="if the checkbox is selected, the bot will harvest the Animal Trough" /> Animal Trough)</nobr> ';
   echo '<nobr>(<input type="checkbox" name="harvest_building_orchard" value="1" '.($px_Setopts['e_h_building_orchard']?'checked':'').' title="if the checkbox is selected, the bot will harvest the Orchard" /> Orchard)</nobr> ';
   echo '<nobr>(<input type="checkbox" name="harvest_building_turkeyroost" value="1" '.($px_Setopts['e_h_building_turkeyroost']?'checked':'').' title="if the checkbox is selected, the bot will harvest the Turkey Roost" /> Turkey Roost)</nobr> ';
   echo '<nobr>(<input type="checkbox" name="harvest_building_wworkshop" value="1" '.($px_Setopts['e_h_building_wworkshop']?'checked':'').' title="if the checkbox is selected, the bot will get items from the Winter Workshop" /> Winter Workshop)</nobr> ';
   echo '<nobr>(<input type="checkbox" name="harvest_building_snowman" value="1" '.($px_Setopts['e_h_building_snowman']?'checked':'').' title="if the checkbox is selected, the bot will get items from the Snowman" /> Snowman)</nobr> ';
   echo '<nobr>(<input type="checkbox" name="harvest_building_duckpond" value="1" '.($px_Setopts['e_h_building_duckpond']?'checked':'').' title="if the checkbox is selected, the bot will get items from the DuckPond" /> Duck Pond)</nobr> ';
   echo '<nobr>(<input type="checkbox" name="harvest_building_ccastle" value="1" '.($px_Setopts['e_h_building_ccastle']?'checked':'').' title="if the checkbox is selected, the bot will get items from the Cupids Castle" /> Cupids Castle)]</nobr> ';
   echo '<nobr>(<input type="checkbox" name="harvest_building_lcottage" value="1" '.($px_Setopts['e_h_building_lcottage']?'checked':'').' title="if the checkbox is selected, the bot will get items from the Leprechaun Cottage" /> Leprechaun Cottage)]</nobr> ';
   echo '<br/><br/>';
   echo '<nobr>[(<input type="checkbox" name="lonlyanimals" value="1" '.($px_Setopts['lonlyanimals']?'checked':'').' title="if the checkbox is selected, the bot will check for lonlyanimals. Use MyRewards to get them." /> Check for LonlyAnimals)</nobr> ';
   echo '<nobr>(<input type="checkbox" name="wanderinganimals" value="1" '.($px_Setopts['wanderinganimals']?'checked':'').' title="if the checkbox is selected, the bot will check for wanderinganimals. Use MyRewards to get them." /> Check for WanderingAnimals)]</nobr>';

   echo '<br/><br/>';
   echo '<nobr>[<input type="checkbox" name="harvest_animals" value="1" '.($px_Setopts['e_h_animal']?'checked':'').' title="if the checkbox is selected, the bot will get products from livestock" /> Harvest Animals</nobr><br/>';
   echo '<nobr>(<input type="checkbox" name="harvest_farmhands" value="1" '.($px_Setopts['e_h_farmhands']?'checked':'').' title="if the checkbox is selected, the bot will harvest from animals with farmhands" /> with Farmhands, ';
   echo 'if more then <select name="harvest_farmhands_at"><option value="'.$px_Setopts['e_h_farmhands_at'].'" selected="selected">'.$px_Setopts['e_h_farmhands_at'].'%</option><option value="1">1%</option><option value="25">25%</option><option value="50">50%</option><option value="75">75%</option><option value="95%">95%</option><option value="100%">100%</option></select> ready)</nobr>&nbsp; ';
   echo '<nobr>(but leave <input type="text" name="harvest_farmhands_min" size="3" value="'.$px_Setopts['e_h_farmhands_min'].'"> farmhands in giftbox)]</nobr><br/>';
   echo '<br/>';

   echo 'Collect from: ';
   echo '<nobr>(<input type="checkbox" name="harvest_cats" value="1" '.($px_Setopts['H_Animal']['cat_']?'checked':'').' title="if the checkbox is selected, the bot will get products from cats" /> Cats)</nobr> ';
   echo '<nobr>(<input type="checkbox" name="harvest_chicken" value="1" '.($px_Setopts['H_Animal']['chicken']?'checked':'').' title="if the checkbox is selected, the bot will get products from chickens" /> Chickens)</nobr> ';
   echo '<nobr>(<input type="checkbox" name="harvest_cow" value="1" '.($px_Setopts['H_Animal']['cow']?'checked':'').' title="if the checkbox is selected, the bot will get products from cows" /> Cows)</nobr> ';
   echo '<br/>Transform: ';
   echo '<nobr>(<input type="checkbox" name="harvest_uglyduck" value="1" '.($px_Setopts['H_Animal']['uglyduck']?'checked':'').' title="if the checkbox is selected, the bot will get products from ugly duck" /> Ugly Duck)</nobr> ';
   #echo '<nobr>(<input type="checkbox" name="harvest_turkeybaby" value="1" '.($px_Setopts['H_Animal']['turkeybaby']?'checked':'').' title="if the checkbox is selected, the bot will get products from baby turkey" /> Baby Turkey)</nobr> ';
   echo '<nobr>(<input type="checkbox" name="harvest_turkeybabybourbon" value="1" '.($px_Setopts['H_Animal']['turkeybabybourbon']?'checked':'').' title="if the checkbox is selected, the bot will get products from baby turkey bourbon" /> Baby Turkey Bourbon)</nobr>] ';

   echo '<br/><br/>';

   echo 'Bot Speed: ';
   echo '<select name="bot_speed"><option value="'.$px_Setopts['bot_speed'].'" selected="selected">'.$px_Setopts['bot_speed'].'X</option><option value="1">1X</option><option value="2">2X</option><option value="3">3X</option><option value="4">4X</option><option value="5">5X</option><option value="6">6X</option><option value="7">7X</option><option value="8">8X</option></select><br/><br/>';

   echo '<table border="0" cellspacing="0" cellpadding="0"><tr><td>';
   echo '<nobr>Use Tractor? <input type="text" name="fuel_plow" size="3" value="'.$px_Setopts['fuel_plow'].'"></nobr><br/>';
   echo '<nobr>Use Seeder? <input type="text" name="fuel_place" size="3" value="'.$px_Setopts['fuel_place'].'"></nobr><br/>';
   echo '<nobr>Use Harvester? <input type="text" name="fuel_harvest" size="3" value="'.$px_Setopts['fuel_harvest'].'"></nobr>';
   echo '<nobr>Use Combine? <input type="text" name="fuel_combine" size="3" value="'.$px_Setopts['fuel_combine'].'"></nobr>';
   echo '</td><td>&nbsp;&nbsp;&nbsp;</td><td>0 = Disabled.<br/>&gt; 0 how many plow/seed/harvest<br/>You NEED fuel and a FULL UPGRADED tractor/seeder/harvester/combine or you will get errors.</td></tr></table>';
   echo '<br/>';

   echo 'Use Biplane? <input type=checkbox name=e_biplane value=1 '.($px_Setopts['e_biplane']?'checked':'').' /> &nbsp;&nbsp;';
   echo '<font color=red>THIS WILL COST CASH!!<br/>Only the first is free!! You NEED the Plane and Cash or you will get errors.</font><br/>Instantgrow will be applied befor harvesting crops!<br/><br/>';
   echo '</div>';
  ?>
     </td></tr>
     <tr>
       <td colspan=3><center><input type="button" onclick="Submit()" name="save" style="width:200px;" value="Save Changes"/></center></td>
     </tr>
     </form>
    </table>
   </body>
  </html>
  <?php
  }
?>