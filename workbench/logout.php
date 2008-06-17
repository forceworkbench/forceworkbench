<?php
require_once('session.php');
require_once('shared.php');
	
	if($_SESSION){
		require_once('header.php');
		if($_SESSION['config']['invalidateSessionOnLogout']){
			try{
				$mySforceConnection->logout();
				$sessionInvatidated = true;
			} catch(Exception $e){
				$sessionInvatidated = false;
			}
		} else {
			$sessionInvatidated = false;
		}
		
		session_unset();
		session_destroy();
		
		if($sessionInvatidated == true){
			show_info('You have been successfully logged out of the Workbench and Salesforce.');
		} else {
			show_info('You have been successfully logged out of the Workbench.');
		}
		
		include_once('footer.php');
	} else {
		session_unset();
		session_destroy();
		header('Location: login.php');
	}
?>
