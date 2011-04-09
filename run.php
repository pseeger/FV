<html><body><?php if(!isset($_GET['cmd'])) die('No command found...');
$cmds = array(	'gitupdate' => 'git pull origin master',
		'runparser' => 'php login.php run' );
if(isset($cmds[$_GET['cmd']])) echo exec($cmds[$_GET['cmd']]); else echo 'Whaaaat?';?></body></html>
