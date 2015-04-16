<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$manufacturer = ucwords(str_replace("-", " ", $_GET['aircraft_manufacturer']));

$spotter_array = Spotter::getSpotterDataByManufacturer($manufacturer,"0,1", $_GET['sort']);

if (!empty($spotter_array))
{
  $title = 'Most Common Airlines from '.$manufacturer;
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
  	print '<h2>Most Common Airlines</h2>';
  	print '<p>The statistic below shows the most common airlines of flights from <strong>'.$manufacturer.'</strong>.</p>';

	  $airline_array = Spotter::countAllAirlinesByManufacturer($manufacturer);
	  
	  if (!empty($airline_array))
    {
      print '<div class="table-responsive">';
          print '<table class="common-airline table-striped">';
            print '<thead>';
            	print '<th></th>';
            	print '<th></th>';
              print '<th>Airline</th>';
              print '<th>Country</th>';
              print '<th># of times</th>';
              print '<th></th>';
            print '</thead>';
            print '<tbody>';
            $i = 1;
              foreach($airline_array as $airline_item)
              {
                print '<tr>';
                print '<td><strong>'.$i.'</strong></td>';
                print '<td class="logo">';
      			      		print '<a href="'.$globalURL.'/airline/'.$airline_item['airline_icao'].'"><img src="';
      				      	if (@getimagesize($globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.png'))
      				      	{
      				      		print $globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.png';
      				      	} else {
      				      		print $globalURL.'/images/airlines/placeholder.png';
      				      	}
      				      	print '" /></a>';
      			      	print '</td>';
                  print '<td>';
                    print '<a href="'.$globalURL.'/airline/'.$airline_item['airline_icao'].'">'.$airline_item['airline_name'].' ('.$airline_item['airline_icao'].')</a>';
                  print '</td>';
                  print '<td>';
                    print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airline_item['airline_country'])).'">'.$airline_item['airline_country'].'</a>';
                  print '</td>';
                  print '<td>';
                    print $airline_item['airline_count'];
                  print '</td>';
                  print '<td><a href="'.$globalURL.'/search?airline='.$airline_item['airline_icao'].'&manufacturer='.$manufacturer.'">Search flights</a></td>';
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