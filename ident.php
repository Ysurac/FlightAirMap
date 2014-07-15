<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

if ($_POST['ident'] != "")
{
	header('Location: '.$globalURL.'/ident/'.$_POST['ident']);
} else {
	header('Location: '.$globalURL);
}
?>