<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$sort = '';
if (isset($_GET['sort'])) $sort = $_GET['sort'];
$spotter_array = Spotter::getSpotterDataByIdent($_GET['ident'],"0,1", $sort);

if (!empty($spotter_array))
{
    $title = 'Most Common Airlines of '.$spotter_array[0]['ident'];
	require('header.php');
    
    
    
    print '<div class="info column">';
  		print '<h1>'.$spotter_array[0]['ident'].'</h1>';
  		print '<div><span class="label">Ident</span>'.$spotter_array[0]['ident'].'</div>';
  		print '<div><span class="label">Airline</span><a href="'.$globalURL.'/airline/'.$spotter_array[0]['airline_icao'].'">'.$spotter_array[0]['airline_name'].'</a></div>'; 
  		print '<div><span class="label">Flight History</span><a href="http://flightaware.com/live/flight/'.$spotter_array[0]['ident'].'" target="_blank">View the Flight History of this callsign</a></div>';       
	print '</div>';

	include('ident-sub-menu.php');
  
  print '<div class="column">';
  	print '<h2>Most Common Airlines</h2>';
  	print '<p>The statistic below shows the most common airlines of flights using the ident/callsign <strong>'.$spotter_array[0]['ident'].'</strong>.</p>';

	  $airline_array = Spotter::countAllAirlinesByIdent($_GET['ident']);
	  
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
      				      	if ($globalIVAO && @getimagesize($globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.gif'))
      				      	{
      				      		print $globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.gif';
      				      	} elseif (@getimagesize($globalURL.'/images/airlines/'.$airline_item['airline_icao'].'.png'))
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
                  print '<td><a href="'.$globalURL.'/search?airline='.$airline_item['airline_icao'].'&callsign='.$_GET['ident'].'">Search flights</a></td>';
                print '</tr>';
                $i++;
              }
             print '<tbody>';
          print '</table>';
      print '</div>';
    }
  print '</div>';
  
  
} else {

	$title = "Ident";
	require('header.php');
	
	print '<h1>Error</h1>';

  print '<p>Sorry, this ident/callsign is not in the database. :(</p>'; 
}


?>

<?php
require('footer.php');
?>