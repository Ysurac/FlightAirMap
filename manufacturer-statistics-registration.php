<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$manufacturer = ucwords(str_replace("-", " ", $_GET['aircraft_manufacturer']));

$spotter_array = Spotter::getSpotterDataByManufacturer($manufacturer,"0,1", $_GET['sort']);

if (!empty($spotter_array))
{
  $title = 'Most Common Aircraft by Registration from '.$manufacturer;
	require('header.php');
  
  date_default_timezone_set('America/Toronto');
  
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
  	print '<h2>Most Common Aircraft by Registration</h2>';
  	print '<p>The statistic below shows the most common aircraft by registration of flights from <strong>'.$manufacturer.'</strong>.</p>';

	  $aircraft_array = Spotter::countAllAircraftRegistrationByManufacturer($manufacturer);
	
	  if (!empty($aircraft_array))
	  {
	    print '<div class="table-responsive">';
		    print '<table class="common-type">';
		      print '<thead>';
		        print '<th></th>';
		        print '<th></th>';
		        print '<th>Registration</th>';
		        print '<th>Aircraft Type</th>';
		        print '<th># of Times</th>';
		        print '<th></th>';
		      print '</thead>';
		      print '<tbody>';
		      $i = 1;
		        foreach($aircraft_array as $aircraft_item)
		        {
		          print '<tr>';
		            print '<td><strong>'.$i.'</strong></td>';
		            if ($aircraft_item['image_thumbnail'] != "")
			    	 {
			    	 	print '<td class="aircraft_thumbnail">';
			    	 		print '<a href="'.$globalURL.'/registration/'.$aircraft_item['registration'].'"><img src="'.$aircraft_item['image_thumbnail'].'" alt="Click to see more information about this aircraft" title="Click to see more information about this aircraft" width="100px" /></a>';
			    	 	print '</td>';
			    	 } else {
			      	 print '<td class="aircraft_thumbnail">';
			      	 	print '<a href="'.$globalURL.'/registration/'.$aircraft_item['registration'].'"><img src="'.$globalURL.'/images/placeholder_thumb.png" alt="Click to see more information about this aircraft" title="Click to see more information about this aircraft" width="100px" /></a>';
			      	 print '</td>';
			    	 }
		            print '<td>';
		              print '<a href="'.$globalURL.'/registration/'.$aircraft_item['registration'].'">'.$aircraft_item['registration'].'</a>';
		            print '</td>';
		            print '<td>';
		              print '<a href="'.$globalURL.'/aircraft/'.$aircraft_item['aircraft_icao'].'">'.$aircraft_item['aircraft_name'].' ('.$aircraft_item['aircraft_icao'].')</a>';
		            print '</td>';
		            print '<td>';
		              print $aircraft_item['registration_count'];
		            print '</td>';
		            print '<td><a href="'.$globalURL.'/search?registration='.$aircraft_item['registration'].'&manufacturer='.$_GET['aircraft_manufacturer'].'">Search flights</a></td>';
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