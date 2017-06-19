<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
$Stats = new Stats();
$title = _("Statistics").' - '._("Busiest Time of the Day");

$type = 'aircraft';
if (isset($_GET['marine'])) {
	$type = 'marine';
	require_once('require/class.Marine.php');
	$Marine = new Marine();
} elseif (isset($_GET['tracker'])) {
	$type = 'tracker';
	require_once('require/class.Tracker.php');
	$Tracker = new Tracker();
}

if (!isset($filter_name)) $filter_name = '';
$airline_icao = (string)filter_input(INPUT_GET,'airline',FILTER_SANITIZE_STRING);
if ($airline_icao == '' && isset($globalFilter)) {
    if (isset($globalFilter['airline'])) $airline_icao = $globalFilter['airline'][0];
}

require_once('header.php');
include('statistics-sub-menu.php');

print '<link href="'.$globalURL.'/css/c3.min.css" rel="stylesheet" type="text/css">';
print '<script type="text/javascript" src="'.$globalURL.'/js/d3.min.js"></script>';
print '<script type="text/javascript" src="'.$globalURL.'/js/c3.min.js"></script>';
print '<div class="info">
	    <h1>'._("Busiest Time of the Day").'</h1>
	</div>
	<p>'._("Below is a list of the most common <strong>time of day</strong>.").'</p>';

if ($type == 'aircraft') $hour_array = $Stats->countAllHours('hour',true,$airline_icao,$filter_name);
elseif ($type == 'marine') $hour_array = $Marine->countAllHours('hour',true);
elseif ($type == 'tracker') $hour_array = $Tracker->countAllHours('hour',true);
print '<div id="chartHour" class="chart" width="100%"></div><script>';
$hour_data = '';
$hour_cnt = '';
$last = 0;
foreach($hour_array as $hour_item)
{
	while($last != $hour_item['hour_name']) {
		$hour_data .= '"'.$last.':00",';
		$hour_cnt .= '0,';
		$last++;
	}
	$last++;
	$hour_data .= '"'.$hour_item['hour_name'].':00",';
	$hour_cnt .= $hour_item['hour_count'].',';
}
$hour_data = "[".substr($hour_data, 0, -1)."]";
$hour_cnt = "['flights',".substr($hour_cnt,0,-1)."]";
print 'c3.generate({
    bindto: "#chartHour",
    data: {
    columns: ['.$hour_cnt.'], types: { flights: "area"}, colors: { flights: "#1a3151"}},
    axis: { x: { type: "category", categories: '.$hour_data.'},y: { label: "# of Flights"}},legend: { show: false }});';
print '</script>';

if ($type == 'aircraft') $hour_array = $Stats->countAllHours('count',true,$airline_icao,$filter_name);
elseif ($type == 'marine') $hour_array = $Marine->countAllHours('count',true);
elseif ($type == 'tracker') $hour_array = $Tracker->countAllHours('count',true);
if (!empty($hour_array))
{
	print '<div class="table-responsive">';
	print '<table class="common-hour table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th>'._("Hour").'</th>';
	print '<th>'._("Number").'</th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($hour_array as $hour_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		print '<td>'.$hour_item['hour_name'].':00</td>';
		print '<td>'.$hour_item['hour_count'].'</td>';
		print '</tr>';
		$i++;
	}
	print '<tbody>';
	print '</table>';
	print '</div>';
}

require_once('footer.php');
?>