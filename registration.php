<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');

if ($_POST['registration'] != "")
{
	$registration = filter_input(INPUT_POST,'registration',FILTER_SANITIZE_STRING);
	header('Location: '.$globalURL.'/registration/'.$registration);
} else {
	if ($globalURL == '') {
		header('Location: /');
	} else {
		header('Location: '.$globalURL);
	}
}
?>