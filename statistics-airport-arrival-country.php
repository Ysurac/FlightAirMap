<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$title = "Statistic - Most common Arrival Airport by Country";
require('header.php');
?>

<?php include('statistics-sub-menu.php'); ?>

		<div class="info">
	  	<h1>Most common Arrival Airport by Country</h1>
	  </div>
    
    	 <p>Below are the <strong>Top 10</strong> most common countries of all the arrival airports.</p>
    
    	<?php
    	 $airport_country_array = Spotter::countAllArrivalCountries();
    	?>
    
    	<script>
    	google.load("visualization", "1", {packages:["geochart"]});
    	google.setOnLoadCallback(drawCharts);
    	$(window).resize(function(){
    		drawCharts();
    	});
    	function drawCharts() {
        
        var data = google.visualization.arrayToDataTable([ 
        	["Country", "# of Times"],
          <?php
          foreach($airport_country_array as $airport_item)
    			{
    				$country_data .= '[ "'.$airport_item['airport_arrival_country'].'",'.$airport_item['airport_arrival_country_count'].'],';
    			}
    			$country_data = substr($country_data, 0, -1);
    			print $country_data;
    			?>
        ]);
    
        var options = {
        	legend: {position: "none"},
        	chartArea: {"width": "80%", "height": "60%"},
        	height:500,
        	colors: ["#8BA9D0","#1a3151"]
        };
    
        var chartCountry = new google.visualization.GeoChart(document.getElementById("chartCountry"));
        chartCountry.draw(data, options);
      }
    	</script>
    
    	<div id="chartCountry" class="chart" width="100%"></div>
    	
    	<?php
        print '<div class="table-responsive">';
            print '<table class="common-country">';
              print '<thead>';
                print '<th></th>';
                print '<th>Country</th>';
                print '<th># of times</th>';
              print '</thead>';
              print '<tbody>';
              $i = 1;
                foreach($airport_country_array as $airport_item)
                {
                  print '<tr>';
                    print '<td><strong>'.$i.'</strong></td>';
                    print '<td>';
                      print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airport_item['airport_arrival_country'])).'">'.$airport_item['airport_arrival_country'].'</a>';
                    print '</td>';
                    print '<td>';
                      print $airport_item['airport_arrival_country_count'];
                    print '</td>';
                  print '</tr>';
                  $i++;
                }
               print '<tbody>';
            print '</table>';
        print '</div>';
      ?>

<?php
require('footer.php');
?>