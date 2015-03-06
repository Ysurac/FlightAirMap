<?php
require('require/class.Connection.php');
require('require/class.ACARS.php');

$title = "Latest ACARS messages";
require('header.php');

$page_url = $globalURL.'/acars';

print '<div class="info column">';
print '<h1>Latest ACARS messages</h1>';
print '</div>';

print '<div class="table column">';	
print '<p>The table below shows the latest ACARS messages.</p>';
$spotter_array = ACARS::getLatestAcarsData();
if (!empty($spotter_array)) include('table-output.php');
print '</div>';

require('footer.php');
?>