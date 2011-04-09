<html>
	<body>
		<select onChange="document.cookie='user='+this.value">
			<option value="">Account</option>
			<?php foreach(glob('FBID_??*') as $fbid) echo '<option value="'.substr($fbid,5).'" >'.$fbid.'</option>';?>
		</select>
		<br />
		<ul>
			<?php foreach(scandir('plugins') as $plugin) if($plugin[0]!='.') echo '<li><a target="plugin" href="/plugins/'.$plugin.'/main.php">'.$plugin.'</a></li>';?>
			<li><a href="phptest.php" target="plugin">PHP-Test</a></li>
			<li><a href="run.php?cmd=gitupdate"target="plugin">Update</a></li>
			<li><a href="run.php?cmd=runparser"target="plugin">Run a cycle</a></li>
		</ul>
	</body>
</html>
