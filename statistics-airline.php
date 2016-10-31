<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
$Stats = new Stats();
$title = _("Statistics").' - '._("Most common Airline");
require_once('header.php');
if (!isset($filter_name)) $filter_name = '';
include('statistics-sub-menu.php'); 

print '<script type="text/javascript" src="https://www.google.com/jsapi"></script>
		<div class="info">
	  	<h1>'._("Most common Airline").'</h1>
	  </div>
    	<p>'._("Below are the <strong>Top 10</strong> most common airlines.").'</p>';

$airline_array = $Stats->countAllAirlines(true,$filter_name);
print '<div id="chart" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["'._("Airline").'", "'._("# of times").'"], ';
$airline_data = '';
foreach($airline_array as $airline_item)
{
	$airline_data .= '[ "'.$airline_item['airline_name'].' ('.$airline_item['airline_icao'].')",'.$airline_item['airline_count'].'],';
}
$airline_data = substr($airline_data, 0, -1);
print $airline_data;
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

if (!empty($airline_array))
{
	print '<div class="table-responsive">';
	print '<table class="common-airline table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th></th>';
	print '<th>'._("Airline").'</th>';
	print '<th>'._("Country").'</th>';
	print '<th>'._("# of times").'</th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($airline_array as $airline_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		print '<td class="logo">';
		print '<a href="'.$globalURL.'/airline/'.$airline_item['airline_icao'].'"><img src="';
		if (isset($globalIVAO) && $globalIVAO && (@getimagesize($globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.gif') || @getimagesize('images/airlines/'.$airline_item['airline_icao'].'.gif')))
		{
			print $globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.gif';
		} elseif (@getimagesize('images/airlines/'.$airline_item['airline_icao'].'.png') || @getimagesize($globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.png'))
		{
			print $globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.png';
		} else {
			print $globalURL.'/images/airlines/placeholder.png';
		}
		print '" /></a>';
		print '</td>';
		print '<td>';
		print '<a href="'.$globalURL.'/airline/'.$airline_item['airline_icao'].'">'.$airline_item['airline_name'].' ('.$airline_item['airline_icao'].')</a>';
		print '</td>';
		print '<td>';
		print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airline_item['airline_country'])).'">'.$airline_item['airline_country'].'</a>';
		print '</td>';
		print '<td>'.$airline_item['airline_count'].'</td>';
		print '</tr>';
		$i++;
	}
	print '<tbody>';
	print '</table>';
	print '</div>';
}
require_once('footer.php');
?>