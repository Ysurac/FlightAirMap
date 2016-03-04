<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');

if ($_POST['registration'] != "")
{
	header('Location: '.$globalURL.'/registration/'.$_POST['registration']);
} else {
	header('Location: '.$globalURL);
}
?>