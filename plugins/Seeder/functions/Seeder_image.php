<?php
//========================================================================================================================
//Seeder_image.php
//by N1n9u3m
//========================================================================================================================
//Seeder_SeedImage
//========================================================================================================================
function Seeder_ShowImage($iconurl)//revised v1.1.2
{
$iconurl = str_replace("//","/","/".$iconurl);
return $iconurl;

}
//========================================================================================================================
//Seeder_BushelImage
//========================================================================================================================
function Seeder_BushelImage($bushel)//added v1.1.2
{

	return $bushel;
//$spacefile = Seeder_imgURL."space.png";
}

//========================================================================================================================
//Seeder_JobImage
//========================================================================================================================
function Seeder_JobImage($quest)//added v1.1.2
{
return $quest;


$iconurl = Bot_path.$iconurl;
$spacefile = Seeder_imgURL."space.png";
$dir = Bot_path.dirname($iconurl);
$image = 1;

   if ((!file_exists($iconfile)) || (filesize($iconfile) == 0))
   {
    $image = 0;//
   }

  if ($image == 1)
  {
  $iconfile = str_replace("//","/","/".$iconurl);
  return $iconfile;
  }
  else
  {
  return $spacefile;
  }

}
//========================================================================================================================
//Seeder_ShowImagebyName
//========================================================================================================================
function Seeder_ShowImagebyName($name)//added v1.1.4
{
$unit = Units_GetUnitByName($name);
$iconurl = $unit['iconurl'];
$iconurl = str_replace("//","/","/".$iconurl);
return $iconurl;
}
//========================================================================================================================
//Seeder_ShowImagebyCode
//========================================================================================================================
function Seeder_ShowImagebyCode($code)//added v1.1.4
{

$unit = Units_GetUnitByName(Units_GetNameByCode($code));
$iconurl = $unit['iconurl'];
return $iconurl;
$iconfile = Bot_path.$iconurl;
$loaderfile = Seeder_imgURL."loader.gif";
$dir = Bot_path.dirname($iconurl);
$image = 1;

   if ((!file_exists($iconfile)) || (filesize($iconfile) == 0))
   {
    $image = 0;
   }

  if ($image == 1)
  {
  $iconfile = str_replace("//","/","/".$iconurl);
  return $iconfile;
  }
  else
  {
  return $loaderfile;
  }

}
//========================================================================================================================
//geoip images
//========================================================================================================================
/*
http://c.tadst.com/gfx/fl/32/us.png
http://www.wtzclock.com/images/flags/us.gif

http://images.boardhost.com/flags/ php echo strtolower($Seeder_info['geoip']); .png

*/
//========================================================================================================================
?>
