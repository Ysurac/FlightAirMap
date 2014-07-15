<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$spotter_array = Spotter::getSpotterDataByDate($_GET['date'],"0,1", $_GET['sort']);

if (!empty($spotter_array))
{
  date_default_timezone_set('America/Toronto');
  
  $title = 'Most Common Aircraft Manufacturer on '.date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601']));
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
  	print '<h2>Most Common Aircraft Manufacturer</h2>';
  	print '<p>The statistic below shows the most common Aircraft Manufacturer of flights on <strong>'.date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601'])).'</strong>.</p>';

	  $manufacturers_array = Spotter::countAllAircraftManufacturerByDate($_GET['date']);
	
	  if (!empty($manufacturers_array))
	  {
	    print '<div class="table-responsive">';
		    print '<table class="common-manufacturer">';
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
		            print '<td><a href="'.$globalURL.'/search?manufacturer='.strtolower(str_replace(" ", "-", $manufacturer_item['aircraft_manufacturer'])).'&start_date='.$_GET['date'].'+00:00&end_date='.$_GET['date'].'+23:59">Search flights</a></td>';
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