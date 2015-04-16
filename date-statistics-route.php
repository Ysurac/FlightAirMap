<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$spotter_array = Spotter::getSpotterDataByDate($_GET['date'],"0,1", $_GET['sort']);

if (!empty($spotter_array))
{
  
  
  $title = 'Most Common Routes on '.date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601']));
	require('header.php');
	
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
  	print '<h2>Most Common Routes</h2>';
  	print '<p>The statistic below shows the most common routes on <strong>'.date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601'])).'</strong>.</p>';
  	
      $route_array = Spotter::countAllRoutesByDate($_GET['date']);

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
                    print '<a href="'.$globalURL.'/search?start_date='.$_GET['date'].'+00:00&end_date='.$_GET['date'].'+23:59&departure_airport_route='.$route_item['airport_departure_icao'].'&arrival_airport_route='.$route_item['airport_arrival_icao'].'">Search Flights</a>';
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

	$title = "Unknown Date";
	require('header.php');
	
	print '<h1>Error</h1>';

  print '<p>Sorry, this date does not exist in this database. :(</p>'; 
}


?>

<?php
require('footer.php');
?>