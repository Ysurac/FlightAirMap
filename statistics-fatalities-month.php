<?php
require_once('require/class.Connection.php');
require_once('require/class.Accident.php');
require_once('require/class.Language.php');
$Accident = new Accident();
$title = _("Statistics").' - '._("Fatalities last 12 months");

require_once('header.php');
include('statistics-sub-menu.php');
print '<link href="'.$globalURL.'/css/c3.min.css" rel="stylesheet" type="text/css">';
print '<script type="text/javascript" src="'.$globalURL.'/js/d3.min.js"></script>';
print '<script type="text/javascript" src="'.$globalURL.'/js/c3.min.js"></script>';

print '<div class="info">
	  	<h1>'._("Fatalities last 12 months").'</h1>
	</div>
      <p>'._("Below is a chart that plots the fatalities <strong>last 12 months</strong>.").'</p>';

$date_array = $Accident->countFatalitiesLast12Months();
print '<div id="chart" class="chart" width="100%"></div><script>';
$year_data = '';
$year_cnt = '';
foreach($date_array as $year_item)
{
	$year_data .= '"'.$year_item['year'].'-'.$year_item['month'].'-01",';
	$year_cnt .= $year_item['count'].',';
}
$year_data = "['x',".substr($year_data, 0, -1)."]";
$year_cnt = "['flights',".substr($year_cnt,0,-1)."]";
print 'c3.generate({
    bindto: "#chart",
    data: { x: "x",
    columns: ['.$year_data.','.$year_cnt.'], types: { flights: "area"}, colors: { flights: "#1a3151"}},
    axis: { x: { type: "timeseries",tick: { format: "%Y-%m"}}, y: { label: "# of Flights"}},legend: { show: false }});';
print '</script>';

if (!empty($date_array))
{
	foreach($date_array as $key => $row) {
		$years[$key] = $row['year'];
		$months[$key] = $row['month'];
		$counts[$key] = $row['count'];
	}
//	array_multisort($years,SORT_DESC,$months,SORT_DESC,$date_array);
	array_multisort($counts,SORT_DESC,$date_array);
	print '<div class="table-responsive">';
	print '<table class="common-date table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th>'._("Month").'</th>';
	print '<th>'._("# of Fatalities").'</th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($date_array as $date_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		print '<td>';
		if ($date_item['month'] < 10) $month = '0'.$date_item['month'];
		else $month = $date_item['month'];
		print '<a href="'.$globalURL.'/accident/'.$date_item['year'].'-'.$month.'">'.date('F, Y',strtotime($date_item['year'].'-'.$date_item['month'].'-01')).'</a>';
		print '</td>';
		print '<td>';
		print $date_item['count'];
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