<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');

if ($_POST['aircraft_manufacturer'] != "")
{
	header('Location: '.$globalURL.'/manufacturer/'.$_POST['aircraft_manufacturer']);
} else {
	header('Location: '.$globalURL);
}
?>