#!/usr/bin/php
<?php
/**
* This script is used to update all tables after a manual insert in database
*/
require_once(dirname(__FILE__).'/../require/settings.php');
if ($globalInstalled) {
    echo '$globalInstalled must be set to FALSE in require/settings.php';
    exit;
}

require_once('../require/class.Connection.php');
require_once('../require/class.Spotter.php');
$Spotter = new Spotter();
$Spotter->updateFieldsFromOtherTables();
?>