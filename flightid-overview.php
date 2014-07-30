<?php
if ($_GET['id'] == "")
{
	header('Location: /');
}

require('require/class.Connection.php');
require('require/class.Spotter.php');

$spotter_array = Spotter::getSpotterDataByID($_GET['id']);


if (!empty($spotter_array))
{
    if($spotter_array[0]['ident'] != "")
    {
    	$title .= $spotter_array[0]['ident'];
    }
    if($spotter_array[0]['airline_name'] != "")
    {
    	$title .= ' - '.$spotter_array[0]['airline_name'];
    }
    if($spotter_array[0]['aircraft_name'] != "")
    {
    	$title .= ' - '.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].')';
    }
    if($spotter_array[0]['registration'] != "")
    {
    	$title .= ' - '.$spotter_array[0]['registration'];
    }
    $facebook_meta_image = $spotter_array[0]['image'];
	require('header.php');
    
    	print '<div class="info column">';
    	    print '<h1>';
	    	    if($spotter_array[0]['ident'] != "")
	    	    {
	    	    	print $spotter_array[0]['ident'];
	    	    }
	    	    if($spotter_array[0]['airline_name'] != "")
	    	    {
	    	    	print ' - '.$spotter_array[0]['airline_name'];
	    	    }
	    	    if($spotter_array[0]['aircraft_name'] != "")
	    	    {
	    	    	print ' - '.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].')';
	    	    }
	    	    if($spotter_array[0]['registration'] != "")
	    	    {
	    	    	print ' - '.$spotter_array[0]['registration'];
	    	    }
    	    print '</h1>';
     print '</div>';
    
        if ($spotter_array[0]['registration'] != "")
        {
            $highlight = Spotter::getHighlightByRegistration($spotter_array[0]['registration']);
            if ($highlight != "")
            {
             print '<div class="alert alert-warning">'.$highlight.'</div>';
            }
        }
        	
		 include('flightid-sub-menu.php');
		 
		 print '<div class="clear column">';
		
			print '<div class="col-sm-4 details">';
		 
		 	foreach($spotter_array as $spotter_item)
		  {
		  	print '<div class="detail">';
		  	if (@getimagesize($globalURL.'/images/airlines/'.$spotter_item['airline_icao'].'.png'))
	  		{
	    			print '<a href="'.$globalURL.'/airline/'.$spotter_item['airline_icao'].'"><img src="'.$globalURL.'/images/airlines/'.$spotter_item['airline_icao'].'.png" /></a>';
	  		} else {
	  				if ($spotter_item['airline_name'] != "")
	  				{
	  					print '<a href="'.$globalURL.'/airline/'.$spotter_item['airline_icao'].'">'.$spotter_item['airline_name'].'</a>';
	  				} else {
	    				print 'N/A';
	  				}
	  		}
	  		print '</div>';
	  			
	  		print '<div class="detail">';
	  			print '<div class="title">Ident/Callsign</div>';
	  			print '<div>';
	  			if ($spotter_item['ident'] != "")
	  			{
	  				print '<a href="'.$globalURL.'/ident/'.$spotter_item['ident'].'">'.$spotter_item['ident'].'</a>';
	  			} else {
	    			print 'N/A';
	  			}
	  			print '</div>';
	  		print '</div>';
	  		
	  		print '<div class="detail">';
	  			print '<div class="title">Aircraft</div>';
	  			print '<div>';
	  				if ($spotter_item['aircraft_name'] != "")
		  			{
		    			print '<a href="'.$globalURL.'/aircraft/'.$spotter_item['aircraft_type'].'">'.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].')</a>';
		  			} else {
		  				if ($spotter_item['aircraft_type'] != "")
		  				{
		    				print $spotter_item['aircraft_type'];
		  				} else {
		    				print 'N/A';
		  				}
		  			}
	  			print '</div>';
	  		print '</div>';
	  		
	  		print '<div class="detail">';
	  			print '<div class="title">Registration</div>';
	  			print '<div>';
	  				if ($spotter_item['registration'] != "")
		  			{
		    			print '<a href="'.$globalURL.'/registration/'.$spotter_item['registration'].'">'.$spotter_item['registration'].'</a>';
		  			} else {
		    				print 'N/A';
		  			}
	  			print '</div>';
	  		print '</div>';
	  		
	  		print '<div class="detail">';
	  			print '<div class="title">Coming from</div>';
	  			print '<div>';
	  				if ($spotter_item['departure_airport_name'] != "")
	    			{
		    			print '<a href="'.$globalURL.'/airport/'.$spotter_item['departure_airport'].'">'.$spotter_item['departure_airport_city'].', '.$spotter_item['departure_airport_name'].', '.$spotter_item['departure_airport_country'].' ('.$spotter_item['departure_airport'].')</a>';
	    			} else {
	    				print $spotter_item['departure_airport'];
	    			}
	  			print '</div>';
	  		print '</div>';
	  		
	  		print '<div class="detail">';
	  			print '<div class="title">Flying to</div>';
	  			print '<div>';
	  				if ($spotter_item['arrival_airport_name'] != "")
	    			{
		    			print '<a href="'.$globalURL.'/airport/'.$spotter_item['arrival_airport'].'">'.$spotter_item['arrival_airport_city'].', '.$spotter_item['arrival_airport_name'].', '.$spotter_item['arrival_airport_country'].' ('.$spotter_item['arrival_airport'].')</a>';
						} else {
							print $spotter_item['arrival_airport'];
						}
	  			print '</div>';
	  		print '</div>';	
	  		
	  		print '<div class="detail">';
	  			print '<div class="title">Date</div>';
	  			print '<div>';
                    date_default_timezone_set('America/Toronto');
	  				print '<a href="'.$globalURL.'/date/'.date("Y-m-d", strtotime($spotter_item['date_iso_8601'])).'">'.date("M j, Y, g:i a", strtotime($spotter_item['date_iso_8601'])).'</a>';
	  			print '</div>';
	  		print '</div>';		
		  }
		 
		 print '</div>';
		
		
			print '<div class="col-sm-7 col-sm-offset-1 image">';
			
			print '<div class="image">';
		    	
	
				if ($spotter_array[0]['image'] != "")
				{	 	
					print '<img src="'.$spotter_array[0]['image'].'" alt="Image are courtesy of Planespotters.net" title="Image are courtesy of Planespotters.net" />';
					
				} else {
					print '<img src="'.$globalURL.'/images/placeholder.png" alt="No image found!" title="No image found!" />';
				}
			 
			 print '</div>';
			 print '<div class="note">Disclaimer: The images are courtesy of Planespotters.net and may not always be 100% accurate of the actual aircraft that has flown over.</div>';
		 print '</div>';
		 
	 print '</div>';
        	
     	print '</div>';
     	
     	
    if ($spotter_array[0]['registration'] != "")
    {
    	print '<div class="last-flights">';
	    	
	    	print '<h3>Last 5 Flights of this Aircraft ('.$spotter_array[0]['registration'].')</h3>';
	    	$hide_th_links = true;
	    	$spotter_array = Spotter::getSpotterDataByRegistration($spotter_array[0]['registration'],"0,5", "");
	    	
	    	include('table-output.php'); 

    	print '</div>';
    	
    }
	     	?>
	     <div class="column">
    	<div class="share">
	    	<span class='st_facebook' displayText='Facebook'></span>
			<span class='st_twitter' displayText='Tweet'></span>
			<span class='st_googleplus' displayText='Google +'></span>
			<span class='st_pinterest' displayText='Pinterest'></span>
			<span class='st_email' displayText='Email'></span>
    	</div>
		<script type="text/javascript">var switchTo5x=true;</script>
		<script type="text/javascript" src="http://w.sharethis.com/button/buttons.js"></script>
		<script type="text/javascript">stLight.options({publisher: "ur-5a9fbd4d-de8a-6441-d567-29163a2526c7", doNotHash: false, doNotCopy: false, hashAddressBar: false});</script>
    	<?php
    print '</div>';

} else {

	$title = "ID";
	require('header.php');
	
	print '<h1>Error</h1>';

  print '<p>Sorry, this flight is not in the database. :(</p>'; 
}


?>

<?php
require('footer.php');
?>