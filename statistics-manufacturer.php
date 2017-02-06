<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
$Stats = new Stats();
$title = _("Statistics").' - '._("Most common Aircraft Manufacturer");

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

$year = filter_input(INPUT_GET,'year',FILTER_SANITIZE_NUMBER_INT);
$month = filter_input(INPUT_GET,'month',FILTER_SANITIZE_NUMBER_INT);

require_once('header.php');
include('statistics-sub-menu.php'); 

print '<script type="text/javascript" src="https://www.google.com/jsapi"></script>
 	<div class="info">
	  	<h1>'._("Most common Aircraft Manufacturer").'</h1>
		 </div>
    	<p>'._("Below are the <strong>Top 10</strong> most common aircraft manufacturers.").'</p>';
 
$manufacturers_array = $Stats->countAllAircraftManufacturers(true,$airline_icao,$filter_name,$year,$month);
print '<div id="chart" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["'._("Aircraft Manufacturer").'", "'._("# of times").'"], ';
$manufacturer_data = '';
foreach($manufacturers_array as $manufacturer_item)
{
	$manufacturer_data .= '[ "'.$manufacturer_item['aircraft_manufacturer'].'",'.$manufacturer_item['aircraft_manufacturer_count'].'],';
}
$aircraft_data = substr($manufacturer_data, 0, -1);
print $manufacturer_data;
print ']);
    
            var options = {
            	chartArea: {"width": "80%", "height": "60%"},
            	height:500,
            	 is3D: true
            };
    
            var chart = new google.visualization.PieChart(document.getElementById("chart"));
            chart.draw(data, options);
          }
          $(window).resize(function(){
    			  drawChart();
    			});
      </script>';

if (!empty($manufacturers_array))
{
	print '<div class="table-responsive">';
	print '<table class="common-manufacturer table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th>'._("Aircraft Manufacturer").'</th>';
	print '<th>'._("# of times").'</th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($manufacturers_array as $manufacturer_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		print '<td>';
		print '<a href="'.$globalURL.'/manufacturer/'.strtolower(str_replace(" ", "-", $manufacturer_item['aircraft_manufacturer'])).'">'.$manufacturer_item['aircraft_manufacturer'].'</a>';
		print '</td>';
		print '<td>';
		print $manufacturer_item['aircraft_manufacturer_count'];
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