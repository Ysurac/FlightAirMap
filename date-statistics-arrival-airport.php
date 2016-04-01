<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
$Spotter = new Spotter();
$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
$spotter_array = $Spotter->getSpotterDataByDate($_GET['date'],"0,1", $sort);

if (!empty($spotter_array))
{
  
  
  $title = 'Most Common Arrival Airports on '.date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601']));
	require_once('header.php');

  print '<div class="select-item">';
  		print '<form action="'.$globalURL.'/date" method="post">';
  			print '<label for="date">Select a Date</label>';
    		print '<input type="text" id="date" name="date" value="'.$_GET['date'].'" size="8" readonly="readonly" class="custom" />';
    		print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
  		print '</form>';
  	print '</div>';
  
  print '<div class="info column">';
  	print '<h1>Flights from '.date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601'])).'</h1>';
  print '</div>';

  include('date-sub-menu.php');
  
  print '<div class="column">';
  	print '<h2>Most Common Arrival Airports</h2>';
  	
  	?>
  	<p>The statistic below shows all arrival airports of flights on <strong><?php print date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601'])); ?></strong>.</p>
  	<?php
    	 $airport_airport_array = $Spotter->countAllArrivalAirportsByDate($_GET['date']);
    	?>
    	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
    	<script>
    	google.load("visualization", "1", {packages:["geochart"]});
    	google.setOnLoadCallback(drawCharts);
    	$(window).resize(function(){
    		drawCharts();
    	});
    	function drawCharts() {
    
        var data = google.visualization.arrayToDataTable([ 
        	["Airport", "# of Times"],
        	<?php
        	$airport_data = '';
          foreach($airport_airport_array as $airport_item)
    			{
    				$name = $airport_item['airport_arrival_city'].', '.$airport_item['airport_arrival_country'].' ('.$airport_item['airport_arrival_icao'].')';
    				$name = str_replace("'", "", $name);
    				$name = str_replace('"', "", $name);
    				$airport_data .= '[ "'.$name.'",'.$airport_item['airport_arrival_icao_count'].'],';
    			}
    			$airport_data = substr($airport_data, 0, -1);
    			print $airport_data;
    			?>
        ]);
    
        var options = {
        	legend: {position: "none"},
        	chartArea: {"width": "80%", "height": "60%"},
        	height:500,
        	displayMode: "markers",
        	colors: ["#8BA9D0","#1a3151"]
        };
    
        var chart = new google.visualization.GeoChart(document.getElementById("chartAirport"));
        chart.draw(data, options);
      }
    	</script>

      <div id="chartAirport" class="chart" width="100%"></div>
      
    	<?php
         print '<div class="table-responsive">';
             print '<table class="common-airport table-striped">';
              print '<thead>';
                print '<th></th>';
                print '<th>Airport</th>';
                print '<th>Country</th>';
                print '<th># of times</th>';
                print '<th></th>';
              print '</thead>';
              print '<tbody>';
              $i = 1;
                foreach($airport_airport_array as $airport_item)
                {
                  print '<tr>';
                  	print '<td><strong>'.$i.'</strong></td>';
                    print '<td>';
                      print '<a href="'.$globalURL.'/airport/'.$airport_item['airport_arrival_icao'].'">'.$airport_item['airport_arrival_city'].', '.$airport_item['airport_arrival_country'].' ('.$airport_item['airport_arrival_icao'].')</a>';
                    print '</td>';
                    print '<td>';
                      print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airport_item['airport_arrival_country'])).'">'.$airport_item['airport_arrival_country'].'</a>';
                    print '</td>';
                    print '<td>';
                      print $airport_item['airport_arrival_icao_count'];
                    print '</td>';
                    print '<td><a href="'.$globalURL.'/search?arrival_airport_route='.$airport_item['airport_arrival_icao'].'&start_date='.$_GET['date'].'+00:00&end_date='.$_GET['date'].'+23:59">Search flights</a></td>';
                  print '</tr>';
                  $i++;
                }
              print '<tbody>';
            print '</table>';
        print '</div>';
      ?>
  	<?php
  print '</div>';
  
  
} else {

	$title = "Unknown Date";
	require_once('header.php');
	
	print '<h1>Error</h1>';

  print '<p>Sorry, this date does not exist in this database. :(</p>';  
}


?>

<?php
require_once('footer.php');
?>