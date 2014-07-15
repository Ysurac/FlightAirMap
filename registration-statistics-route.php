<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$spotter_array = Spotter::getSpotterDataByRegistration($_GET['registration'], "0,1", $_GET['sort']);
$aircraft_array = Spotter::getAircraftInfoByRegistration($_GET['registration']);


if (!empty($spotter_array))
{
  $title = 'Most Common Routes from aircraft with registration '.$_GET['registration'];
	require('header.php');
  
  date_default_timezone_set('America/Toronto');
  
	print '<div class="info column">';
		print '<h1>'.$_GET['registration'].' - '.$aircraft_array[0]['aircraft_name'].' ('.$aircraft_array[0]['aircraft_icao'].')</h1>';
		print '<div><span class="label">Name</span><a href="'.$globalURL.'/aircraft/'.$aircraft_array[0]['aircraft_icao'].'">'.$aircraft_array[0]['aircraft_name'].'</a></div>';
		print '<div><span class="label">ICAO</span><a href="'.$globalURL.'/aircraft/'.$aircraft_array[0]['aircraft_icao'].'">'.$aircraft_array[0]['aircraft_icao'].'</a></div>'; 
		print '<div><span class="label">Manufacturer</span><a href="'.$globalURL.'/manufacturer/'.strtolower(str_replace(" ", "-", $aircraft_array[0]['aircraft_manufacturer'])).'">'.$aircraft_array[0]['aircraft_manufacturer'].'</a></div>';
	print '</div>';
	
	if ($spotter_array[0]['highlight'] != "")
	{
		print '<div class="alert alert-warning">'.$spotter_array[0]['highlight'].'</div>';
	}
	
	include('registration-sub-menu.php');
  
  print '<div class="column">';
  	print '<h2>Most Common Routes</h2>';
  	print '<p>The statistic below shows the most common routes of aircraft with registration <strong>'.$_GET['registration'].'</strong>.</p>';
  	
      $route_array = Spotter::countAllRoutesByRegistration($_GET['registration']);
	  
	  if (!empty($route_array))
    {
       print '<div class="table-responsive">';
           print '<table class="common-routes">';
            print '<thead>';
              print '<th></th>';
              print '<th>Departure Airport</th>';
              print '<th>Arrival Airport</th>';
              print '<th># of Times</th>';
							print '<th></th>';
            print '</thead>';
            print '<tbody>';
            $i = 1;
              foreach($route_array as $route_item)
              {
                print '<tr>';
                	print '<td><strong>'.$i.'</strong></td>';
                  print '<td>';
                    print '<a href="'.$globalURL.'/airport/'.$route_item['airport_departure_icao'].'">'.$route_item['airport_departure_city'].', '.$route_item['airport_departure_country'].' ('.$route_item['airport_departure_icao'].')</a>';
                  print '</td>';
      						print '<td>';
                    print '<a href="'.$globalURL.'/airport/'.$route_item['airport_arrival_icao'].'">'.$route_item['airport_arrival_city'].', '.$route_item['airport_arrival_country'].' ('.$route_item['airport_arrival_icao'].')</a>';
                  print '</td>';
                  print '<td>';
                    print $route_item['route_count'];
                  print '</td>';
                  print '<td>';
                      print '<a href="'.$globalURL.'/route/'.$route_item['airport_departure_icao'].'/'.$route_item['airport_arrival_icao'].'">Route Profile</a>';
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

	$title = "Registration";
	require('header.php');
	
	print '<h1>Error</h1>';

  print '<p>Sorry, this registration does not exist in this database. :(</p>'; 
}


?>

<?php
require('footer.php');
?>