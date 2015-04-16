<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$manufacturer = ucwords(str_replace("-", " ", $_GET['aircraft_manufacturer']));

$spotter_array = Spotter::getSpotterDataByManufacturer($manufacturer,"0,1", $_GET['sort']);

if (!empty($spotter_array))
{
  $title = 'Most Common Routes from '.$manufacturer;
	require('header.php');
  
  
  
  print '<div class="select-item">';
	print '<form action="'.$globalURL.'/manufacturer" method="post">';
		print '<select name="aircraft_manufacturer" class="selectpicker" data-live-search="true">';
      print '<option></option>';
      $all_manufacturers = Spotter::getAllManufacturers();
      foreach($all_manufacturers as $all_manufacturer)
      {
        if($_GET['aircraft_manufacturer'] == strtolower(str_replace(" ", "-", $all_manufacturer['aircraft_manufacturer'])))
        {
          print '<option value="'.strtolower(str_replace(" ", "-", $all_manufacturer['aircraft_manufacturer'])).'" selected="selected">'.$all_manufacturer['aircraft_manufacturer'].'</option>';
        } else {
          print '<option value="'.strtolower(str_replace(" ", "-", $all_manufacturer['aircraft_manufacturer'])).'">'.$all_manufacturer['aircraft_manufacturer'].'</option>';
        }
      }
    print '</select>';
	print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
	print '</form>';
  print '</div>';
	
	print '<div class="info column">';
  	print '<h1>'.$manufacturer.'</h1>';
  print '</div>';

  include('manufacturer-sub-menu.php');
  
  print '<div class="column">';
  	print '<h2>Most Common Routes</h2>';
  	print '<p>The statistic below shows the most common routes from <strong>'.$manufacturer.'</strong>.</p>';
  	
      $route_array = Spotter::countAllRoutesByManufacturer($manufacturer);
	  
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
                    print '<a href="'.$globalURL.'/search?manufacturer='.$_GET['aircraft_manufacturer'].'&departure_airport_route='.$route_item['airport_departure_icao'].'&arrival_airport_route='.$route_item['airport_arrival_icao'].'">Search Flights</a>';
                  print '</td>';
                  print '<td>';
                      print '<a href="'.$globalURL.'/route/'.$route_item['airport_departure_icao'].'/'.$route_item['airport_arrival_icao'].'">Route History</a>';
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

	$title = "Manufacturer";
	require('header.php');
	
	print '<h1>Error</h1>';

   print '<p>Sorry, the aircraft manufacturer does not exist in this database. :(</p>';
}


?>

<?php
require('footer.php');
?>