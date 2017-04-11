<?php
require_once('require/class.Connection.php');
require_once('require/class.Language.php');

$type = '';
if (isset($_GET['marine'])) {
	require_once('require/class.Marine.php');;
	require_once('require/class.MarineLive.php');
	$MarineLive=new MarineLive();
	$type = 'marine';
} else {
	require_once('require/class.Spotter.php');;
	require_once('require/class.SpotterLive.php');
	$SpotterLive=new SpotterLive();
	$type = 'aircraft';
}

$title = "Current Activity";
require_once('header.php');
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
	if (!ctype_digit(strval($limit_start)) || !ctype_digit(strval($limit_end))) {
		$limit_start = 0;
		$limit_end = 25;
	}
}
$absolute_difference = abs($limit_start - $limit_end);
$limit_next = $limit_end + $absolute_difference;
$limit_previous_1 = $limit_start - $absolute_difference;
$limit_previous_2 = $limit_end - $absolute_difference;

$page_url = $globalURL.'/currently';
 
print '<div class="info column">';
print '<h1>'._("Current Activity").'</h1>';
print '</div>';

print '<div class="table column">';
if ($type == 'aircraft') print '<p>'._("The table below shows the detailed information of all current flights.").'</p>';
elseif ($type == 'marine') print '<p>'._("The table below shows the detailed information of all current vessels.").'</p>';

$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
if ($sort != '') {
	if ($type == 'aircraft') $spotter_array = $SpotterLive->getLiveSpotterData($limit_start.",".$absolute_difference, $sort);
	elseif ($type == 'marine') $spotter_array = $MarineLive->getLiveMarineData($limit_start.",".$absolute_difference, $sort);
} else {
	if ($type == 'aircraft') $spotter_array = $SpotterLive->getLiveSpotterData($limit_start.",".$absolute_difference);
	elseif ($type == 'marine') $spotter_array = $MarineLive->getLiveMarineData($limit_start.",".$absolute_difference);
}

if (!empty($spotter_array))
{
	include('table-output.php');
	print '<div class="pagination">';
	if ($limit_previous_1 >= 0)
	{
		print '<a href="'.$page_url.'/'.$limit_previous_1.','.$limit_previous_2.'/'.$sort.'">&laquo;'._("Previous Page").'</a>';
	}
	if ($spotter_array[0]['query_number_rows'] == $absolute_difference)
	{
		print '<a href="'.$page_url.'/'.$limit_end.','.$limit_next.'/'.$sort.'">'._("Next Page").'&raquo;</a>';
	}
	print '</div>';
	print '</div>';
}
require_once('footer.php');
?>