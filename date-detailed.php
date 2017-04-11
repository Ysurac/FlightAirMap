<?php
require_once('require/class.Connection.php');
require_once('require/class.Language.php');
$type = '';
$date = filter_input(INPUT_GET,'date',FILTER_SANITIZE_STRING);
if (isset($_GET['marine'])) {
	require_once('require/class.Marine.php');;
	$Marine = new Marine();
	$type = 'marine';
	$page_url = $globalURL.'/marine/date/'.$date;
} elseif (isset($_GET['tracker'])) {
	require_once('require/class.Tracker.php');;
	$Tracker = new Tracker();
	$type = 'tracker';
	$page_url = $globalURL.'/tracker/date/'.$date;
} else {
	require_once('require/class.Spotter.php');;
	$Spotter = new Spotter();
	$type = 'aircraft';
	$page_url = $globalURL.'/date/'.$date;
}

if (!isset($_GET['date'])){
	header('Location: '.$globalURL.'');
} else {
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
	
	$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
	if ($sort != '') 
	{
		if ($type == 'marine') $spotter_array = $Marine->getMarineDataByDate($date,$limit_start.",".$absolute_difference, $sort);
		elseif ($type == 'tracker') $spotter_array = $Tracker->getTrackerDataByDate($date,$limit_start.",".$absolute_difference, $sort);
		else $spotter_array = $Spotter->getSpotterDataByDate($date,$limit_start.",".$absolute_difference, $sort);
	} else {
		if ($type == 'marine') $spotter_array = $Marine->getMarineDataByDate($date,$limit_start.",".$absolute_difference);
		elseif ($type == 'tracker') $spotter_array = $Tracker->getTrackerDataByDate($date,$limit_start.",".$absolute_difference);
		else $spotter_array = $Spotter->getSpotterDataByDate($date,$limit_start.",".$absolute_difference);
	}
	
	if (!empty($spotter_array))
	{
		date_default_timezone_set($globalTimezone);
		if ($type == 'marine') $title = sprintf(_("Detailed View for vessels from %s"),date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601'])));
		elseif ($type == 'tracker') $title = sprintf(_("Detailed View for trackers from %s"),date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601'])));
		else $title = sprintf(_("Detailed View for flights from %s"),date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601'])));

		require_once('header.php');
		print '<div class="select-item">';
		print '<form action="'.$globalURL.'/date" method="post" class="form-inline">';
		print '<div class="form-group">';
		print '<label for="datepickeri">'._("Select a Date").'</label>';
		print '<div class="input-group date" id="datepicker">';
		print '<input type="text" class="form-control" id="datepickeri" name="date" value="" />';
		print '<span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>';
		print '</div>';
		print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
		print '</div>';
		print '</form>';
		print '</div>';
		print '<script type="text/javascript">$(function () { $("#datepicker").datetimepicker({ format: "YYYY-MM-DD", defaultDate: "'.$date.'" }); }); </script>';
		print '<br />';
		print '<div class="info column">';
		if ($type == 'marine') print '<h1>'.sprintf(_("Vessels from %s"),date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601']))).'</h1>';
		else print '<h1>'.sprintf(_("Flights from %s"),date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601']))).'</h1>';
		print '</div>';

		if ($type == 'aircraft') include('date-sub-menu.php');
		print '<div class="table column">';
		if ($type == 'marine') print '<p>'.sprintf(_("The table below shows the detailed information of all vessels on <strong>%s</strong>."),date("l M j, Y", strtotime($spotter_array[0]['date_iso_8601']))).'</p>';
		elseif ($type == 'tracker') print '<p>'.sprintf(_("The table below shows the detailed information of all trackers on <strong>%s</strong>."),date("l M j, Y", strtotime($spotter_array[0]['date_iso_8601']))).'</p>';
		else print '<p>'.sprintf(_("The table below shows the detailed information of all flights on <strong>%s</strong>."),date("l M j, Y", strtotime($spotter_array[0]['date_iso_8601']))).'</p>';
 
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
	} else {
		$title = _("Unknown Date");
		require_once('header.php');
		print '<h1>'._("Error").'</h1>';
		print '<p>'._("Sorry, this date does not exist in this database. :(").'</p>'; 
	}
}

require_once('footer.php');
?>