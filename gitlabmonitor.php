<?php

require __DIR__ . '/vendor/autoload.php';

session_start();

if (isset($_SESSION['connected']) && ($_SESSION['connected'] == True)) {
	$_SESSION['page'] = 6;
	include 'header.php';
	include 'core.php';

	?>
	<iframe src="<?php echo(GITLAB_MONITOR_URL); ?>" width="100%" height="920px;" frameBorder="0"></iframe>
	<?php

	include 'footer.php';

} else {
	include 'login.php';
}

?>
