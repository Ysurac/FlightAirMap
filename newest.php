<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
$Spotter = new Spotter();

if (isset($_POST['category']))
{
	$category = filter_input(INPUT_POST,'category',FILTER_SANITIZE_STRING);
	header('Location: '.$globalURL.'/newest/'.$category);
}

$title = _("Newest");
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
}

$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);

$absolute_difference = abs($limit_start - $limit_end);
$limit_next = $limit_end + $absolute_difference;
$limit_previous_1 = $limit_start - $absolute_difference;
$limit_previous_2 = $limit_end - $absolute_difference;

if (!isset($_GET['category']))
{
	$category = "aircraft";
} else {
	$category = filter_input(INPUT_GET,'category',FILTER_SANITIZE_STRING);
}

$page_url = $globalURL.'/newest/'.$category;

print '<div class="select-item">';
print '<form action="'.$globalURL.'/newest" method="post">';
print '<select name="category" class="selectpicker" data-live-search="true">';

if ($category == "aircraft")
{
	print '<option value="aircraft" selected="selected">'._("Aircraft Type").'</option>';
} else {
	print '<option value="aircraft">'._("Aircraft Type").'</option>';
}

if ($category == "registration")
{
	print '<option value="registration" selected="selected">'._("Aircraft Registration").'</option>';
} else {
	print '<option value="registration">'._("Aircraft Registration").'</option>';
}

if ($category == "airline")
{
	print '<option value="airline" selected="selected">'._("Airline").'</option>';
} else {
	print '<option value="airline">'._("Airline").'</option>';
}

if ($category == "departure_airport")
{
	print '<option value="departure_airport" selected="selected">'._("Departure Airport").'</option>';
} else {
	print '<option value="departure_airport">'._("Departure Airport").'</option>';
}

if ($category == "arrival_airport")
{
	print '<option value="arrival_airport" selected="selected">'._("Arrival Airport").'</option>';
} else {
	print '<option value="arrival_airport">'._("Arrival Airport").'</option>';
}

print '</select>';

print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
print '</form>';
print '</div>';

print '<div class="info column">';
if ($category == "aircraft")
{
	print '<h1>'._("Newest Aircraft Type").'</h1>';
} else if ($category == "registration")
{
	print '<h1>'._("Newest Aircraft Registration").'</h1>';
} else if ($category == "airline")
{
	print '<h1>'._("Newest Airline").'</h1>';
} else if ($category == "departure_airport")
{
	print '<h1>'._("Newest Departure Airport").'</h1>';
} else if ($category == "arrival_airport")
{
	print '<h1>'._("Newest Arrival Airport").'</h1>';
}
print '</div>';

print '<div class="table column">';	
if ($category == "aircraft")
{
	print '<p>'._("The table below shows the detailed information sorted by the newest recorded aircraft type. Each aircraft type is grouped and is shown only once, the first time it flew nearby.").'</p>';
} else if ($category == "registration")
{
	print '<p>'._("The table below shows the detailed information sorted by the newest recorded aircraft by registration. Each aircraft registration is grouped and is shown only once, the first time it flew nearby.").'</p>';
} else if ($category == "airline")
{
	print '<p>'._("The table below shows the detailed information sorted by the newest recorded airline. Each airline is grouped and is shown only once, the first time it flew nearby.").'</p>';
} else if ($category == "departure_airport")
{
	print '<p>'._("The table below shows the detailed information sorted by the newest recorded departure airport. Each departure airport is grouped and is shown only once, the first time an aircraft flew nearby from the airport.").'</p>';
} else if ($category == "arrival_airport")
{
	print '<p>'._("The table below shows the detailed information sorted by the newest recorded arrival airport. Each arrival airport is grouped and is shown only once, the first time an aircraft flew nearby to the airport.").'</p>';
}

if ($category == "aircraft")
{
	$spotter_array = $Spotter->getNewestSpotterDataSortedByAircraftType($limit_start.",".$absolute_difference, $sort);
} else if ($category == "registration")
{
	$spotter_array = $Spotter->getNewestSpotterDataSortedByAircraftRegistration($limit_start.",".$absolute_difference, $sort);
} else if ($category == "airline")
{
	$spotter_array = $Spotter->getNewestSpotterDataSortedByAirline($limit_start.",".$absolute_difference, $sort);
} else if ($category == "departure_airport")
{
	$spotter_array = $Spotter->getNewestSpotterDataSortedByDepartureAirport($limit_start.",".$absolute_difference, $sort);
} else if ($category == "arrival_airport")
{
	$spotter_array = $Spotter->getNewestSpotterDataSortedByArrivalAirport($limit_start.",".$absolute_difference, $sort);
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