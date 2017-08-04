<?php
require_once(dirname(__FILE__).'/require/settings.php');
$ident = '';
if (isset($_POST['ident'])) $ident = filter_input(INPUT_POST,'ident',FILTER_SANITIZE_STRING);
if (isset($_GET['ident'])) $ident = filter_input(INPUT_GET,'ident',FILTER_SANITIZE_STRING);
if ($ident != '')
{
	if (isset($_GET['marine'])) header('Location: '.$globalURL.'/marine/ident/'.$ident);
	else header('Location: '.$globalURL.'/ident/'.$ident);
} else {
	if ($globalURL == '') {
		header('Location: /');
	} else {
		header('Location: '.$globalURL);
	}
}
?>