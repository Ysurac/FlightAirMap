<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
$Stats = new Stats();
$title = "Statistic - Most common Country a flight was over";
require_once('header.php');
include('statistics-sub-menu.php'); 
?>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
		<div class="info">
	  	<h1>Most common Country a flight was over</h1>
	  </div>
    
    	<p>Below are the <strong>Top 10</strong> most common country a flight was over.</p>
          	
<?php
$flightover_array = $Stats->countAllFlightOverCountries();
print '<div id="chart" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["Country", "# of Times"], ';
$flightover_data = '';
foreach($flightover_array as $flightover_item)
{
	$flightover_data .= '[ "'.$flightover_item['flight_country'].' ('.$flightover_item['flight_country_iso2'].')",'.$flightover_item['flight_count'].'],';
}
$flightover_data = substr($flightover_data, 0, -1);
print $flightover_data;
print ']);
    
            var options = {
            	chartArea: {"width": "80%", "height": "60%"},
            	height:500,
            	 is3D: true,
                colors: ["#8BA9D0","#1a3151"]
            };
    
            //var chart = new google.visualization.PieChart(document.getElementById("chart"));
	    var chart = new google.visualization.GeoChart(document.getElementById("chart"));
            chart.draw(data, options);
          }
          $(window).resize(function(){
    			  drawChart();
    			});
      </script>';

if (!empty($flightover_array))
{
	print '<div class="table-responsive">';
	print '<table class="common-countries table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th>Name</th>';
	print '<th># of times</th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($flightover_array as $flightover_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		print '<td>';
/*		print '<a href="'.$globalURL.'/ident/'.$callsign_item['callsign_icao'].'">'.$callsign_item['callsign_icao'].'</a>';
		print '</td>';
		print '<td>';
		print '<a href="'.$globalURL.'/airline/'.$callsign_item['airline_icao'].'">'.$callsign_item['airline_name'].'</a>';
*/
		print $flightover_item['flight_country'];
		print '</td>';
		print '<td>'.$flightover_item['flight_count'].'</td>';
		print '</tr>';
		$i++;
	}
	print '<tbody>';
	print '</table>';
	print '</div>';
}

require_once('footer.php');
?>