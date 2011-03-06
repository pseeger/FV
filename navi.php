<html><select onChange="document.cookie='user='+this.value"><option value="">Account</option><?php foreach(glob('FBID_??*') as $fbid) echo '<option value="'.substr($fbid,5).'" >'.$fbid.'</option>';?></select>
<br /><ul><?php foreach(scandir('plugins') as $plugin) if($plugin[0]!='.') echo '<li><a target="plugin" href="/plugins/'.$plugin.'/main.php">'.$plugin.'</a></li>';?></ul></html>
