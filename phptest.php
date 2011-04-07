<html><body>php-cUrl: <?php if(function_exists('curl_init')) echo 'Check.'; else echo 'Nope.';?><br />
php-sqlite: <?php if(function_exists('sqlite_exec')) echo 'Check.'; else echo 'Nope.';?><br /><br />
Accounts: <?php include('config/accounts.php'); print_r($accounts);?>
</body></html>
