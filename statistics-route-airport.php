<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
$Spotter = new Spotter();
$title = _("Statistics").' - '._("Most common Route by Airport");
require_once('header.php');
include('statistics-sub-menu.php'); 

print '<script type="text/javascript" src="https://www.google.com/jsapi"></script>
		<div class="info">
	  	<h1>'._("Most common Route by Airport").'</h1>
	  </div>
	<p>'._("Below are the <strong>Top 10</strong> most common Departure &amp; Arrival airport combinations.").'</p>';
     
$route_array = $Spotter->countAllRoutes();
print '<div id="chart" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["'._("Aircraft").'", "'._("# of Times").'"], ';

$route_data = '';
foreach($route_array as $route_item)
{
	$route_data .= '[ "'.$route_item['airport_departure_icao'].' - '.$route_item['airport_arrival_icao'].'",'.$route_item['route_count'].'],';
}
$route_data = substr($route_data, 0, -1);
print $route_data;
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


if (!empty($route_array))
{
	print '<div class="table-responsive">';
	print '<table class="common-routes table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th>'._("Departure Airport").'</th>';
	print '<th>'._("Arrival Airport").'</th>';
	print '<th>'._("# of Times").'</th>';
	print '<th></th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($route_array as $route_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		print '<td>';
		print '<a href="'.$globalURL.'/airport/'.$route_item['airport_departure_icao'].'">'.$route_item['airport_departure_city'].', '.$route_item['airport_departure_country'].' ('.$route_item['airport_departure_icao'].')</a>';
		print '</td>';
		print '<td>';
		print '<a href="'.$globalURL.'/airport/'.$route_item['airport_arrival_icao'].'">'.$route_item['airport_arrival_city'].', '.$route_item['airport_arrival_country'].' ('.$route_item['airport_arrival_icao'].')</a>';
		print '</td>';
		print '<td>'.$route_item['route_count'].'</td>';
		print '<td>';
		print '<a href="'.$globalURL.'/route/'.$route_item['airport_departure_icao'].'/'.$route_item['airport_arrival_icao'].'">'._("Route Profile").'</a>';
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