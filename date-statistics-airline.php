<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$spotter_array = Spotter::getSpotterDataByDate($_GET['date'],"0,1", $_GET['sort']);

if (!empty($spotter_array))
{
  date_default_timezone_set('America/Toronto');
  
  $title = 'Most Common Airlines on '.date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601']));
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
  	print '<h2>Most Common Airlines</h2>';
  	print '<p>The statistic below shows the most common airlines of flights on <strong>'.date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601'])).'</strong>.</p>';

	  $airline_array = Spotter::countAllAirlinesByDate($_GET['date']);
	 
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
                  print '<td><a href="'.$globalURL.'/search?airline='.$airline_item['airline_icao'].'&start_date='.$_GET['date'].'+00:00&end_date='.$_GET['date'].'+23:59">Search flights</a></td>';
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