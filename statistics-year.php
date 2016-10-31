<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
$Stats = new Stats();
$title = _("Statistics").' - '._("Busiest Month of Last Year");

if (!isset($filter_name)) $filter_name = '';
$airline_icao = (string)filter_input(INPUT_GET,'airline',FILTER_SANITIZE_STRING);
if ($airline_icao == 'all') {
    unset($_COOKIE['stats_airline_icao']);
    setcookie('stats_airline_icao', '', time()-3600);
    $airline_icao = '';
} elseif ($airline_icao == '' && isset($_COOKIE['stats_airline_icao'])) {
    $airline_icao = $_COOKIE['stats_airline_icao'];
} elseif ($airline_icao == '' && isset($globalFilter)) {
    if (isset($globalFilter['airline'])) $airline_icao = $globalFilter['airline'][0];
}
setcookie('stats_airline_icao',$airline_icao);

require_once('header.php');
include('statistics-sub-menu.php');
print '<script type="text/javascript" src="https://www.google.com/jsapi"></script>
	<div class="info">
	  	<h1>'._("Busiest Month").'</h1>
	</div>
      <p>'._("Below is a chart that plots the busiest month during the <strong>last year</strong>.").'</p>';

$date_array = $Stats->countAllMonthsLastYear(true,$airline_icao,$filter_name);
print '<div id="chart" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["'._("Month").'", "'._("# of Flights").'"], ';

$date_data = '';
foreach($date_array as $date_item)
{
	$date_data .= '[ "'.date("F, Y", strtotime($date_item['year_name'].'-'.$date_item['month_name'].'-01')).'",'.$date_item['date_count'].'],';
}
$date_data = substr($date_data, 0, -1);
print $date_data;
print ']);
    
            var options = {
            	legend: {position: "none"},
            	chartArea: {"width": "80%", "height": "60%"},
            	vAxis: {title: "'._("# of Flights").'"},
            	hAxis: {showTextEvery: 2},
            	height:300,
            	colors: ["#1a3151"]
            };
    
            var chart = new google.visualization.AreaChart(document.getElementById("chart"));
            chart.draw(data, options);
          }
          $(window).resize(function(){
    			  drawChart();
    			});
      </script>';

$date_array = $Stats->countAllMonths($airline_icao);
if (!empty($date_array))
{
	print '<div class="table-responsive">';
	print '<table class="common-date table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th>'._("Date").'</th>';
	print '<th>'._("# of Flights").'</th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($date_array as $date_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		print '<td>';
		print '<a href="'.$globalURL.'/date/'.$date_item['year_name'].'-'.$date_item['month_name'].'">'.date("F, Y", strtotime($date_item['year_name'].'-'.$date_item['month_name'].'-01')).'</a>';
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