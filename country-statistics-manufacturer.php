<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

$country = ucwords(str_replace("-", " ", $_GET['country']));

$spotter_array = Spotter::getSpotterDataByCountry($country, "0,1", $_GET['sort']);


if (!empty($spotter_array))
{
  $title = 'Most Common Aircraft Manufacturer from '.$country;
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
  	print '<h2>Most Common Aircraft Manufacturer</h2>';
  	print '<p>The statistic below shows the most common Aircraft Manufacturer of airlines or departure/arrival airports from <strong>'.$country.'</strong>.</p>';

	 $manufacturers_array = Spotter::countAllAircraftManufacturerByCountry($country);
	
	  if (!empty($manufacturers_array))
	  {
	    print '<div class="table-responsive">';
		    print '<table class="common-manufacturer table-striped">';
		      print '<thead>';
		        print '<th></th>';
		        print '<th>Aircraft Manufacturer</th>';
		        print '<th># of Times</th>';
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