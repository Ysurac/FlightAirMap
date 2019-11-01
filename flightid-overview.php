<?php
$id = filter_input(INPUT_GET,'id',FILTER_SANITIZE_STRING);
if ($id == "")
{
	header('Location: /');
}

require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.ACARS.php');
require_once('require/class.Language.php');
require_once('require/class.SpotterArchive.php');
$ACARS = new ACARS();
$Spotter = new Spotter();
$SpotterArchive = new SpotterArchive();
$spotter_array = $Spotter->getSpotterDataByID($id);


if (!empty($spotter_array))
{
	if(isset($spotter_array[0]['flightaware_id'])) {
		$flightaware_id = $spotter_array[0]['flightaware_id'];
	}
	if(isset($spotter_array[0]['last_latitude']) && $spotter_array[0]['last_latitude'] != '') {
		$latitude = $spotter_array[0]['last_latitude'];
	} elseif(isset($spotter_array[0]['latitude'])) {
		$latitude = $spotter_array[0]['latitude'];
	}
	if(isset($spotter_array[0]['last_longitude']) && $spotter_array[0]['last_longitude'] != '') {
		$longitude = $spotter_array[0]['last_longitude'];
	} elseif(isset($spotter_array[0]['longitude'])) {
		$longitude = $spotter_array[0]['longitude'];
	}
	
	if (isset($flightaware_id) && ((!isset($latitude) && !isset($longitude)) || ($latitude == 0 && $longitude == 0))) {
		require_once('require/class.SpotterLive.php');
		$SpotterLive = new SpotterLive();
		$live_data = $SpotterLive->getLastLiveSpotterDataById($flightaware_id);
		$latitude = $live_data[0]['latitude'];
		$longitude = $live_data[0]['longitude'];
	}
	
	$title = '';
	if(isset($spotter_array[0]['ident'])) {
		$title .= $spotter_array[0]['ident'];
	}
	if(isset($spotter_array[0]['airline_name'])) {
		$title .= ' - '.$spotter_array[0]['airline_name'];
	}
	if(isset($spotter_array[0]['aircraft_name']) && $spotter_array[0]['aircraft_name'] != 'Not Available') {
		$title .= ' - '.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].')';
	}
	if(isset($spotter_array[0]['registration']) && $spotter_array[0]['registration'] != 'NA' && $spotter_array[0]['registration'] != 'N/A') {
		$title .= ' - '.$spotter_array[0]['registration'];
	}
	//$facebook_meta_image = $spotter_array[0]['image'];
	require_once('header.php');
	if (isset($globalArchive) && $globalArchive) {
		$all_data = $SpotterArchive->getAltitudeSpeedArchiveSpotterDataById($spotter_array[0]['flightaware_id']);
		if (isset($globalTimezone)) {
			date_default_timezone_set($globalTimezone);
		} else date_default_timezone_set('UTC');
		
		if (is_array($all_data) && count($all_data) > 1) {
			print '<br/>';
			print '<link href="'.$globalURL.'/css/c3.min.css" rel="stylesheet" type="text/css">';
			print '<script type="text/javascript" src="'.$globalURL.'/js/d3.min.js"></script>';
			print '<script type="text/javascript" src="'.$globalURL.'/js/c3.min.js"></script>';
			print '<div id="chart" class="chart" width="100%"></div><script>';
			$altitude_data = '';
			$hour_data = '';
			$speed_data = '';
			foreach($all_data as $data)
			{
				$hour_data .= '"'.$data['date'].'",';
				if (isset($data['real_altitude']) && $data['real_altitude'] != '') {
					$altitude = $data['real_altitude'];
				} else {
					$altitude = $data['altitude'].'00';
				}
				if ((!isset($_COOKIE['unitaltitude']) && isset($globalUnitAltitude) && $globalUnitAltitude == 'feet') || (isset($_COOKIE['unitaltitude']) && $_COOKIE['unitaltitude'] == 'feet')) {
					$unit = 'feet';
				} else {
					$unit = 'm';
					$altitude = round($altitude*0.3048);
				}
				$altitude_data .= $altitude.',';
				$speed = $data['ground_speed'];
				if ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'mph') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'mph')) {
					$speed = round($speed*1.15078);
					$units = 'mph';
				} elseif ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'knots') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'knots')) {
					$units = 'knots';
				} else {
					$speed = round($speed*1.852);
					$units = 'km/h';
				}
				$speed_data .= $speed.',';
			}
			$hour_data = "['x',".substr($hour_data, 0, -1)."]";
			$altitude_data = "['altitude',".substr($altitude_data,0,-1)."]";
			$speed_data = "['speed',".substr($speed_data,0,-1)."]";
			print 'c3.generate({
			    bindto: "#chart",
			    data: {
				x: "x",
				axes: {
				    altitude: "y",
				    speed: "y2"
				},
				xFormat: "%Y-%m-%d %H:%M:%S",
				columns: ['.$hour_data.','.$altitude_data.','.$speed_data.'],
				colors: { 
				    altitude: "#1a3151",
				    speed: "#aa0000"
				}
			    },
			    axis: { 
				x: { 
				    type: "timeseries", tick: { format: "%H:%M:%S"}
				},
				y: {
				    label: "Altitude ('.$unit.')"
				},
				y2: { 
				    label: "Speed ('.$units.')",
				    show: true
				}
			    },
			    legend: { show: false }});';
			print '</script>';
		}
	}


	print '<div class="info column">';
	print '<br/><br/><br/>';
	print '<h1>';
	if ($globalIVAO && @getimagesize($globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.gif')) {
		print '<a href="'.$globalURL.'/airline/'.$spotter_array[0]['airline_icao'].'"><img src="'.$globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.gif" class="airline-logo" /></a> ';
	} elseif (@getimagesize($globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.png')) {
		print '<a href="'.$globalURL.'/airline/'.$spotter_array[0]['airline_icao'].'"><img src="'.$globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.png" class="airline-logo" /></a> ';
	} else {
		if (isset($spotter_array[0]['airline_name']) && $spotter_array[0]['airline_name'] != "") {
			print '<a href="'.$globalURL.'/airline/'.$spotter_array[0]['airline_icao'].'">'.$spotter_array[0]['airline_name'].'</a> ';
		}
	}
	if(isset($spotter_array[0]['ident'])) {
		print $spotter_array[0]['ident'];
	}
	if(isset($spotter_array[0]['airline_name'])) {
		print ' - '.$spotter_array[0]['airline_name'];
	}
	if(isset($spotter_array[0]['aircraft_name']) && $spotter_array[0]['aircraft_name'] != 'Not Available') {
		print ' - '.$spotter_array[0]['aircraft_name'].' ('.$spotter_array[0]['aircraft_type'].')';
	}
	if(isset($spotter_array[0]['registration']) && $spotter_array[0]['registration'] != 'NA') {
		print ' - '.$spotter_array[0]['registration'];
	}
	print '</h1>';
	print '</div>';

	if ($spotter_array[0]['registration'] != "") {
		//$highlight = $Spotter->getHighlightByRegistration($spotter_array[0]['registration']);
		$highlight = $spotter_array[0]['highlight'];
		if ($highlight != "") {
			print '<div class="alert alert-warning">'.$highlight.'</div>';
		}
	}

	include('flightid-sub-menu.php');
	print '<div class="clear column">';
	print '<div class="image">';
	if ($spotter_array[0]['image'] != "")
	{
		if ($spotter_array[0]['image_source'] == 'planespotters') {
			$planespotter_url_array = explode("_", $spotter_array[0]['image']);
			$planespotter_id = str_replace(".jpg", "", $planespotter_url_array[1]);
			print '<a href="http://www.planespotters.net/Aviation_Photos/photo.show?id='.$planespotter_id.'" target="_blank"><img src="'.$spotter_array[0]['image_thumbnail'].'" alt="Click image to view on Planespotters.net" title="Click image to view on Planespotters.net" width="100%" /></a>';
			print '<div class="note">Disclaimer: The images are courtesy of Planespotters.net and their respective uploaders. This system may not always 100% accuratly show the actual aircraft.</div>';
			print '<div class="note">Planespotters.net didn\'t allow us to show full size pics here. This pic is copyright '.$spotter_array[0]['image_copyright'].'</div>';
		} else {
			if (isset($spotter_array[0]['image_source_website']) && $spotter_array[0]['image_source_website'] != '') {
				print '<a href="'.$spotter_array[0]['image_source_website'].'"><img src="'.$spotter_array[0]['image'].'" width="100%" /></a>';
			} else {
				print '<img src="'.$spotter_array[0]['image'].'" width="100%" />';
			}
			print '<div class="note">Disclaimer: The source of the above image is '.$spotter_array[0]['image_source'].' and is copyright '.$spotter_array[0]['image_copyright'].'. This system may not show the actual aircraft with 100% accuracy.</div>';
		}
	} else {
		//print '<img src="'.$globalURL.'/images/placeholder.png" alt="No image found!" title="No image found!" />';
	}
	print '</div>';
		
/*			print '<div class="col-sm-4 details">';
		 
		 	foreach($spotter_array as $spotter_item)
		  {
		  	print '<div class="detail">';
		  	if (@getimagesize($globalURL.'/images/airlines/'.$spotter_item['airline_icao'].'.png'))
	  		{
	    			print '<a href="'.$globalURL.'/airline/'.$spotter_item['airline_icao'].'"><img src="'.$globalURL.'/images/airlines/'.$spotter_item['airline_icao'].'.png" /></a>';
	  		} else {
	  				if (isset($spotter_item['airline_name']) && $spotter_item['airline_name'] != "")
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
	  				if (isset($spotter_item['aircraft_name']))
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
                    
	  				print '<a href="'.$globalURL.'/date/'.date("Y-m-d", strtotime($spotter_item['date_iso_8601'])).'">'.date("M j, Y, g:i a", strtotime($spotter_item['date_iso_8601'])).'</a>';
	  			print '</div>';
	  		print '</div>';		
		  }
		 
		 print '</div>';
		
		
			print '<div class="col-sm-7 col-sm-offset-1 image">';
			
			print '<div class="image">';
		    	
	
				if ($spotter_array[0]['image'] != "")
				{	 	
                    $planespotter_url_array = explode("_", $spotter_array[0]['image']);
                    $planespotter_id = str_replace(".jpg", "", $planespotter_url_array[1]);
                    print '<a href="http://www.planespotters.net/Aviation_Photos/photo.show?id='.$planespotter_id.'" target="_blank"><img src="'.$spotter_array[0]['image'].'" alt="Click image to view on Planespotters.net" title="Click image to view on Planespotters.net" /></a>';
					
				} else {
					print '<img src="'.$globalURL.'/images/placeholder.png" alt="No image found!" title="No image found!" />';
				}
			 
			 print '</div>';
			 print '<div class="note">Disclaimer: The images are courtesy of Planespotters.net and may not always be 100% accurate of the actual aircraft that has flown over.</div>';
		 print '</div>';
	 print '</div>';
*/

	foreach($spotter_array as $spotter_item)
	{
		print '<div class="details">';
		print '<h3>'._("Flight Information").'</h3>';
		print '<div class="detail callsign">';
		print '<div class="title">'._("Ident/Callsign").'</div>';
		print '<div>';
		if ($spotter_item['ident'] != "")
		{
			print '<a href="'.$globalURL.'/ident/'.$spotter_item['ident'].'">'.$spotter_item['ident'].'</a>';
		} else {
			print 'N/A';
		}
		print '</div>';
		print '</div>';

		if (isset($spotter_item['aircraft_owner']) && $spotter_item['aircraft_owner'] != '') 
		{
			print '<div class="detail fa-user">';
			print '<div class="title">'._("Owner").'</div>';
			print '<div>';
			print $spotter_item['aircraft_owner'];
			print '</div>';
			print '</div>';
		} elseif ((isset($globalVA) && $globalVA) || (isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS)) {
			print '<div class="detail fa-user">';
			print '<div class="title">'._("Pilot Name").'</div>';
			print '<div>';
			if (isset($spotter_item['pilot_id']) && $spotter_item['pilot_id'] != "")
			{
				if ($spotter_item['format_source'] == 'whazzup') print '<a href="https://www.ivao.aero/Member.aspx?ID='.$spotter_item['pilot_id'].'">'.$spotter_item['pilot_name'].' ('.$spotter_item['pilot_id'].')</a>';
				elseif ($spotter_item['format_source'] == 'vatsimtxt') print '<a href="http://www.vataware.com/pilot/'.$spotter_item['pilot_id'].'">'.$spotter_item['pilot_name'].' ('.$spotter_item['pilot_id'].')</a>';
				else print $spotter_item['pilot_name'].' ('.$spotter_item['pilot_id'].')';
			} else {
				if (isset($spotter_item['pilot_name']) && $spotter_item['pilot_name'] != "")
				{
					print $spotter_item['pilot_name'];
				} else {
					print _("N/A");
				}
			}
			print '</div>';
			print '</div>';
		}

		print '<div class="detail airline">';
		print '<div class="title">'._("Airline").'</div>';
		print '<div>';
		if ($spotter_item['airline_name'] != "")
		{
			print '<a href="'.$globalURL.'/airline/'.$spotter_item['airline_icao'].'">'.$spotter_item['airline_name'].'</a>';
		} else {
			print _("N/A");
		}
		print '</div>';
		print '</div>';

		print '<div class="detail aircraft">';
		print '<div class="title">'._("Aircraft").'</div>';
		print '<div>';
		if ($spotter_item['aircraft_name'] != "")
		{
			print '<a href="'.$globalURL.'/aircraft/'.$spotter_item['aircraft_type'].'">'.$spotter_item['aircraft_name'].' ('.$spotter_item['aircraft_type'].')</a>';
		} else {
			if ($spotter_item['aircraft_type'] != "")
			{
				print $spotter_item['aircraft_type'];
			} else {
				print _("N/A");
			}
		}
		print '</div>';
		print '</div>';

		print '<div class="detail registration">';
		print '<div class="title">'._("Registration").'</div>';
		print '<div>';
		if ($spotter_item['registration'] != "")
		{
			print '<a href="'.$globalURL.'/registration/'.$spotter_item['registration'].'">'.$spotter_item['registration'].'</a>';
		} else {
			print 'N/A';
		}
		print '</div>';
		print '</div>';

		if ($spotter_item['departure_airport'] != 'NA') {
			print '<div class="detail departure">';
			print '<div class="title">'._("Departure Airport").'</div>';
			print '<div>';
			if ($spotter_item['departure_airport_name'] != "")
			{
				print '<a href="'.$globalURL.'/airport/'.$spotter_item['departure_airport'].'">'.$spotter_item['departure_airport_city'].', '.$spotter_item['departure_airport_name'].', '.$spotter_item['departure_airport_country'].' ('.$spotter_item['departure_airport'].')</a>';
			} else {
				print $spotter_item['departure_airport'];
			}
			print '</div>';
			if (isset($spotter_item['departure_airport_time']) && $spotter_item['departure_airport_time'] != '') {
				if ($spotter_item['departure_airport_time'] > 2460) {
					print '<div class="time">';
					print 'at '.date('H:m',$spotter_item['departure_airport_time']);
					print '</div>';
				} else {
					print '<div class="time">';
					print 'at '.$spotter_item['departure_airport_time'];
					print '</div>';
				}
			}
			print '</div>';
		}

		if ($spotter_item['arrival_airport'] != 'NA') {
			print '<div class="detail arrival">';
			print '<div class="title">'._("Arrival Airport").'</div>';
			print '<div>';
			if ($spotter_item['arrival_airport_name'] != "")
			{
				print '<a href="'.$globalURL.'/airport/'.$spotter_item['arrival_airport'].'">'.$spotter_item['arrival_airport_city'].', '.$spotter_item['arrival_airport_name'].', '.$spotter_item['arrival_airport_country'].' ('.$spotter_item['arrival_airport'].')</a>';
			} else {
				print $spotter_item['arrival_airport'];
			}
			print '</div>';
			if (isset($spotter_item['arrival_airport_time']) && $spotter_item['arrival_airport_time'] != '') {
				print '<div class="time">';
				print 'at '.$spotter_item['arrival_airport_time'];
				print '</div>';
			} elseif (isset($spotter_item['real_arrival_airport_time']) && $spotter_item['real_arrival_airport_time'] != '') {
				print '<div class="time">';
				print 'at '.$spotter_item['real_arrival_airport_time'];
				print '</div>';
			}
			print '</div>';
		}

		if ($spotter_item['waypoints'] != "" || (isset($spotter_item['route_stop']) && $spotter_item['route_stop'] != ""))
		{
			print '<div class="detail coordinates">';
			print '<div class="title">'._("Route").'</div>';
			print '<div>';
			if ($spotter_item['waypoints'] != "")
			{
				print $spotter_item['waypoints'];
			} elseif ($spotter_item['route_stop'] != "")
			{
				print $spotter_item['route_stop'];
			}
			print '</div>';
			print '</div>';
		}
		print '</div>';

		print '<div class="details">';
		print '<h3>Additional information as it flew nearby</h3>';
		if ($spotter_item['latitude'] != 0 && $spotter_item['longitude'] != 0) {
			print '<div class="detail speed">';
			print '<div class="title">'._("Ground Speed").'</div>';
			print '<div>';
			if (isset($spotter_item['last_ground_speed']) && $spotter_item['last_ground_speed'] != '') {
				if ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'mph') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'mph')) {
					print round($spotter_item['last_ground_speed']*1.15078).' mph';
				} elseif ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'knots') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'knots')) {
					print $spotter_item['last_ground_speed'].' knots';
				} else {
					print round($spotter_item['last_ground_speed']*1.852).' km/h';
				}
			} else {
				if ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'mph') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'mph')) {
					print round($spotter_item['ground_speed']*1.15078).' mph';
				} elseif ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'knots') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'knots')) {
					print $spotter_item['ground_speed'].' knots';
				} else {
					print round($spotter_item['ground_speed']*1.852).' km/h';
				}
		}
		print '</div>';
		print '</div>';	

		print '<div class="detail heading">';
		print '<div class="title">'._("Heading (degrees)").'</div>';
		print '<div>';
		print $spotter_item['heading'].' ('.$spotter_item['heading_name'].')';
		print '</div>';
		print '</div>';

		print '<div class="detail altitude">';
		print '<div class="title">'._("Altitude").'</div>';
		print '<div>';
		if (isset($spotter_item['last_altitude']) && $spotter_item['last_altitude'] != '') {
			if ((!isset($_COOKIE['unitaltitude']) && isset($globalUnitAltitude) && $globalUnitAltitude == 'feet') || (isset($_COOKIE['unitaltitude']) && $_COOKIE['unitaltitude'] == 'feet')) {
				print number_format($spotter_item['last_altitude'].'00').' feet';
			} else {
				print round($spotter_item['last_altitude']*30.48).' m';
			}
		} else {
			if ((!isset($_COOKIE['unitaltitude']) && isset($globalUnitAltitude) && $globalUnitAltitude == 'feet') || (isset($_COOKIE['unitaltitude']) && $_COOKIE['unitaltitude'] == 'feet')) {
				print number_format($spotter_item['altitude'].'00').' feet';
			} else {
				print round($spotter_item['altitude']*30.48).' m';
			}
		}
		print '</div>';
		print '</div>';

		print '<div class="detail coordinates">';
		print '<div class="title">'._("Coordinates").'</div>';
		print '<div>';
		//print '<a href="https://www.google.com/maps/place/'.$spotter_item['latitude'].','.$spotter_item['longitude'].'/@'.$spotter_item['latitude'].','.$spotter_item['longitude'].',10z" target="_blank">Lat: '.$spotter_item['latitude'].' Lng: '.$spotter_item['longitude'].'</a>';
		if (isset($spotter_item['last_latitude']) && $spotter_item['last_latitude'] != '') {
			print 'Lat: '.$spotter_item['last_latitude'].' Lng: '.$spotter_item['last_longitude'];
		} else {
			print 'Lat: '.$spotter_item['latitude'].' Lng: '.$spotter_item['longitude'];
		}
		print '</div>';
		print '</div>';
		}
		print '<div class="detail date">';
		print '<div class="title">'._("Date").' ('.$globalTimezone.')</div>';
		print '<div>';
		date_default_timezone_set($globalTimezone);
		print '<a href="'.$globalURL.'/date/'.date("Y-m-d", strtotime($spotter_item['date_iso_8601'])).'">'.date("M j, Y g:i a", strtotime($spotter_item['date_iso_8601'])).'</a>';
		print '</div>';
		print '</div>';	

		print '<div class="detail date">';
		print '<div class="title">'._("Date").' (UTC)</div>';
		print '<div>';
		date_default_timezone_set('UTC');
		print date("M j, Y G:i", strtotime($spotter_item['date_iso_8601']));
		print '</div>';
		print '</div>';
		
		if (isset($spotter_item['duration'])) {
			print '<div class="detail duration">';
			print '<div class="title">'._("Flight spotted duration").'</div>';
			print '<div>';
			date_default_timezone_set('UTC');
			print date("H:m:s", strtotime($spotter_item['duration']));
			print '</div>';
			print '</div>';
		}

		if (isset($spotter_item['departure_airport']) && $spotter_item['departure_airport'] != '' && $spotter_item['departure_airport'] != 'NA' && $spotter_item['latitude'] != 0 && $spotter_item['longitude'] != 0) {
			print '<div class="detail distance-departure">';
			print '<div class="title">'._("Distance from Departure Airport").'</div>';
			print '<div>';
			$Common = new Common();
			$departure_airport_info = $Spotter->getAllAirportInfo($spotter_item['departure_airport']);
			if (count($departure_airport_info) > 0) {
				if (isset($spotter_item['last_latitude']) && $spotter_item['last_latitude'] != '') {
					if ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'nm') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'nm')) {
						print $Common->distance($spotter_item['last_latitude'],$spotter_item['last_longitude'],$departure_airport_info[0]['latitude'],$departure_airport_info[0]['longitude'],'nm').' nm';
					} elseif ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'mi') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'mi')) {
						print $Common->distance($spotter_item['last_latitude'],$spotter_item['last_longitude'],$departure_airport_info[0]['latitude'],$departure_airport_info[0]['longitude'],'mi').' mi';
					} else {
						print $Common->distance($spotter_item['last_latitude'],$spotter_item['last_longitude'],$departure_airport_info[0]['latitude'],$departure_airport_info[0]['longitude'],'km').' km';
					}
				} else {
					if ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'nm') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'nm')) {
						print $Common->distance($spotter_item['latitude'],$spotter_item['longitude'],$departure_airport_info[0]['latitude'],$departure_airport_info[0]['longitude'],'nm').' nm';
					} elseif ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'mi') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'mi')) {
						print $Common->distance($spotter_item['latitude'],$spotter_item['longitude'],$departure_airport_info[0]['latitude'],$departure_airport_info[0]['longitude'],'mi').' mi';
					} else {
						print $Common->distance($spotter_item['latitude'],$spotter_item['longitude'],$departure_airport_info[0]['latitude'],$departure_airport_info[0]['longitude'],'km').' km';
					}
				}
			}
			print '</div>';
			print '</div>';
		}
		if (isset($spotter_item['arrival_airport']) && $spotter_item['arrival_airport'] != '' && $spotter_item['arrival_airport'] != 'NA' && $spotter_item['latitude'] != 0 && $spotter_item['longitude'] != 0) {
			print '<div class="detail distance-arrival">';
			print '<div class="title">'._("Distance to Arrival Airport").'</div>';
			print '<div>';
			$Common = new Common();
			$arrival_airport_info = $Spotter->getAllAirportInfo($spotter_item['arrival_airport']);
			if (count($arrival_airport_info) > 0) {
				if (isset($spotter_item['last_latitude']) && $spotter_item['last_latitude'] != '') {
					if ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'nm') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'nm')) {
						print $Common->distance($spotter_item['last_latitude'],$spotter_item['last_longitude'],$arrival_airport_info[0]['latitude'],$arrival_airport_info[0]['longitude'],'nm').' nm';
					} elseif ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'mi') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'mi')) {
						print $Common->distance($spotter_item['last_latitude'],$spotter_item['last_longitude'],$arrival_airport_info[0]['latitude'],$arrival_airport_info[0]['longitude'],'mi').' mi';
					} else {
						print $Common->distance($spotter_item['last_latitude'],$spotter_item['last_longitude'],$arrival_airport_info[0]['latitude'],$arrival_airport_info[0]['longitude'],'km').' km';
					}
				} else {
					if ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'nm') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'nm')) {
						print $Common->distance($spotter_item['latitude'],$spotter_item['longitude'],$arrival_airport_info[0]['latitude'],$arrival_airport_info[0]['longitude'],'nm').' nm';
					} elseif ((!isset($_COOKIE['unitdistance']) && isset($globalUnitDistance) && $globalUnitDistance == 'mi') || (isset($_COOKIE['unitdistance']) && $_COOKIE['unitdistance'] == 'mi')) {
						print $Common->distance($spotter_item['latitude'],$spotter_item['longitude'],$arrival_airport_info[0]['latitude'],$arrival_airport_info[0]['longitude'],'mi').' mi';
					} else {
						print $Common->distance($spotter_item['latitude'],$spotter_item['longitude'],$arrival_airport_info[0]['latitude'],$arrival_airport_info[0]['longitude'],'km').' km';
					}
				}
			}
			print '</div>';
			print '</div>';
		}
		$LatestACARS = $ACARS->getLiveAcarsData($spotter_item['ident']);
		if ($LatestACARS != '') {
			print '<div class="detail acars">';
			print '<div class="title">'._("Latest ACARS message").'</div>';
			print '<div>';
			print $LatestACARS;
			print '</div>';
			print '</div>';
		}
		print '</div>';
	}
	print '</div>';

	print '<div id="archive-map"></div>';
	//print '<div id="live-map"></div>';

	if ($spotter_array[0]['registration'] != "" && $spotter_array[0]['registration'] != "NA" && $spotter_array[0]['registration'] != "N/A")
	{
		$registration = $spotter_array[0]['registration'];
		print '<div class="last-flights">';
		print '<h3>'._("Last 5 Flights of this Aircraft").' ('.$registration.')</h3>';
		$hide_th_links = true;
		$spotter_array = $Spotter->getSpotterDataByRegistration($registration,"0,5", "");
		include('table-output.php'); 
		print '<div class="more">';
		print '<a href="'.$globalURL.'/registration/'.$registration.'" class="btn btn-default btn" role="button">See all Flights&raquo;</a>';
		print '</div>';
		print '</div>';
	}
/*	     	?>
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
*/
} else {
	$title = "ID";
	require_once('header.php');
	print '<h1>'._("Error").'</h1>';
	print '<p>'._("Sorry, this flight is not anymore in the database. :(").'</p>'; 
}
require_once('footer.php');
?>