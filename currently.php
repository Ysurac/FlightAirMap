<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');
require('require/class.SpotterLive.php');

$title = "Current Activity";
require('header.php');

//calculuation for the pagination
if(!isset($_GET['limit']))
{
  $limit_start = 0;
  $limit_end = 25;
  $absolute_difference = 25;
}  else {
	$limit_explode = explode(",", $_GET['limit']);
	$limit_start = $limit_explode[0];
	$limit_end = $limit_explode[1];
}
$absolute_difference = abs($limit_start - $limit_end);
$limit_next = $limit_end + $absolute_difference;
$limit_previous_1 = $limit_start - $absolute_difference;
$limit_previous_2 = $limit_end - $absolute_difference;

$page_url = $globalURL.'/currently';
 
print '<div class="info column">';
print '<h1>Current Activity</h1>';
print '</div>';

print '<div class="table column">';
print '<p>The table below shows the detailed information of all current flights.</p>';

if (isset($_GET['sort'])) {
	$spotter_array = SpotterLive::getLiveSpotterData($limit_start.",".$absolute_difference, $_GET['sort']);
} else {
	$spotter_array = SpotterLive::getLiveSpotterData($limit_start.",".$absolute_difference);
}

if (!empty($spotter_array))
{
	include('table-output.php');
	print '<div class="pagination">';
	if ($limit_previous_1 >= 0)
	{
		print '<a href="'.$page_url.'/'.$limit_previous_1.','.$limit_previous_2.'/'.$_GET['sort'].'">&laquo;Previous Page</a>';
	}
	if ($spotter_array[0]['query_number_rows'] == $absolute_difference)
	{
		print '<a href="'.$page_url.'/'.$limit_end.','.$limit_next.'/'.$_GET['sort'].'">Next Page&raquo;</a>';
	}
	print '</div>';
	print '</div>';
}
require('footer.php');
?>