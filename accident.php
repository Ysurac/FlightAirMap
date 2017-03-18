<?php
require_once(dirname(__FILE__).'/require/settings.php');
$date = filter_input(INPUT_POST,'date',FILTER_SANITIZE_STRING);
if ($date == '') $date = date('Y-m-d');
header('Location: '.$globalURL.'/accident/'.$date);
?>