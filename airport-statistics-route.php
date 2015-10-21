<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');
if (!isset($_GET['airport'])) {
        header('Location: '.$globalURL.'/airport');
        die();
}
$Spotter = new Spotter();
$spotter_array = $Spotter->getSpotterDataByAirport($_GET['airport'],"0,1","");
$airport_array = $Spotter->getAllAirportInfo($_GET['airport']);

if (!empty($airport_array))
{
  $title = 'Most Common Routes to/from '.$airport_array[0]['city'].', '.$airport_array[0]['name'].' ('.$airport_array[0]['icao'].')';
	require('header.php');
  
  
  
  print '<div class="select-item">';
	print '<form action="'.$globalURL.'/airport" method="post">';
		print '<select name="airport" class="selectpicker" data-live-search="true">';
      print '<option></option>';
      $airport_names = $Spotter->getAllAirportNames();
      ksort($airport_names);
      foreach($airport_names as $airport_name)
      {
        if($_GET['airport'] == $airport_name['airport_icao'])
        {
          print '<option value="'.$airport_name['airport_icao'].'" selected="selected">'.$airport_name['airport_city'].', '.$airport_name['airport_name'].', '.$airport_name['airport_country'].' ('.$airport_name['airport_icao'].')</option>';
        } else {
          print '<option value="'.$airport_name['airport_icao'].'">'.$airport_name['airport_city'].', '.$airport_name['airport_name'].', '.$airport_name['airport_country'].' ('.$airport_name['airport_icao'].')</option>';
        }
      }
    print '</select>';
	print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
	print '</form>';
  print '</div>';
	
	if ($_GET['airport'] != "NA")
		{
	    print '<div class="info column">';
	    	print '<h1>'.$airport_array[0]['city'].', '.$airport_array[0]['name'].' ('.$airport_array[0]['icao'].')</h1>';
	    	print '<div><span class="label">Name</span>'.$airport_array[0]['name'].'</div>';
    	print '<div><span class="label">City</span>'.$airport_array[0]['city'].'</div>';
    	print '<div><span class="label">Country</span>'.$airport_array[0]['country'].'</div>';
    	print '<div><span class="label">ICAO</span>'.$airport_array[0]['icao'].'</div>';
    	print '<div><span class="label">IATA</span>'.$airport_array[0]['iata'].'</div>';
    	print '<div><span class="label">Altitude</span>'.$airport_array[0]['altitude'].'</div>';
    	print '<div><span class="label">Coordinates</span><a href="http://maps.google.ca/maps?z=10&t=k&q='.$airport_array[0]['latitude'].','.$airport_array[0]['longitude'].'" target="_blank">Google Map<i class="fa fa-angle-double-right"></i></a></div>';
	    print '</div>';
	  } else {
	    print '<div class="alert alert-warning">This special airport profile shows all flights that do <u>not</u> have a departure and/or arrival airport associated with them.</div>';
	  }

  include('airport-sub-menu.php');
  
  print '<div class="column">';
  	print '<h2>Most Common Routes</h2>';
  	print '<p>The statistic below shows the most common routes to/from <strong>'.$airport_array[0]['city'].', '.$airport_array[0]['name'].' ('.$airport_array[0]['icao'].')</strong>.</p>';
  	
      $route_array = $Spotter->countAllRoutesByAirport($_GET['airport']);

	  if (!empty($route_array))
    {
       print '<div class="table-responsive">';
           print '<table class="common-routes table-striped">';
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

	$title = "Airport";
	require('header.php');
	
	print '<h1>Error</h1>';

   print '<p>Sorry, the airport does not exist in this database. :(</p>';
}


?>

<?php
require('footer.php');
?>