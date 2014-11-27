<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$country = ucwords(str_replace("-", " ", $_GET['country']));

$spotter_array = Spotter::getSpotterDataByCountry($country, "0,1", $_GET['sort']);


if (!empty($spotter_array))
{
  $title = 'Most Common Departure Airports by Country from '.$country;
	require('header.php');
  
  date_default_timezone_set('America/Toronto');
  
  print '<div class="select-item">';
	print '<form action="'.$globalURL.'/country" method="post">';
		print '<select name="country" class="selectpicker" data-live-search="true">';
      print '<option></option>';
      $all_countries = Spotter::getAllCountries();
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
  	print '<h2>Most Common Departure Airports by Country</h2>';
  	
  	?>
  	<p>The statistic below shows all departure airports by Country of origin of flights of airports &amp; airlines from <strong><?php print $country; ?></strong>.</p>
  	<?php
    	 $airport_country_array = Spotter::countAllDepartureAirportCountriesByCountry($country);
      
      print '<div id="chartCountry" class="chart" width="100%"></div>
      	<script> 
      		google.load("visualization", "1", {packages:["geochart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
            	["Country", "# of Times"], ';
              foreach($airport_country_array as $airport_item)
    					{
    						$country_data .= '[ "'.$airport_item['departure_airport_country'].'",'.$airport_item['airport_departure_country_count'].'],';
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

      if (!empty($airport_country_array))
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
                foreach($airport_country_array as $airport_item)
                {
                  print '<tr>';
                  	print '<td><strong>'.$i.'</strong></td>';
                    print '<td>';
                      print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airport_item['departure_airport_country'])).'">'.$airport_item['departure_airport_country'].'</a>';
                    print '</td>';
                    print '<td>';
                      print $airport_item['airport_departure_country_count'];
                    print '</td>';
                  print '</tr>';
                  $i++;
                }
               print '<tbody>';
            print '</table>';
        print '</div>';
      }
  print '</div>';

  
  
} else {

	$title = "Country";
	require('header.php');
	
	print '<h1>Error</h1>';

  print '<p>Sorry, the country does not exist in this database. :(</p>';  
}


?>

<?php
require('footer.php');
?>