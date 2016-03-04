<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');

if (isset($_POST['ident']) && $_POST['ident'] != "")
{
	header('Location: '.$globalURL.'/ident/'.$_POST['ident']);
} else {
	header('Location: '.$globalURL);
}
?>