<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');
require('require/class.Stats.php');
$Spotter = new Spotter();
$Stats = new Stats();
$title = "Statistic - Most common owners";
require('header.php');
?>

<?php include('statistics-sub-menu.php'); ?>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
 <div class="info">
	  	<h1>Most common owner</h1>
	  </div>

		<p>Below are the <strong>Top 10</strong> most common owner.</p>
	  
	  <?php
	  $owner_array = $Stats->countAllOwners();
	  
		print '<div id="chart" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["owner", "# of Times"], ';
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
      ?>

<?php
	if (!empty($owner_array))
	{
		print '<div class="table-responsive">';
		print '<table class="common-type table-striped">';
		print '<thead>';
		print '<th></th>';
		print '<th>owner Name</th>';
		print '<th># of Times</th>';
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
require('footer.php');
?>