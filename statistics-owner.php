<?php
require_once('require/class.Connection.php');
require_once('require/class.Stats.php');
$Stats = new Stats();
$title = _("Statistics").' - '._("Most common owners");
require_once('header.php');

include('statistics-sub-menu.php');
print '<script type="text/javascript" src="https://www.google.com/jsapi"></script>
	<div class="info">
		<h1>'._("Most common owner").'</h1>
	</div>
	<p>'._("Below are the <strong>Top 10</strong> most common owner.").'</p>';
 
	$owner_array = $Stats->countAllOwners();
	print '<div id="chart" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["'._("Owner").'", "'._("# of Times").'"], ';
            	$owner_data = '';
		foreach($owner_array as $owner_item)
		{
			$owner_data .= '[ "'.$owner_item['owner_name'].'",'.$owner_item['owner_count'].'],';
		}
		$owner_data = substr($owner_data, 0, -1);
		print $owner_data;
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

if (!empty($owner_array))
{
	print '<div class="table-responsive">';
	print '<table class="common-type table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th>'._("Owner Name").'</th>';
	print '<th>'._("# of Times").'</th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($owner_array as $owner_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		print '<td>';
		print $owner_item['owner_name'].'</a>';
		print '</td>';
		print '<td>';
		print $owner_item['owner_count'];
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