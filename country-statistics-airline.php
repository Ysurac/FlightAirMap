<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$country = ucwords(str_replace("-", " ", $_GET['country']));

$spotter_array = Spotter::getSpotterDataByCountry($country, "0,1", $_GET['sort']);


if (!empty($spotter_array))
{
  $title = 'Most Common Airlines of '.$country;
	require('header.php');
  
  date_default_timezone_set('America/Toronto');
  
  print '<div class="select-item">';
	print '<form action="'.$globalURL.'/country" method="post">';
		print '<select name="country" class="selectpicker" data-live-search="true">';
      print '<option></option>';
      $all_countries = Spotter::getAllCountries();
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
  	print '<h2>Most Common Airlines</h2>';
  	print '<p>The statistic below shows the most common airlines of flights from airports &amp; airlines of <strong>'.$country.'</strong>.</p>';

	  $airline_array = Spotter::countAllAirlinesByCountry($country);
	  
	  if (!empty($airline_array))
    {
      print '<div class="table-responsive">';
          print '<table class="common-airline">';
            print '<thead>';
            	print '<th></th>';
            	print '<th></th>';
              print '<th>Airline</th>';
              print '<th>Country</th>';
              print '<th># of times</th>';
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
	require('header.php');
	
	print '<h1>Error</h1>';

  print '<p>Sorry, the country does not exist in this database. :(</p>';  
}


?>

<?php
require('footer.php');
?>