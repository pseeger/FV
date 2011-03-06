<?php

function fvNeighbors_form(){
  global $this_plugin;
  $fsM = new fvNeighbors('formload');
  $gridHandler = new GridServerHandler();
  $gtype = getParameter('exportType');
  
  if (isset($_POST['save'])) {
    $json = new Services_JSON();
    $value = $json->decode($_POST['_gt_json']);
    foreach ($value['deletedRecords'] as $dr){
      $fsM->DeleteNeigh($dr['fbid']);      
    }
    echo "{success : true,exception:''}";
    return;
  }
  
  if (@$_GET['submit'] == 'Save Settings'){
    $fsM->DoSettings();
  }
  
  if ($gtype != '') {
      $tmpNeigh =  $fsM->GetNeighbors();
      foreach ($tmpNeigh as $nbor){
        $lastseen = date("m/d/y, g:i a", $nbor['lastseen']);
        $lastseen = (empty($lastseen)) ? 'Not Seen' : $lastseen;
        $farmsize = $nbor['sizeX'] . 'x' . $nbor['sizeY'];
		////	
		$nbor['name'] = $fsM->fvnGetNeighborRealName($nbor['fbid']);
		$nbor['name'] = empty($nbor['name']) ? 'UnKnown' : $nbor['name'];
		$wworld           = $nbor['name'];
		$nbor['worldn'] 	  = $wworld."s Farm";
		
        $nbor['plots'] = ($nbor['plots'] < 1) ? 0 : $nbor['plots'];
        $data1[] =  array(
          'fbid'     => $nbor['fbid'], 
          'worldn'   => '"' . $nbor['worldn'] . '"',
          'name'     => '"' . $nbor['name'] . '"', 
          'lastseen' => '"' . $lastseen . '"', 
          'level'    => $nbor['level'],
          'exp'      => round($nbor['xp']), 
          'coins'    => round($nbor['coin']), 
          'cash'     => round($nbor['cash']), 
          'farmsize' => '"' . $farmsize . '"', 
          'fuel'     => $nbor['fuel'], 
          'friends'  => $nbor['friends'],
          'objects'  => $nbor['objects'], 
          'plots'    => $nbor['plots']
        );
      }
      if ( $gtype == 'xml' )      $gridHandler->exportXML($data1);
      else if ( $gtype == 'xls' ) $gridHandler->exportXLS($data1);
      else if ( $gtype == 'csv' ) $gridHandler->exportCSV($data1);
    return;
  }
  
  if(!empty($fsM->error) && $fsM->haveWorld !== false){
    echo $fsM->error;
    return;
  }
  if(isset($_GET) && (count($_GET) > 1)){
    if ($_GET['action'] == 'settings') {
      $fsM->DoSettings();
    }
  }
  define ( 'REQ_VER_PARSER', '218');
  if ((!PX_VER_PARSER) || (PX_VER_PARSER < REQ_VER_PARSER)){
    echo "<br><br><span style=\"text-align:center;color:red\">**** ERROR: fnNeighbors v".fvNeighbors_version." Requires parser version v".REQ_VER_PARSER." or higher ****</span><br>";
    return;
  }

  $fsM->settings = $fsM->LoadSettings();
  ?>
<html>
  <head>
    <link rel="stylesheet" type="text/css" href="/plugins/fvNeighbors/index.css" />
    <link rel="stylesheet" type="text/css" href="/plugins/fvNeighbors/js/grid/gt_grid.css" />
    <script src="/plugins/fvNeighbors/js/grid/gt_msg_en.js"></script>
    <script src="/plugins/fvNeighbors/js/grid/gt_const.js"></script>
    <script src="/plugins/fvNeighbors/js/grid/gt_grid_all.js"></script>
    <script type="text/javascript">
      var data1= [
      <?php 
          $nbors = $fsM->GetNeighbors();
          foreach ($nbors as $nbor) {
            $lastseen = date("m/d/y, g:i a", $nbor['lastseen']);
            if (empty($lastseen)) $lastseen = 'Not Seen';
            
            $lastupdate = date("m/d/y, g:i a", $nbor['timestamp']);
            if (empty($lastupdate)) $lastupdate = 'Not Updated';
            
            $farmsize = $nbor['sizeX'] . 'x' . $nbor['sizeY'];
            $nbor['plots'] = ($nbor['plots'] < 1) ? 0 : $nbor['plots'];
			////	
			$nbor['name'] = $fsM->fvnGetNeighborRealName($nbor['fbid']);
			$nbor['name'] = empty($nbor['name']) ? 'UnKnown' : $nbor['name'];
			$wworld           = $nbor['name'];
			$nbor['worldn'] 	  = $wworld."'s Farm";
            $z=strtotime($lastupdate);
			$z=date('m/d/y, h:i:s A', $z);
            $data[] =  
              sprintf('        {fbid: "%s", worldn: "%s", name: "%s", lastseen: "%s", level: %d, exp: %d, coins: %d, cash: %d, farmsize: "%s", fuel: "%s", friends: %d, objects: %d, plots: %d, lastupdate: "%s"}',
                $nbor['fbid'], $nbor['worldn'], $nbor['name'], $lastseen, $nbor['level'], $nbor['xp'], round($nbor['coin']), round($nbor['cash']), $farmsize, $nbor['fuel'], $nbor['friends'], $nbor['objects'], $nbor['plots'], $z);
          }
          echo implode(",\n", $data);  
      ?>
      ];

      var dsOption= {
        fields :[
          {name : "fbid"                      },
          {name : "worldn"                    },
          {name : "name"                      },
          {name : "lastseen",   type: 'date'  },
          {name : "level",      type: 'float' },
          {name : "exp",        type: 'float' },
          {name : 'coins',      type: 'float' },
          {name : 'cash',       type: 'float' },
          {name : 'farmsize'                  },
          {name : 'fuel',       type: 'float' },
          {name : 'friends',    type: 'float' },
          {name : 'objects',    type: 'float' },
          {name : 'plots',      type: 'float' },
          {name : 'lastupdate', type: 'date'  }    
        ],
        recordType : 'object',
        data       : data1
      };

      var colsOption= [
        {id: 'chk',        isCheckColumn: true, filterable: false, exportable:false },                 
        {id: 'fbid',       header: "Facebook ID",     width :120 },
        {id: 'worldn',     header: "Farm Name",       width :160 },
        {id: 'name',       header: "Name",            width :120  },
        {id: 'lastseen',   header: "Last Seen",       width :120 },
        //{id: 'level',      header: "Level",           width :40  },
        //{id: 'exp',        header: "Exp",      		  width :40  },
        //{id: 'coins',      header: "Coins",           width :40  },
        //{id: 'cash',       header: "FV Cash",         width :60  },
        {id: 'farmsize',   header: "Farm Size",       width :65  },
        //{id: 'fuel',       header: "Fuel",            width :40  },
        {id: 'friends',    header: "Neighbors",       width :70  },
        {id: 'objects',    header: "Objects on Farm", width :100 },
        //{id: 'plots',      header: "Plots",           width :40 },
        {id: 'lastupdate', header: "Updated",         width :130 }
      ];

      var gridOption={
        id               : "grid1",
        container        : 'grid1_container',
        dataset          : dsOption ,
        columns          : colsOption,
        replaceContainer : false,
        pageSizeList     : [5,10,15,20,50,100,300],
        selectRowByCheck : true,
        exportFileName   : 'neighbor-list',
        exportURL        : 'main.php?export=1',
        saveURL          : 'main.php?save=1',
        remotePaging     : false,
        defaultRecord    : ["","","2008-01-01",0,0,0,"",0,0,0,0],
        pageSize         : 50,
        resizable        : true,
        toolbarContent   : 'nav goto | pagesize | reload | del save | print csv xls filter | state'
      };

      var mygrid = new Sigma.Grid(gridOption);
      Sigma.Util.onLoad( Sigma.Grid.render(mygrid));
    </script>

    <script type="text/javascript">
      function showhide(id){
        if (document.getElementById){
          obj = document.getElementById(id);
          if (obj.style.display == "none"){
            obj.style.display = "";
          } else {
            obj.style.display = "none";
          }
        }
      }
    </script> 
  </head>
  <body>
    <h1>fvNeighbors v<?php echo fvNeighbors_version; ?></h1>
    <p><a href="#" onclick="showhide('settings');">Show/Hide Settings</a></p>
    <div id="settings" style="display: none;">
      <form id="settings" method="get">
        <small>
          <input type="checkbox" name="accepthelp" value="accepthelp" <? if ($fsM->settings['accepthelp'] == 1) echo 'checked'; ?> />Accept Neighbors Help<br />
          <input type="checkbox" name="delpending" value="delpending" <? if ($fsM->settings['delpending'] == 1) echo 'checked'; ?> />Cancel Pending Neighbor Requests <i>(Warning: Do not invite new neighbors while this is checked)</i><br />
          Number of Neighbors to Help/Update Per Cycle: <input type="text" name="helpcycle" size="4" value="<?php echo $fsM->settings['helpcycle']; ?>" />&nbsp;
          Help/Update Neighbors every <input type="text" name="helptime" size="4" value="<?php echo $fsM->settings['helptime']; ?>" /> hours.<br />
          <b><i>Help Neighbors do the Following:</i></b><br />
          <input type="checkbox" name="htrees" value="htrees"           <? if ($fsM->settings['htrees']     == 1) echo 'checked'; ?> />Harvest Trees&nbsp;
          <input type="checkbox" name="hanimals" value="hanimals"       <? if ($fsM->settings['hanimals']    == 1) echo 'checked'; ?> />Harvest Animals&nbsp;
          <input type="checkbox" name="pplots" value="pplots"           <? if ($fsM->settings['pplots']      == 1) echo 'checked'; ?> />Plow Plots&nbsp;
          <input type="checkbox" name="fplots" value="fplots"           <? if ($fsM->settings['fplots']      == 1) echo 'checked'; ?> />Fertilize Plots&nbsp;
          <input type="checkbox" name="ucrops" value="ucrops"           <? if ($fsM->settings['ucrops']      == 1) echo 'checked'; ?> />Unwither Crops&nbsp;
          <input type="checkbox" name="fchickens" value="fchickens"     <? if ($fsM->settings['fchickens']   == 1) echo 'checked'; ?> />Feed Chickens&nbsp;
		  <input type="checkbox" name="sendfeed" value="sendfeed"       <? if ($fsM->settings['sendfeed']    == 1) echo 'checked'; ?> />Send Feed&nbsp;<br />
		  <input type="checkbox" name="domissions" value="domissions"   <? if ($fsM->settings['domissions']  == 1) echo 'checked'; ?> />Do Missions&nbsp;
          <input type="checkbox" name="getcandy" value="getcandy"       <? if ($fsM->settings['getcandy']    == 1) echo 'checked'; ?> />Get Halloween Candy&nbsp;
		  <input type="checkbox" name="dotricks" value="dotricks"       <? if ($fsM->settings['dotricks']    == 1) echo 'checked'; ?> />Do Halloween Tricks&nbsp;
		  <input type="checkbox" name="fpigpen" value="fpigpen"       <? if ($fsM->settings['fpigpen']    == 1) echo 'checked'; ?> />Feed Pig Pen&nbsp;
		  <input type="checkbox" name="getholidaygifts" value="getholidaygifts"       <? if ($fsM->settings['getholidaygifts']    == 1) echo 'checked'; ?> />Harvest Cupids Castle&nbsp;
		  <input type="checkbox" name="hgreenhouse" value="hgreenhouse"       <? if ($fsM->settings['hgreenhouse']    == 1) echo 'checked'; ?> />Harvest Greenhouse<br />
		  <input type="checkbox" name="hcottage" value="hcottage"       <? if ($fsM->settings['hcottage']    == 1) echo 'checked'; ?> />Harvest Leprechaun Cottage<br /><br />
          <input type="checkbox" name="vneighborsn" value="vneighborsn" <? if ($fsM->settings['vneighborsn'] == 1) echo 'checked'; ?> />Visit Neighbors Neighbors <i>(Warning: On 10/21/10 Zynga disabled the method to do this, I am leaving here in case they reenable it, Do not complain about errors if this is on)</i><br />
          <input type="submit" name="submit" value="Save Settings" />
        </small>
      </form>
    </div>
    <b>Neighbor Neighbors:</b> <?php echo $fsM->NNCount(); ?><br />
    <div id="grid1_container" style="width: 100%; height: 500px"></div>
  </body>
</html>
<?
  unset($fsM);
}
?>