<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

if ($_POST['aircraft_manufacturer'] != "")
{
	header('Location: '.$globalURL.'/manufacturer/'.$_POST['aircraft_manufacturer']);
} else {
	header('Location: '.$globalURL);
}
?>