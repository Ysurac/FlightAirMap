<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');

if ($_POST['date'] != "")
{
	header('Location: '.$globalURL.'/date/'.$_POST['date']);
} else {
	header('Location: '.$globalURL);
}
?>