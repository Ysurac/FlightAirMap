<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$title = "Statistic - Most common Airline by Country";
require('header.php');
?>

<?php include('statistics-sub-menu.php'); ?>

 		<div class="info">
	  	<h1>Most common Airline by Country</h1>
	  </div>

      <p>Below are the <strong>Top 10</strong> countries that an airline belongs to.</p>
      
      <?php
      $airline_array = Spotter::countAllAirlineCountries();
      
      print '<div id="chartCountry" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["geochart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["Country", "# of Times"], ';
              foreach($airline_array as $airline_item)
    					{
    						$country_data .= '[ "'.$airline_item['airline_country'].'",'.$airline_item['airline_country_count'].'],';
    					}
    					$country_data = substr($country_data, 0, -1);
    					print $country_data;
            print ']);
    
            var options = {
            	legend: {position: "none"},
            	chartArea: {"width": "80%", "height": "60%"},
            	height:500,
            	colors: ["#8BA9D0","#1a3151"]
            };
    
            var chart = new google.visualization.GeoChart(document.getElementById("chartCountry"));
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
            print '<table class="common-country table-striped">';
              print '<thead>';
              	print '<th></th>';
                print '<th>Country</th>';
                print '<th># of times</th>';
              print '</thead>';
              print '<tbody>';
              $i = 1;
                foreach($airline_array as $airline_item)
                {
                  print '<tr>';
                  	print '<td><strong>'.$i.'</strong></td>';
                    print '<td>';
                      print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airline_item['airline_country'])).'">'.$airline_item['airline_country'].'</a>';
                    print '</td>';
                    print '<td>';
                      print $airline_item['airline_country_count'];
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