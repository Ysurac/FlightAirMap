<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');

if (isset($_POST['aircraft_manufacturer']) && $_POST['aircraft_manufacturer'] != '')
{
	$aircraft_manufacturer = filter_input(INPUT_POST,'aircraft_manufacturer',FILTER_SANITIZE_STRING);
	header('Location: '.$globalURL.'/manufacturer/'.$aircraft_manufacturer);
} elseif (isset($_GET['aircraft_manufacturer']) && $_GET['aircraft_manufacturer'] != '')
{
	$aircraft_manufacturer = filter_input(INPUT_GET,'aircraft_manufacturer',FILTER_SANITIZE_STRING);
	header('Location: '.$globalURL.'/manufacturer/'.$aircraft_manufacturer);
} else {
	if ($globalURL == '') {
		header('Location: /');
	} else {
		header('Location: '.$globalURL);
	}
}
?>