<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
$Stats = new Stats();

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
$title = _("Statistics").' - '._("Busiest Month of Last Year");


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
	  	<h1>'._("Busiest Month").'</h1>
	</div>
      <p>'._("Below is a chart that plots the busiest month during the <strong>last year</strong>.").'</p>';

if ($type == 'aircraft') $date_array = $Stats->countAllMonthsLastYear(true,$airline_icao,$filter_name);
elseif ($type == 'marine') $date_array = $Marine->countAllMonthsLastYear(true);
elseif ($type == 'tracker') $date_array = $Tracker->countAllMonthsLastYear(true);
print '<div id="chart" class="chart" width="100%"></div><script>';
$year_data = '';
$year_cnt = '';
foreach($date_array as $year_item)
{
	$year_data .= '"'.$year_item['year_name'].'-'.$year_item['month_name'].'-01'.'",';
	$year_cnt .= $year_item['date_count'].',';
}
$year_data = "['x',".substr($year_data, 0, -1)."]";
$year_cnt = "['flights',".substr($year_cnt,0,-1)."]";
print 'c3.generate({
    bindto: "#chart",
    data: { x: "x",
    columns: ['.$year_data.','.$year_cnt.'], types: { flights: "area"}, colors: { flights: "#1a3151"}},
    axis: { x: { type: "timeseries", localtime: false,tick: { format: "%Y-%m"}}, y: { label: "#"}},legend: { show: false }});';
print '</script>';

if ($type == 'aircraft') $date_array = $Stats->countAllMonths($airline_icao);
elseif ($type == 'marine') $date_array = $Marine->countAllMonths();
elseif ($type == 'tracker') $date_array = $Tracker->countAllMonths();

if (!empty($date_array))
{
	print '<div class="table-responsive">';
	print '<table class="common-date table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th>'._("Date").'</th>';
	print '<th>'._("Number").'</th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($date_array as $date_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		print '<td>';
		if ($type == 'aircraft') {
			print '<a href="'.$globalURL.'/date/'.$date_item['year_name'].'-'.$date_item['month_name'].'">'.date("F, Y", strtotime($date_item['year_name'].'-'.$date_item['month_name'].'-01')).'</a>';
		} else {
			print '<a href="'.$globalURL.'/'.$type.'/date/'.$date_item['year_name'].'-'.$date_item['month_name'].'">'.date("F, Y", strtotime($date_item['year_name'].'-'.$date_item['month_name'].'-01')).'</a>';
		}
		print '</td>';
		print '<td>';
		print $date_item['date_count'];
		print '</td>';
		print '</tr>';
		$i++;
	}
	print '<tbody>';
	print '</table>';
	print '</div>';
}

require_once('footer.php');
?>