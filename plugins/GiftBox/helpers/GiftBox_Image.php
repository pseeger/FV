<?php
//*********************************
// Make sure image folders exists *
//*********************************
function GB_Check_Image_Cache() {
    if (!is_dir('assets')) {
        mkdir('assets');
        if (!is_dir('assets')) {
            AddLog2('Unable to create assets folder.. run bot as administrator. Images Disabled.');
            return 'fail1';
        }
    }
    $assetneeded = array('decorations', 'decorations\\flags', 'decorations\\Chateau', 'farmers_market', 'animals', 'newsfeed', 'hud', 'hud\\Lights_Button', 'buildings', 'crops', 'flowers', 'trees', 'equipment', 'consumables', 'farms', 'collect', 'achievements', 'achievements\\48x48');
    foreach($assetneeded as $name) {
        $dir = "assets\\$name";
        if (!is_dir($dir)) {
            mkdir($dir);
            if (!is_dir($dir)) {
                AddLog2('Unable create asset sub directory $dir. Images Disabled.');
                return 'fail2';
            }
        }
    }
    return 'OK';
}
//*********************************
// load the image settings
//*********************************
function GB_image_Settings() {
    global $GBmainSet;
    global $GB_Setting;
    $FindNewImageRevision = false;
    $flashRevision = $GBmainSet['FlashVersion'];
    $ImageRevision = $GBmainSet['ImageVersion'];
    if ($flashRevision != $ImageRevision) {
        $FindNewImageRevision = true;
    }
    GB_AddLog("GB Checking new image version");
    if ($FindNewImageRevision) {
        $imgurl = 'http://static.farmville.com/v' . $flashRevision . '/assets/animals/animal_chicken_icon.png';
        $found = false;
        $GB_image_Check = @fopen($imgurl, "r");
        if ($GB_image_Check) {
            $ImageRevision = $flashRevision;
            GB_AddLog("Flash: " . $flashRevision . " Image: " . $ImageRevision);
        } else {
            $TempRev = $flashRevision;
            while ($found == false) {
                $TempRev--;
                if ($TempRev < 37100) {
                    GB_AddLog("No new image version found, continue anyway");
                    $found = true;
                }
                $imgurl = 'http://static.farmville.com/v' . $TempRev . '/assets/animals/animal_chicken_icon.png';
                $GB_image_Check = @fopen($imgurl, "r");
                if ($GB_image_Check) {
                    $ImageRevision = $TempRev;
                    $found = true;
                } //end if

            } // end while

        } // else
        GB_AddLog("Flash: " . $flashRevision . " Image: " . $ImageRevision);
        if ($GBmainSet['ImageVersion'] != $ImageRevision) {
            //GB_Update_User_Setting('ImageRevision' , $ImageRevision);
            $GBmainSet['ImageVersion'] = $ImageRevision;
            global $this_plugin;
            $f = fopen($this_plugin['folder'] . '/Image.txt', "w+");
            fwrite($f, serialize($GBmainSet));
            fclose($f);
            //save_array ( $GBmainSet, 'Image.txt' );

        }
    }
    return;
} // end function
// =============================================================================
// create the thumb
// =============================================================================
function GB_createthumb($name, $newname, $new_w, $new_h, $border = false, $transparency = true, $base64 = false) {
    if (file_exists($newname)) @unlink($newname);
    if (!file_exists($name)) return false;
    $arr = split("\.", $name);
    $ext = $arr[count($arr) - 1];
    $newarr = split("\.", $newname);
    $newext = $newarr[count($newarr) - 1];
    if ($ext == "jpeg" || $ext == "jpg") {
        $img = @imagecreatefromjpeg($name);
    } elseif ($ext == "png") {
        $img = @imagecreatefrompng($name);
    } elseif ($ext == "gif") {
        $img = @imagecreatefromgif($name);
    }
    if (!$img) return false;
    $old_x = imageSX($img);
    $old_y = imageSY($img);
    if ($old_x < $new_w && $old_y < $new_h) {
        $thumb_w = $old_x;
        $thumb_h = $old_y;
    } elseif ($old_x > $old_y) {
        $thumb_w = $new_w;
        $thumb_h = floor(($old_y * ($new_h / $old_x)));
    } elseif ($old_x < $old_y) {
        $thumb_w = floor($old_x * ($new_w / $old_y));
        $thumb_h = $new_h;
    } elseif ($old_x == $old_y) {
        $thumb_w = $new_w;
        $thumb_h = $new_h;
    }
    $thumb_w = ($thumb_w < 1) ? 1 : $thumb_w;
    $thumb_h = ($thumb_h < 1) ? 1 : $thumb_h;
    $new_img = ImageCreateTrueColor($thumb_w, $thumb_h);
    if ($transparency) {
        if ($ext == "png") {
            imagealphablending($new_img, false);
            $colorTransparent = imagecolorallocatealpha($new_img, 0, 0, 0, 127);
            //    $yellow = imagecolorallocatealpha($new_img, 255, 255, 0, 75);
            //  imagefill($new_img, 0, 0, $yellow);
            imagefill($new_img, 0, 0, $colorTransparent);
            imagesavealpha($new_img, true);
        } elseif ($ext == "gif") {
            $trnprt_indx = imagecolortransparent($img);
            if ($trnprt_indx >= 0) {
                //its transparent
                $trnprt_color = imagecolorsforindex($img, $trnprt_indx);
                $trnprt_indx = imagecolorallocate($new_img, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
                imagefill($new_img, 0, 0, $trnprt_indx);
                imagecolortransparent($new_img, $trnprt_indx);
            }
        }
    } else {
        Imagefill($new_img, 0, 0, imagecolorallocate($new_img, 255, 255, 255));
    }
    imagecopyresampled($new_img, $img, 0, 0, 0, 0, $thumb_w, $thumb_h, $old_x, $old_y);
    if ($border) {
        $black = imagecolorallocate($new_img, 0, 0, 0);
        imagerectangle($new_img, 0, 0, $thumb_w, $thumb_h, $black);
    }
    if ($base64) {
        ob_start();
        imagepng($new_img);
        $img = ob_get_contents();
        ob_end_clean();
        $return = base64_encode($img);
    } else {
        if ($newext == "jpeg" || $ext == "jpg") {
            imagejpeg($new_img, $newname, 50);
            $return = true;
        } elseif ($newext == "png") {
            imagepng($new_img, $newname);
            //imagejpeg($new_img, $newname.".jpeg", 50);
            //imagegif($new_img, $newname.".gif");
            $return = true;
        } elseif ($newext == "gif") {
            imagegif($new_img, $newname);
            $return = true;
        }
    }
    imagedestroy($new_img);
    imagedestroy($img);
    return $return;
}
function GB_Show_ImageTab($ObjD) {
    global $images;
    if ($images == 1) {
        $GB_iconurl = $ObjD['iconurl'];
        $GB_image = "<img class=\"icon\" src=\"main.php?image=$GB_iconurl\">";
    } else {
        $GB_image = "&nbsp;";
    }
    return $GB_image;
}
function GB_Show_ImageTabName($ObjD) {
    global $images;
    if ($images == 1) {
        $GB_iconurl = $ObjD['iconurl'];
        $GB_image = "<img class=\"icon\" name=\"main.php?image=$GB_iconurl\">";
    } else {
        $GB_image = "&nbsp;";
    }
    return $GB_image;
}
function GB_ShowIMG($value) {
    global $images;
    global $GB_ImagePath;
    global $vDataDB, $flashRevision;
    if (isset($value['_name'])) {
        $name = $value['_name'];
    } else {
        $name = '';
    }
    if (isset($value['iconurl']) && $images == 1) {
        $x_iconurl = $value['iconurl'];
        $fileurl = str_replace("/", "\\", $x_iconurl);
        $fileurl40 = $fileurl . ".40x40.jpeg";
        $x_iconurl = '/' . $value['iconurl'];
        $x_iconurl40 = $x_iconurl . ".40x40.jpeg";
        if (file_exists($fileurl40)) {
            if ((filesize($fileurl40) > 800)) {
                $GB_image = '<img class="icon" src="' . $x_iconurl40 . '" alt="' . $name . ' (40)" />';
                return $GB_image;
            }
        }
        if (file_exists($fileurl)) {
            if ((filesize($fileurl) != 0)) { //$GB_image = '<img class="icon" src="' . $x_local_iconurl . '">';
                $GB_image = '<img class="icon" src="' . $x_iconurl . '" alt="' . $name . '" />';
                return $GB_image;
            } else { // file exist, but is 0
                GB_AddLog("GB found a image that is 0 byte.");
                GB_AddLog("GB removed image" . $x_iconurl);
                @unlink($fileurl); // remove the 0 file
                @unlink($fileurl . ".40x40.jpeg"); // remove the 0 file
                // now update the parser sqlite, to download it a the end of the cycle
                GB_AddLog("GB updateing the SQL for image." . $name);
                $vDataDB->queryExec('DELETE FROM units WHERE name = "' . $name . '" AND field = "imageready"');
            }
        }
    } else {
        $GB_image = "-&nbsp;";
        return $GB_image;
    }
    // file does not exist, show alternative.
    $x_iconurl = '/plugins/GiftBox/image/progress.gif';
    $GB_image = '<img class="icon" src="' . $x_iconurl . '" alt="' . $name . '" />';
    return $GB_image;
}
function OLD___GB_ShowIMG($value) {
    global $images;
    global $GB_ImagePath;
    if (isset($value['iconurl']) && $images == 1) {
        $x_local_iconurl = 'file:///' . $GB_ImagePath . '/' . $value['iconurl'];
        $x_iconurl = $value['iconurl'];
        $fileurl = str_replace("/", "\\", $x_iconurl);
        if (file_exists($fileurl)) {
            if ((filesize($fileurl) != 0)) {
                $GB_image = '<img class="icon" src="' . $x_local_iconurl . '">';
                return $GB_image;
            }
        }
    } else {
        $GB_image = "-&nbsp;";
        return $GB_image;
    }
    $x_local_iconurl = 'file:///' . $GB_ImagePath . '/plugins/GiftBox/image/progress.gif';
    $GB_image = '<img class="icon" src="' . $x_local_iconurl . '">';
    return $GB_image;
}
function GB_ShowIMGbig($value) {
    global $images;
    global $GB_ImagePath;
    if (isset($value['iconurl']) && $images == 1) {
        $x_iconurl = $value['iconurl'];
        $fileurl = str_replace("/", "\\", $x_iconurl);
        $x_iconurl = '/' . $value['iconurl'];
        if (file_exists($fileurl)) {
            if ((filesize($fileurl) != 0)) {
                $GB_image = '<img src="' . $x_iconurl . '" alt="" />';
                return $GB_image;
            }
        }
    } else {
        $GB_image = "-&nbsp;";
        return $GB_image;
    }
    // file does not exist, show alternative.
    $x_iconurl = '/plugins/GiftBox/image/progress.gif';
    $GB_image = '<img  src="' . $x_iconurl . '" alt="" />';
    return $GB_image;
}
function OLD___GB_ShowIMGbig($value) {
    global $images;
    global $GB_ImagePath;
    if (isset($value['iconurl']) && $images == 1) {
        $x_local_iconurl = 'file:///' . $GB_ImagePath . '/' . $value['iconurl'];
        $x_iconurl = $value['iconurl'];
        $fileurl = str_replace("/", "\\", $x_iconurl);
        if (file_exists($fileurl)) {
            if ((filesize($fileurl) != 0)) {
                $GB_image = '<img src="' . $x_local_iconurl . '">';
                return $GB_image;
            }
        }
    } else {
        $GB_image = "&nbsp;";
        return $GB_image;
    }
    $x_local_iconurl = 'file:///' . $GB_ImagePath . '/plugins/GiftBox/image/progress.gif';
    $GB_image = '<img src="' . $x_local_iconurl . '">';
    return $GB_image;
}
function GB_Show_ImageFile($ObjD) {
    global $images;
    global $GB_ImagePath;
    if (isset($value['iconurl']) && $images == 1) {
        $GB_iconurl = $ObjD['iconurl'];
        $GB_image = '<img class="icon" src="file:///' . $GB_ImagePath . '/' . $GB_iconurl . ".40x40.jpeg" . '">';
    } else {
        $GB_image = "&nbsp;";
    }
    return $GB_image;
}
function GB_Show_Image($value) {
    global $images;
    if (isset($value['iconurl']) && $images == 1) {
        $GB_iconurl = $value['iconurl'];
        $GB_image = "<img class=\"icon\" src=\"main.php?image=$GB_iconurl\">";
    } else {
        $GB_image = "&nbsp;";
    }
    return $GB_image;
}
// =============================================================================
// create the Farm Image
// =============================================================================
function create_image2() {
    global $GB_Setting;
    global $GBDBmain;
    global $GBDBuser;
    $GBSQL = "SELECT * from locations ";
    $result = sqlite_query($GBDBuser, $GBSQL);
    $locs = sqlite_fetch_all($result);
    //if(file_exists( "plugins/GiftBox/".$GB_Setting['userid']."_".GBox_XY_map ))
    //  {
    //     $MapXY = load_array ( GBox_XY_map );
    //   }
    //   else
    //   {
    //      AddLog2("GB_XY_map.txt not found");
    //      $MapXY = array();
    //   }
    @list($level, $gold, $cash, $FarmSizeX, $FarmSizeY) = explode(';', @file_get_contents(F('playerinfo.txt')));
    if (($FarmSizeX == '') || ($FarmSizeY == '')) {
        $GB_place_items = "No";
        return;
    } else {
        $GB_place_items = "OK";
    }
    $maxX = $FarmSizeX * 4;
    $maxX = $maxX + 3;
    $maxY = $FarmSizeY * 4;
    $maxY = $maxY + 3; //GB_AddLog ("*** SQL Error *** " . $GBSQL );
    $im = @imagecreate($maxX, $maxY) or GB_AddLog("Cannot Initialize new GD image stream");
    $background_color = imagecolorallocate($im, 255, 255, 255); // yellow
    $red = imagecolorallocate($im, 255, 0, 0); // red
    $green = imagecolorallocate($im, 0, 255, 0);
    $blue = imagecolorallocate($im, 0, 0, 255); // blue
    $white = imagecolorallocate($im, 255, 255, 255);
    $yellow = imagecolorallocate($im, 255, 255, 0);
    $black = imagecolorallocate($im, 0, 0, 0);
    $purple = ImageColorAllocate($im, 153, 51, 255); //purple
    $pink = ImageColorAllocate($im, 255, 0, 128); //pink
    $grey = ImageColorAllocate($im, 192, 192, 192); //grey
    $brown = ImageColorAllocate($im, 51, 0, 0);
    $loc = "Animal";
    $style = array($white, $white, $white, $blue, $blue, $blue);
    ImageSetStyle($im, $style);
    $X1 = $GB_Setting[$loc . 'X1'] * 4;
    $Y1 = $maxY - $GB_Setting[$loc . 'Y1'] * 4;
    $X2 = $GB_Setting[$loc . 'X2'] * 4;
    $Y2 = $maxY - $GB_Setting[$loc . 'Y2'] * 4;
    //imagefilledrectangle($im, $X1,  $Y1 , $X2, $Y2, $blue);
    imagefilledrectangle($im, $X1, $Y1, $X2, $Y2, IMG_COLOR_STYLED);
    $loc = "Tree";
    $style = array($white, $white, $white, $yellow, $yellow, $yellow);
    ImageSetStyle($im, $style);
    $X1 = $GB_Setting[$loc . 'X1'] * 4;
    $Y1 = $maxY - $GB_Setting[$loc . 'Y1'] * 4;
    $X2 = $GB_Setting[$loc . 'X2'] * 4;
    $Y2 = $maxY - $GB_Setting[$loc . 'Y2'] * 4;
    //imagefilledrectangle($im, $X1,  $Y1 , $X2, $Y2, $yellow);
    imagefilledrectangle($im, $X1, $Y1, $X2, $Y2, IMG_COLOR_STYLED);
    $loc = "Decoration";
    $style = array($white, $white, $white, $black, $black, $black);
    ImageSetStyle($im, $style);
    $X1 = $GB_Setting[$loc . 'X1'] * 4;
    $Y1 = $maxY - $GB_Setting[$loc . 'Y1'] * 4;
    $X2 = $GB_Setting[$loc . 'X2'] * 4;
    $Y2 = $maxY - $GB_Setting[$loc . 'Y2'] * 4;
    //imagefilledrectangle($im, $X1,  $Y1 , $X2, $Y2, $grey);
    imagefilledrectangle($im, $X1, $Y1, $X2, $Y2, IMG_COLOR_STYLED);
    foreach($locs as $loc) {
        $GB_fill = $red;
        if (strpos($loc['_what'], 'E') !== false) {
            $GB_fill = $green;
        }
        if (strpos($loc['_what'], 'Decoration') !== false) {
            $GB_fill = $black;
        }
        if (strpos($loc['_what'], 'Animal') !== false) {
            $GB_fill = $purple;
        }
        if (strpos($loc['_what'], 'Building') !== false) {
            $GB_fill = $pink;
        }
        if (strpos($loc['_what'], 'Plot') !== false) {
            $GB_fill = $brown;
        }
        $Map_PXI = $loc['_X'] * 4;
        $Map_PYI = $loc['_Y'] * 4;
        $Map_PYI = $maxY - $Map_PYI;
        imagefilledrectangle($im, $Map_PXI, $Map_PYI, $Map_PXI + 1, $Map_PYI + 1, $GB_fill);
    }
    $GB_map_image = "plugins/GiftBox/" . $GB_Setting['userid'] . "_FarmMap3.png";
    imagepng($im, $GB_map_image);
    imagedestroy($im);
}
?>