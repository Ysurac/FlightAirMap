<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');

if ($_POST['departure_airport'] != "" || $_POST['arrival_airport'])
{
	header('Location: '.$globalURL.'/route/'.$_POST['departure_airport'].'/'.$_POST['arrival_airport']);
} else {
	header('Location: '.$globalURL);
}
?>