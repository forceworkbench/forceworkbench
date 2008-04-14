<?php
require_once('session.php');
require_once('shared.php');
	if($_SESSION){
		session_unset();
		session_destroy();
		require_once('header.php');
		show_info('You have been successfully logged out.');
		include_once('footer.php');
	} else {
		session_unset();
		session_destroy();
		header('Location: login.php');
	}
?>
