<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$spotter_array = Spotter::getSpotterDataByDate($_GET['date'],"0,1", $_GET['sort']);

if (!empty($spotter_array))
{
  date_default_timezone_set('America/Toronto');
  
  $title = 'Most Common Aircraft by Registration on '.date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601']));
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
  	print '<h2>Most Common Aircraft by Registration</h2>';
  	print '<p>The statistic below shows the most common aircraft by registration of flights on <strong>'.date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601'])).'</strong>.</p>';

	  $aircraft_array = Spotter::countAllAircraftRegistrationByDate($_GET['date']);
	
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
		            print '<td><a href="'.$globalURL.'/search?registration='.$aircraft_item['registration'].'&start_date='.$_GET['date'].'+00:00&end_date='.$_GET['date'].'+23:59">Search flights</a></td>';
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