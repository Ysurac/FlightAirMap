<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
$Stats = new Stats();
$title = _("Statistics").' - '._("Busiest Month of Last Year");

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
	  	<h1>'._("Busiest Day Last Month").'</h1>
	  </div>
      <p>'._("Below is a chart that plots the busiest day during the <strong>last month</strong>.").'</p>';

if ($type == 'aircraft') $date_array = $Stats->countAllDatesLastMonth($airline_icao,$filter_name);
elseif ($type == 'marine') $date_array = $Marine->countAllDatesLastMonth();
elseif ($type == 'tracker') $date_array = $Tracker->countAllDatesLastMonth();

print '<div id="chart" class="chart" width="100%"></div><script>';
$month_data = '';
$month_cnt = '';
foreach($date_array as $month_item)
{
	$month_data .= '"'.$month_item['date_name'].'",';
	$month_cnt .= $month_item['date_count'].',';
}
$month_data = "['x',".substr($month_data, 0, -1)."]";
$month_cnt = "['flights',".substr($month_cnt,0,-1)."]";
print 'c3.generate({
    bindto: "#chart",
    data: { x: "x",
    columns: ['.$month_data.','.$month_cnt.'], types: { flights: "area"}, colors: { flights: "#1a3151"}},
    axis: { x: { type: "timeseries", localtime: false,tick: { format: "%Y-%m-%d"}}, y: { label: "#"}},legend: { show: false }});';
print '</script>';

//$date_array = $Stats->countAllDates();
if (!empty($date_array))
{
	foreach($date_array as $key => $row) {
		$years[$key] = $row['date_name'];
		$counts[$key] = $row['date_count'];
	}
	array_multisort($counts,SORT_DESC,$date_array);
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
		if ($type == 'aircraft') print '<a href="'.$globalURL.'/date/'.date('Y-m-d',strtotime($date_item['date_name'])).'">'.date("l F j, Y", strtotime($date_item['date_name'])).'</a>';
		else print '<a href="'.$globalURL.'/'.$type.'/date/'.date('Y-m-d',strtotime($date_item['date_name'])).'">'.date("l F j, Y", strtotime($date_item['date_name'])).'</a>';
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