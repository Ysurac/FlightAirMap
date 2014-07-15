<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$title = "Statistic - Most busiest Time of the Day";
require('header.php');
?>

<?php include('statistics-sub-menu.php'); ?>

		<div class="info">
	  	<h1>Most busiest Time of the Day</h1>
	  </div>
      
       <p>Below is a list of the most common <strong>time of day</strong>.</p>
      
      <?php
      $hour_array = Spotter::countAllHours('hour');
      
      print '<div id="chartHour" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["Hour", "# of Flights"], ';
              foreach($hour_array as $hour_item)
    					{
    						$hour_data .= '[ "'.date("ga", strtotime($hour_item['hour_name'].":00")).'",'.$hour_item['hour_count'].'],';
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
      ?>
      
      <?php
    	$hour_array = Spotter::countAllHours('count');
      if (!empty($hour_array))
      {
        print '<div class="table-responsive">';
            print '<table class="common-hour">';
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
                    print '<td>';
                      print date("g a", strtotime($hour_item['hour_name'].":00"));
                    print '</td>';
                    print '<td>';
                      print $hour_item['hour_count'];
                    print '</td>';
                  print '</tr>';
                  $i++;
                }
               print '<tbody>';
            print '</table>';
        print '</div>';
      }
      ?>

<?php
require('footer.php');
?>