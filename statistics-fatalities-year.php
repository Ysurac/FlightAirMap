<?php
require_once('require/class.Connection.php');
require_once('require/class.Accident.php');
require_once('require/class.Language.php');
$Accident = new Accident();
$title = _("Statistics").' - '._("Fatalities by Year");

require_once('header.php');
include('statistics-sub-menu.php');
print '<script type="text/javascript" src="https://www.google.com/jsapi"></script>
	<div class="info">
	  	<h1>'._("Fatalities").'</h1>
	</div>
      <p>'._("Below is a chart that plots the fatalities by <strong>year</strong>.").'</p>';

$date_array = $Accident->countFatalitiesByYear();
print '<div id="chart" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["'._("Year").'", "'._("# of Fatalities").'"], ';

$date_data = '';
foreach($date_array as $date_item)
{
	$date_data .= '[ "'.$date_item['year'].'",'.$date_item['count'].'],';
}
$date_data = substr($date_data, 0, -1);
print $date_data;
print ']);
    
            var options = {
            	legend: {position: "none"},
            	chartArea: {"width": "80%", "height": "60%"},
            	vAxis: {title: "'._("# of Fatalities").'"},
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

if (!empty($date_array))
{
	foreach($date_array as $key => $row) {
		$years[$key] = $row['year'];
		$counts[$key] = $row['count'];
	}
	//array_multisort($years,SORT_DESC,$date_array);
	array_multisort($counts,SORT_DESC,$date_array);
	print '<div class="table-responsive">';
	print '<table class="common-date table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th>'._("Year").'</th>';
	print '<th>'._("# of Fatalities").'</th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($date_array as $date_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		print '<td>';
		print '<a href="'.$globalURL.'/accident/'.$date_item['year'].'">'.$date_item['year'].'</a>';
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