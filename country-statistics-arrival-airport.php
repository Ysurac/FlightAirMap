<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
if (!isset($_GET['country'])) {
        header('Location: '.$globalURL.'/country');
        die();
}
$Spotter = new Spotter();
$country = ucwords(str_replace("-", " ", $_GET['country']));
$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
$spotter_array = $Spotter->getSpotterDataByCountry($country, "0,1", $sort);


if (!empty($spotter_array))
{
  $title = 'Most Common Arrival Airports from '.$country;
	require_once('header.php');
  
  
  
  print '<div class="select-item">';
	print '<form action="'.$globalURL.'/country" method="post">';
		print '<select name="country" class="selectpicker" data-live-search="true">';
      print '<option></option>';
      $all_countries = $Spotter->getAllCountries();
      foreach($all_countries as $all_country)
      {
        if($country == $all_country['country'])
        {
          print '<option value="'.strtolower(str_replace(" ", "-", $all_country['country'])).'" selected="selected">'.$all_country['country'].'</option>';
        } else {
          print '<option value="'.strtolower(str_replace(" ", "-", $all_country['country'])).'">'.$all_country['country'].'</option>';
        }
      }
    print '</select>';
	print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
	print '</form>';
  print '</div>';
  
  if ($_GET['country'] != "NA")
  {
	print '<div class="info column">';
		print '<h1>Airports &amp; Airlines from '.$country.'</h1>';
	print '</div>';
  } else {
	  print '<div class="alert alert-warning">This special country profile shows all flights that do <u>not</u> have a country of a airline or departure/arrival airport associated with them.</div>';
  }
	
	include('country-sub-menu.php');
  
  print '<div class="column">';
  	print '<h2>Most Common Arrival Airports</h2>';
  	
  	?>
  	 <p>The statistic below shows all arrival airports of flights of airports &amp; airlines from <strong><?php print $country; ?></strong>.</p>
  	<?php
    	 $airport_airport_array = $Spotter->countAllArrivalAirportsByCountry($country);
    	?>
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

	$title = "Country";
	require_once('header.php');
	
	print '<h1>Error</h1>';

  print '<p>Sorry, the country does not exist in this database. :(</p>';   
}


?>

<?php
require_once('footer.php');
?>