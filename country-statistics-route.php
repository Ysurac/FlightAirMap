<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
if (!isset($_GET['country'])) {
        header('Location: '.$globalURL.'/country');
        die();
}
$Spotter = new Spotter();
$country = ucwords(str_replace("-", " ", $_GET['country']));

$spotter_array = $Spotter->getSpotterDataByCountry($country, "0,1", $_GET['sort']);


if (!empty($spotter_array))
{
  $title = 'Most Common Routes from '.$country;
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
  	print '<h2>Most Common Routes</h2>';
  	print '<p>The statistic below shows the most common routes of airports &amp; airlines from <strong>'.$country.'</strong>.</p>';
  	
      $route_array = $Spotter->countAllRoutesByCountry($country);
	  
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

	$title = "Country";
	require_once('header.php');
	
	print '<h1>Error</h1>';

  print '<p>Sorry, the country does not exist in this database. :(</p>';  
}


?>

<?php
require_once('footer.php');
?>