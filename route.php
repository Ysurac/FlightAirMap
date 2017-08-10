<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');

if (isset($_POST['departure_airport']) && $_POST['departure_airport'] != '')
{
	$departure_airport = filter_input(INPUT_POST,'departure_airport',FILTER_SANITIZE_STRING);
	$arrival_airport = filter_input(INPUT_POST,'arrival_airport',FILTER_SANITIZE_STRING);
	header('Location: '.$globalURL.'/route/'.$departure_airport.'/'.$arrival_airport);
} else {
	if ($globalURL == '') {
		header('Location: /');
	} else {
		header('Location: '.$globalURL);
	}
}
?>