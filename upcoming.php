<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
$Spotter = new Spotter();
$title = "Upcoming Flights";
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

$page_url = $globalURL.'/upcoming';

print '<div class="info column">';
print '<h1>Upcoming Flights</h1>';
print '</div>';

print '<div class="table column">';	
print '<p>'._("This page shows all upcoming flights within the next 3 hours calculated using historical data, based on the number of same flights on the current day of the week &amp; current time. This does not take into account real-time delays and other factors.").'</p>';

$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
if ($sort != '') {
	$spotter_array = $Spotter->getUpcomingFlights($limit_start.",".$absolute_difference, $sort);
} else {
	$spotter_array = $Spotter->getUpcomingFlights($limit_start.",".$absolute_difference);
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