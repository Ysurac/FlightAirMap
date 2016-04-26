<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
$Stats = new Stats();
$title = _("Statistic - Most common Departure Airport");
require_once('header.php');

include('statistics-sub-menu.php'); 
print '<script type="text/javascript" src="https://www.google.com/jsapi"></script>
		<div class="info">
	  	<h1>'._("Most common Departure Airport").'</h1>
	  </div>
    	<p>'._("Below are the <strong>Top 10</strong> most common departure airports.").'</p>';

$airport_airport_array = $Stats->countAllDepartureAirports();
print '<script>
    	google.load("visualization", "1", {packages:["geochart"]});
    	google.setOnLoadCallback(drawCharts);
    	$(window).resize(function(){
    		drawCharts();
    	});
    	function drawCharts() {
    
        var data = google.visualization.arrayToDataTable([ 
        	["'._("Airport").'", "'._("# of Times").'"],';

$airport_data = '';
foreach($airport_airport_array as $airport_item)
{
	$name = $airport_item['airport_departure_city'].', '.$airport_item['airport_departure_country'].' ('.$airport_item['airport_departure_icao'].')';
	$name = str_replace("'", "", $name);
	$name = str_replace('"', "", $name);
	$airport_data .= '[ "'.$name.'",'.$airport_item['airport_departure_icao_count'].'],';
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
print '<th>'._("Airport").'</th>';
print '<th>'._("Country").'</th>';
print '<th>'._("# of times").'</th>';
print '</thead>';
print '<tbody>';
$i = 1;
foreach($airport_airport_array as $airport_item)
{
	print '<tr>';
	print '<td><strong>'.$i.'</strong></td>';
	print '<td>';
	print '<a href="'.$globalURL.'/airport/'.$airport_item['airport_departure_icao'].'">'.$airport_item['airport_departure_city'].', '.$airport_item['airport_departure_country'].' ('.$airport_item['airport_departure_icao'].')</a>';
	print '</td>';
	print '<td>';
	print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airport_item['airport_departure_country'])).'">'.$airport_item['airport_departure_country'].'</a>';
	print '</td>';
	print '<td>';
	print $airport_item['airport_departure_icao_count'];
	print '</td>';
	print '</tr>';
	$i++;
}
print '<tbody>';
print '</table>';
print '</div>';

require_once('footer.php');
?>