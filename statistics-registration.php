<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$title = "Statistic - Most common Aircraft Registrations";
require('header.php');
?>

<?php include('statistics-sub-menu.php'); ?>

	 	<div class="info">
		  	<h1>Most common Aircraft Registrations</h1>
		 </div>
    
    	<p>Below are the <strong>Top 10</strong> most common aircraft registrations.</p>
      
      <?php
      $registration_array = Spotter::countAllAircraftRegistrations();
      
      print '<div id="chart" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["Aircraft Manufacturer", "# of Times"], ';
              foreach($registration_array as $registration_item)
    					{
	    						$registration_data .= '[ "'.$registration_item['registration'].' - '.$registration_item['aircraft_name'].' ('.$registration_item['aircraft_icao'].')",'.$registration_item['aircraft_registration_count'].'],';
    					}
    					$registration_data = substr($registration_data, 0, -1);
    					print $registration_data;
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
      if (!empty($registration_array))
      {
        print '<div class="table-responsive">';
            print '<table class="common-registration">';
              print '<thead>';
              	print '<th></th>';
              	print '<th></th>';
                print '<th>Registration</th>';
                print '<th>Aircraft</th>';
                print '<th># of Times</th>';
              print '</thead>';
              print '<tbody>';
              $i = 1;
                foreach($registration_array as $registration_item)
                {
                  print '<tr>';
                  	print '<td><strong>'.$i.'</strong></td>';
                  	if ($registration_item['image_thumbnail'] != "")
						    	 {
						    	 	print '<td class="aircraft_thumbnail">';
						    	 		print '<a href="'.$globalURL.'/registration/'.$registration_item['registration'].'"><img src="'.$registration_item['image_thumbnail'].'" data-toggle="popover" title="'.$registration_item['registration'].' - '.$registration_item['aircraft_icao'].' - '.$registration_item['airline_name'].'" alt="'.$registration_item['registration'].' - '.$registration_item['airline_name'].'" data-content="Registration: '.$registration_item['registration'].'<br />Aircraft: '.$registration_item['aircraft_name'].' ('.$registration_item['aircraft_icao'].')<br />Airline: '.$registration_item['airline_name'].'" data-html="true" width="100px" /></a>';
						    	 	print '</td>';
						    	 } else {
						      	 print '<td class="aircraft_thumbnail">';
						      	 	print '<a href="'.$globalURL.'/registration/'.$registration_item['registration'].'"><img src="'.$globalURL.'/images/placeholder_thumb.png" data-toggle="popover" title="'.$registration_item['registration'].' - '.$registration_item['aircraft_icao'].' - '.$registration_item['airline_name'].'" alt="'.$registration_item['registration'].' - '.$registration_item['airline_name'].'" data-content="Registration: '.$registration_item['registration'].'<br />Aircraft: '.$registration_item['aircraft_name'].' ('.$registration_item['aircraft_icao'].')<br />Airline: '.$registration_item['airline_name'].'" data-html="true" width="100px" /></a>';
						      	 print '</td>';
						    	 }
                    print '<td>';
                      print '<a href="'.$globalURL.'/registration/'.$registration_item['registration'].'">'.$registration_item['registration'].'</a>';
                    print '</td>';
                    print '<td>';
                      print '<a href="'.$globalURL.'/aircraft/'.$registration_item['aircraft_icao'].'">'.$registration_item['aircraft_name'].' ('.$registration_item['aircraft_icao'].')</a>';
                    print '</td>';
                    print '<td>';
                      print $registration_item['aircraft_registration_count'];
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