<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
$Stats = new Stats();
$title = "Statistic - Most common Callsign";
require_once('header.php');
include('statistics-sub-menu.php'); 
?>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
		<div class="info">
	  	<h1>Most common Callsign</h1>
	  </div>
    
    	<p>Below are the <strong>Top 10</strong> most common ident/callsigns of all airlines.</p>
          	
<?php
$callsign_array = $Stats->countAllCallsigns();
print '<div id="chart" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["Aircraft", "# of Times"], ';
$callsign_data = '';
foreach($callsign_array as $callsign_item)
{
	$callsign_data .= '[ "'.$callsign_item['callsign_icao'].' ('.$callsign_item['airline_name'].')",'.$callsign_item['callsign_icao_count'].'],';
}
$callsign_data = substr($callsign_data, 0, -1);
print $callsign_data;
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

if (!empty($callsign_array))
{
	print '<div class="table-responsive">';
	print '<table class="common-callsigns table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th>Callsign</th>';
	print '<th>Airline</th>';
	print '<th># of times</th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($callsign_array as $callsign_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		print '<td>';
		print '<a href="'.$globalURL.'/ident/'.$callsign_item['callsign_icao'].'">'.$callsign_item['callsign_icao'].'</a>';
		print '</td>';
		print '<td>';
		print '<a href="'.$globalURL.'/airline/'.$callsign_item['airline_icao'].'">'.$callsign_item['airline_name'].'</a>';
		print '</td>';
		print '<td>'.$callsign_item['callsign_icao_count'].'</td>';
		print '</tr>';
		$i++;
	}
	print '<tbody>';
	print '</table>';
	print '</div>';
}

require_once('footer.php');
?>