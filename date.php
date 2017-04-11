<?php
require_once(dirname(__FILE__).'/require/settings.php');
$type = 'aircraft';
if (isset($_GET['marine'])) $type = 'marine';
$date = filter_input(INPUT_POST,'date',FILTER_SANITIZE_STRING);
if ($date == '') $date = date('Y-m-d');
if ($type == 'marine') header('Location: '.$globalURL.'/marine/date/'.$date);
else header('Location: '.$globalURL.'/date/'.$date);
?>