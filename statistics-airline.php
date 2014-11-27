<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$title = "Statistic - Most common Airline";
require('header.php');
?>

<?php include('statistics-sub-menu.php'); ?>

		<div class="info">
	  	<h1>Most common Airline</h1>
	  </div>
    
    	<p>Below are the <strong>Top 10</strong> most common airlines.</p>
      
      <?php
      $airline_array = Spotter::countAllAirlines();
      
      print '<div id="chart" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["Airline", "# of Times"], ';
              foreach($airline_array as $airline_item)
    					{
	    						$airline_data .= '[ "'.$airline_item['airline_name'].' ('.$airline_item['airline_icao'].')",'.$airline_item['airline_count'].'],';
    					}
    					$airline_data = substr($airline_data, 0, -1);
    					print $airline_data;
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
      if (!empty($airline_array))
      {
        print '<div class="table-responsive">';
            print '<table class="common-airline table-striped">';
              print '<thead>';
              	print '<th></th>';
              	print '<th></th>';
                print '<th>Airline</th>';
                print '<th>Country</th>';
                print '<th># of times</th>';
              print '</thead>';
              print '<tbody>';
              $i = 1;
                foreach($airline_array as $airline_item)
                {
                  print '<tr>';
                  print '<td><strong>'.$i.'</strong></td>';
                  print '<td class="logo">';
        			      		print '<a href="'.$globalURL.'/airline/'.$airline_item['airline_icao'].'"><img src="';
        				      	if (@getimagesize($globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.png'))
        				      	{
        				      		print $globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.png';
        				      	} else {
        				      		print $globalURL.'/images/airlines/placeholder.png';
        				      	}
        				      	print '" /></a>';
        			      	print '</td>';
                    print '<td>';
                      print '<a href="'.$globalURL.'/airline/'.$airline_item['airline_icao'].'">'.$airline_item['airline_name'].' ('.$airline_item['airline_icao'].')</a>';
                    print '</td>';
                    print '<td>';
                      print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airline_item['airline_country'])).'">'.$airline_item['airline_country'].'</a>';
                    print '</td>';
                    print '<td>';
                      print $airline_item['airline_count'];
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