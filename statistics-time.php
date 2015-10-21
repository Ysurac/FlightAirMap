<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');
$Spotter = new Spotter();
$title = "Statistic - Most busiest Time of the Day";
require('header.php');
include('statistics-sub-menu.php'); 
?>

<script type="text/javascript" src="https://www.google.com/jsapi"></script>
		<div class="info">
	  	<h1>Most busiest Time of the Day</h1>
	  </div>
      
       <p>Below is a list of the most common <strong>time of day</strong>.</p>
      
<?php

$hour_array = $Spotter->countAllHours('hour');
print '<div id="chartHour" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["Hour", "# of Flights"], ';

$hour_data = '';
if (isset($globalTimezone)) {
	date_default_timezone_set($globalTimezone);
} else {
	date_default_timezone_set('UTC');
}
print_r($hour_array);
foreach($hour_array as $hour_item)
{
	$hour_data .= '[ "'.$hour_item['hour_name'].':00",'.$hour_item['hour_count'].'],';
}
$hour_data = substr($hour_data, 0, -1);
print $hour_data;
print ']);
    
            var options = {
            	legend: {position: "none"},
            	chartArea: {"width": "80%", "height": "60%"},
            	vAxis: {title: "# of Flights"},
            	hAxis: {showTextEvery: 2},
            	height:300,
            	colors: ["#1a3151"]
            };
    
            var chart = new google.visualization.AreaChart(document.getElementById("chartHour"));
            chart.draw(data, options);
          }
          $(window).resize(function(){
    			  drawChart();
    			});
      </script>';

$hour_array = $Spotter->countAllHours('count');
if (!empty($hour_array))
{
	print '<div class="table-responsive">';
	print '<table class="common-hour table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th>Hour</th>';
	print '<th># of Flights</th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($hour_array as $hour_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		print '<td>'.$hour_item['hour_name'].':00</td>';
		print '<td>'.$hour_item['hour_count'].'</td>';
		print '</tr>';
		$i++;
	}
	print '<tbody>';
	print '</table>';
	print '</div>';
}

require('footer.php');
?>