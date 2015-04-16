<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$spotter_array = Spotter::getSpotterDataByAirport($_GET['airport'],"0,1","");
$airport_array = Spotter::getAllAirportInfo($_GET['airport']);

if (!empty($airport_array))
{
  $title = 'Most Common Aircraft Manufacturer to/from '.$airport_array[0]['city'].', '.$airport_array[0]['name'].' ('.$airport_array[0]['icao'].')';
	require('header.php');
  
  
  
  print '<div class="select-item">';
	print '<form action="'.$globalURL.'/airport" method="post">';
		print '<select name="airport" class="selectpicker" data-live-search="true">';
      print '<option></option>';
      $airport_names = Spotter::getAllAirportNames();
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
  	print '<h2>Most Common Aircraft Manufacturer</h2>';
  	print '<p>The statistic below shows the most common Aircraft Manufacturer of flights to/from <strong>'.$airport_array[0]['city'].', '.$airport_array[0]['name'].' ('.$airport_array[0]['icao'].')</strong>.</p>';

	  $manufacturers_array = Spotter::countAllAircraftManufacturerByAirport($_GET['airport']);
	
	  if (!empty($manufacturers_array))
	  {
	    print '<div class="table-responsive">';
		    print '<table class="common-manufacturer table-striped">';
		      print '<thead>';
		        print '<th></th>';
		        print '<th>Aircraft Manufacturer</th>';
		        print '<th># of Times</th>';
		        print '<th></th>';
		      print '</thead>';
		      print '<tbody>';
		      $i = 1;
		        foreach($manufacturers_array as $manufacturer_item)
		        {
		          print '<tr>';
		            print '<td><strong>'.$i.'</strong></td>';
		            print '<td>';
		              print '<a href="'.$globalURL.'/manufacturer/'.strtolower(str_replace(" ", "-", $manufacturer_item['aircraft_manufacturer'])).'">'.$manufacturer_item['aircraft_manufacturer'].'</a>';
		            print '</td>';
		            print '<td>';
		              print $manufacturer_item['aircraft_manufacturer_count'];
		            print '</td>';
		            print '<td><a href="'.$globalURL.'/search?manufacturer='.strtolower(str_replace(" ", "-", $manufacturer_item['aircraft_manufacturer'])).'&airport='.$_GET['airport'].'">Search flights</a></td>';
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