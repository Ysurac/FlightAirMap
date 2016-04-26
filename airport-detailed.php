<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Stats.php');
require_once('require/class.METAR.php');

if (!isset($_GET['airport'])){
	header('Location: '.$globalURL.'/airport');
} else {
	$Spotter = new Spotter();
	//calculuation for the pagination
	if(!isset($_GET['limit']))
	{
		$limit_start = 0;
		$limit_end = 25;
		$absolute_difference = 25;
	}  else {
		$limit_explode = explode(",", $_GET['limit']);
		$limit_start = $limit_explode[0];
		$limit_end = $limit_explode[1];
		if (!ctype_digit(strval($limit_start)) || !ctype_digit(strval($limit_end))) {
			$limit_start = 0;
			$limit_end = 25;
		}
	}
	$absolute_difference = abs($limit_start - $limit_end);
	$limit_next = $limit_end + $absolute_difference;
	$limit_previous_1 = $limit_start - $absolute_difference;
	$limit_previous_2 = $limit_end - $absolute_difference;
	$airport_icao = filter_input(INPUT_GET,'airport',FILTER_SANITIZE_STRING);
	$page_url = $globalURL.'/airport/'.$airport_icao;
	
	if (isset($_GET['sort'])) {
		$spotter_array = $Spotter->getSpotterDataByAirport($airport_icao,$limit_start.",".$absolute_difference, $_GET['sort']);
	} else {
		$spotter_array = $Spotter->getSpotterDataByAirport($airport_icao,$limit_start.",".$absolute_difference, '');
	}
	$airport_array = $Spotter->getAllAirportInfo($airport_icao);
	
	if (!empty($airport_array))
	{
		
		if (isset($globalMETAR) && $globalMETAR) {
			$METAR = new METAR();
			$metar_info = $METAR->getMETAR($airport_icao);
			//print_r($metar_info);
			if (isset($metar_info[0]['metar'])) $metar_parse = $METAR->parse($metar_info[0]['metar']);
            		//print_r($metar_parse);
		}
		
		$title = _("Detailed View for").' '.$airport_array[0]['city'].', '.$airport_array[0]['name'].' ('.$airport_array[0]['icao'].')';

		require_once('header.php');
		print '<div class="select-item">';
		print '<form action="'.$globalURL.'/airport" method="post">';
		print '<select name="airport" class="selectpicker" data-live-search="true">';
		print '<option></option>';
		$airport_names = $Spotter->getAllAirportNames();
		ksort($airport_names);
		foreach($airport_names as $airport_name)
		{
			if($airport_icao == $airport_name['airport_icao'])
			{
				print '<option value="'.$airport_name['airport_icao'].'" selected="selected">'.$airport_name['airport_city'].', '.$airport_name['airport_name'].', '.$airport_name['airport_country'].' ('.$airport_name['airport_icao'].')</option>';
			} else {
				print '<option value="'.$airport_name['airport_icao'].'">'.$airport_name['airport_city'].', '.$airport_name['airport_name'].', '.$airport_name['airport_country'].' ('.$airport_name['airport_icao'].')</option>';
			}
		}
		print '</select>';
		print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
		print '</form>';
		print '</div>';
		if ($airport_icao != "NA")
		{
			print '<div class="info column">';
			print '<h1>'.$airport_array[0]['city'].', '.$airport_array[0]['name'].' ('.$airport_array[0]['icao'].')</h1>';
			print '<div><span class="label">'._("Name").'</span>'.$airport_array[0]['name'].'</div>';
			print '<div><span class="label">'._("City").'</span>'.$airport_array[0]['city'].'</div>';
			print '<div><span class="label">'._("Country").'</span>'.$airport_array[0]['country'].'</div>';
			print '<div><span class="label">'._("ICAO").'</span>'.$airport_array[0]['icao'].'</div>';
			print '<div><span class="label">'._("IATA").'</span>'.$airport_array[0]['iata'].'</div>';
			print '<div><span class="label">'._("Altitude").'</span>'.$airport_array[0]['altitude'].'</div>';
			print '<div><span class="label">'._("Coordinates").'</span><a href="http://maps.google.ca/maps?z=10&t=k&q='.$airport_array[0]['latitude'].','.$airport_array[0]['longitude'].'" target="_blank">Google Map<i class="fa fa-angle-double-right"></i></a></div>';
			print '</div>';
			
			print '<script type="text/javascript" src="https://www.google.com/jsapi"></script>';
			$Stats = new Stats();
			
			$all_data = $Stats->getLast7DaysAirports($airport_icao);
			if (isset($globalTimezone)) {
				date_default_timezone_set($globalTimezone);
			} else date_default_timezone_set('UTC');
			if (count($all_data) > 0) {
				print '<div id="chart6" class="chart" width="100%"></div>
                    <script> 
                        google.load("visualization", "1.1", {packages:["line","corechart"]});
                      google.setOnLoadCallback(drawChart6);
                      function drawChart6() {
                        var data = google.visualization.arrayToDataTable([
                            ["'._("Date").'","'._("Departure").'","'._("Arrival").'"], ';
                            $airport_data = '';
                                foreach($all_data as $data)
                                {
                                        $airport_data .= '[ "'.$data['date'].'",'.$data['departure'].','.$data['arrival'].'],';
                                }
                                $airport_data = substr($airport_data, 0, -1);
                                print $airport_data.']);

                        var options = {
                    	    legend: {position: "none"},
                    	    chart: {
                    		title: "'._("Last week flights departure/arrival").'"
                    	    },
                            height:210
                        };

                        var chart = new google.charts.Line(document.getElementById("chart6"));
                        chart.draw(data, options);
                      }
                      $(window).resize(function(){
                              drawChart6();
                            });
                                 </script>';
			}
			print '<div class="info column">';
			if (isset($metar_parse)) {
				print '<div><span class="label">METAR</span>';
				print $metar_info[0]['metar'].'<br />';
				print '<b>'.$metar_info[0]['metar_date'].'</b> ';
				if (isset($metar_parse['wind'])) {
					print _("Wind:").' ';
					if (isset($metar_parse['wind']['direction'])) {
						$direction = $Spotter->parseDirection($metar_parse['wind']['direction']);
						print $direction[0]['direction_fullname'];
						print ' ('.$metar_parse['wind']['direction'].'°) ';
					}
					if (isset($metar_parse['wind']['speed'])) {
						print $metar_parse['wind']['speed'].' m/s';
					}
					print ' - ';
				}
				if (isset($metar_parse['visibility'])) {
					print _("Visibility:").' '.$metar_parse['visibility'].' m'." - ";
				}
				if (isset($metar_parse['weather'])) {
					print _("Weather:").' '.$metar_parse['weather']." - ";
				}
				if (isset($metar_parse['temperature'])) {
					print _("Temperature:").' '.$metar_parse['temperature'].' °C'." - ";
				}
				if (isset($metar_parse['dew'])) {
					print _("Dew point:").' '.$metar_parse['dew'].' °C'." - ";
				}
				if (isset($metar_parse['temperature']) && isset($metar_parse['dew'])) {
					$humidity = round(100 * pow((112 - (0.1 * $metar_parse['temperature']) + $metar_parse['dew']) / (112 + (0.9 * $metar_parse['temperature'])), 8),1);
					print _("Humidity:").' '.$humidity.'%'." - ";
				}
				if (isset($metar_parse['QNH'])) {
					print _("Pressure:").' '.$metar_parse['QNH'].' hPa';
				}
				print '</div>';
			}
			print '</div>';
		} else {
			print '<div class="alert alert-warning">'._("This special airport profile shows all flights that do <u>not</u> have a departure and/or arrival airport associated with them.").'</div>';
		}
		include('airport-sub-menu.php');
		print '<div class="table column">';
		 if ($airport_array[0]['iata'] != "NA")
		{
			print '<p>'._("The table below shows the detailed information of all flights to/from").' <strong>'.$airport_array[0]['city'].', '.$airport_array[0]['name'].' ('.$airport_array[0]['icao'].')</strong>.</p>';
		}
		include('table-output.php');  
		print '<div class="pagination">';
		if ($limit_previous_1 >= 0)
		{
			print '<a href="'.$page_url.'/'.$limit_previous_1.','.$limit_previous_2.'/'.$_GET['sort'].'">&laquo;'._("Previous Page").'</a>';
		}
		if (isset($spotter_array[0]['query_number_rows']) && $spotter_array[0]['query_number_rows'] == $absolute_difference)
		{
			print '<a href="'.$page_url.'/'.$limit_end.','.$limit_next.'/'.$_GET['sort'].'">'._("Next Page").'&raquo;</a>';
		}
		print '</div>';
		print '</div>';
	} else {
		$title = "Airport";
		require_once('header.php');
		print '<h1>'._("Error").'</h1>';
		print '<p>'._("Sorry, the airport does not exist in this database. :(").'</p>'; 
	}
}
require_once('footer.php');
?>