#!/usr/bin/php
<?php
    require('class.update_db.php');
    update_db::update_all();
    require_once('../require/class.Spotter.php');
    Spotter::updateFieldsFromOtherTables();
?>