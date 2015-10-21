<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');
$Spotter = new Spotter();
$title = "Statistic - Most common Arrival Airport";
require('header.php');
include('statistics-sub-menu.php'); 
?>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
		<div class="info">
	  	<h1>Most common Arrival Airport</h1>
	  </div>
    
    	 <p>Below are the <strong>Top 10</strong> most common arrival airports.</p>
    
    	<?php
    	 $airport_airport_array = $Spotter->countAllArrivalAirports();
    	?>
    
    	<script>
    	google.load("visualization", "1", {packages:["geochart"]});
    	google.setOnLoadCallback(drawCharts);
    	$(window).resize(function(){
    		drawCharts();
    	});
    	function drawCharts() {
    
        var data = google.visualization.arrayToDataTable([ 
        	["Airport", "# of Times"],
<?php

$airport_data = '';
foreach($airport_airport_array as $airport_item)
{
	$name = $airport_item['airport_arrival_city'].', '.$airport_item['airport_arrival_country'].' ('.$airport_item['airport_arrival_icao'].')';
	$name = str_replace("'", "", $name);
	$name = str_replace('"', "", $name);
	$airport_data .= '[ "'.$name.'",'.$airport_item['airport_arrival_icao_count'].'],';
}
$airport_data = substr($airport_data, 0, -1);
print $airport_data;
?>

        ]);
    
        var options = {
        	legend: {position: "none"},
        	chartArea: {"width": "80%", "height": "60%"},
        	height:500,
        	displayMode: "markers",
        	colors: ["#8BA9D0","#1a3151"]
        };
    
        var chart = new google.visualization.GeoChart(document.getElementById("chartAirport"));
        chart.draw(data, options);
      }
    	</script>

      <div id="chartAirport" class="chart" width="100%"></div>
    	
<?php

print '<div class="table-responsive">';
print '<table class="common-airport table-striped">';
print '<thead>';
print '<th></th>';
print '<th>Airport</th>';
print '<th>Country</th>';
print '<th># of times</th>';
print '</thead>';
print '<tbody>';
$i = 1;
foreach($airport_airport_array as $airport_item)
{
	print '<tr>';
	print '<td><strong>'.$i.'</strong></td>';
	print '<td>';
	print '<a href="'.$globalURL.'/airport/'.$airport_item['airport_arrival_icao'].'">'.$airport_item['airport_arrival_city'].', '.$airport_item['airport_arrival_country'].' ('.$airport_item['airport_arrival_icao'].')</a>';
	print '</td>';
	print '<td>';
	print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airport_item['airport_arrival_country'])).'">'.$airport_item['airport_arrival_country'].'</a>';
	print '</td>';
	print '<td>'.$airport_item['airport_arrival_icao_count'].'</td>';
	print '</tr>';
	$i++;
}
print '<tbody>';
print '</table>';
print '</div>';

require('footer.php');
?>