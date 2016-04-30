<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
$Stats = new Stats();
$title = _("Statistics").' - '._("Most common Pilots");
require_once('header.php');

include('statistics-sub-menu.php');

print '<script type="text/javascript" src="https://www.google.com/jsapi"></script>
	<div class="info">
	 	<h1>'._("Most common Pilot").'</h1>
	</div>
	<p>'._("Below are the <strong>Top 10</strong> most common pilot.").'</p>';
	  
	$pilot_array = $Stats->countAllPilots();
	print '<div id="chart" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["'._("Pilot").'", "'._("# of Times").'"], ';
            	$pilot_data = '';
		foreach($pilot_array as $pilot_item)
		{
			$pilot_data .= '[ "'.$pilot_item['pilot_name'].' ('.$pilot_item['pilot_id'].')",'.$pilot_item['pilot_count'].'],';
		}
		$pilot_data = substr($pilot_data, 0, -1);
		print $pilot_data;
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

if (!empty($pilot_array))
{
	print '<div class="table-responsive">';
	print '<table class="common-type table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th>'._("Pilot Name").'</th>';
	print '<th>'._("# of Times").'</th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($pilot_array as $pilot_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		print '<td>';
		print $pilot_item['pilot_name'].' ('.$pilot_item['pilot_id'].')</a>';
		print '</td>';
		print '<td>';
		print $pilot_item['pilot_count'];
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