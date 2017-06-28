<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
require_once('require/class.METAR.php');
$Spotter = new Spotter();

if (isset($_GET['airport_icao'])) {
	$icao = filter_input(INPUT_GET,'airport_icao',FILTER_SANITIZE_STRING);
	$spotter_array = $Spotter->getAllAirportInfo($icao);
	if (isset($globalMETAR) && $globalMETAR) {
		$METAR = new METAR();
		$metar_info = $METAR->getMETAR($icao);
		//print_r($metar_info);
		if (isset($metar_info[0]['metar'])) $metar_parse = $METAR->parse($metar_info[0]['metar']);
		//print_r($metar_parse);
	}
 ?>
<div class="alldetails">
<button type="button" class="close">&times;</button>
<?php
	$spotter_item = $spotter_array[0];
	//print_r($spotter_item);
	date_default_timezone_set('UTC');
	if (isset($spotter_item['image_thumb']) && $spotter_item['image_thumb'] != "")
	{
		$image = $spotter_item['image_thumb'];
	}
	print '<div class="top">';
	if (isset($image)) {
		//print '<div class="left"><img src="'.$image.'" alt="'.$spotter_item['icao'].' '.$spotter_item['name'].'" title="'.$spotter_item['name'].'"/><br />Image &copy; '.$spotter_item['image_copyright'].'</div>';
		print '<div class="left"><img src="'.$image.'" alt="'.$spotter_item['icao'].' '.$spotter_item['name'].'" title="'.$spotter_item['name'].'"/><br /></div>';
	}
	print '<div class="right"><div class="callsign-details"><div class="callsign">'.$spotter_item['name'].'</div>';
	print '</div>';
	print '<div class="nomobile airports"><div class="airport"><span class="code"><a href="/airport/'.$spotter_item['icao'].'" target="_blank">'.$spotter_item['icao'].'</a></span>';
	print '</div></div>';
	print '</div></div>';
	print '<div class="details"><div class="mobile airports"><div class="airport">';
	print '<span class="code"><a href="/airport/'.$spotter_item['icao'].'" target="_blank">'.$spotter_item['icao'].'</a></span>';
	print '</div></div><div>';
	print '<span>'._("City").'</span>';
	print $spotter_item['city'];
	print '</div>';
	print '<div><span>'._("Altitude").'</span>';
	if ((!isset($_COOKIE['unitaltitude']) && isset($globalUnitAltitude) && $globalUnitAltitude == 'feet') || (isset($_COOKIE['unitaltitude']) && $_COOKIE['unitaltitude'] == 'feet')) {
		print $spotter_item['altitude'].' feet';
	} else {
		print round($spotter_item['altitude']*0.3048).' m';
	}
	print '</div>';
	print '<div><span>'._("Country").'</span>'.$spotter_item['country'].'</div>';
	print '<div><span>'._("Coordinates").'</span>'.round($spotter_item['latitude'],3).', '.round($spotter_item['longitude'],3).'</div>';
	if (isset($spotter_item['home_link']) && $spotter_item['home_link'] != '' && isset($spotter_item['wikipedia_link']) && $spotter_item['wikipedia_link'] != '') {
		print '<div><span>'._("Links").'</span>';
		print '<a href="'.$spotter_item['home_link'].'">'._("Homepage").'</a>';
		print ' - ';
		print '<a href="'.$spotter_item['wikipedia_link'].'">Wikipedia</a>';
		print '</div>';
	} elseif (isset($spotter_item['home_link']) && $spotter_item['home_link'] != '') {
		print '<div><span>'._("Links").'</span>';
		print '<a href="'.$spotter_item['home_link'].'">'._("Homepage").'</a>';
		print '</div>';
	} elseif (isset($spotter_item['wikipedia_link']) && $spotter_item['wikipedia_link'] != '') {
		print '<div><span>'._("Links").'</span>';
		print '<a href="'.$spotter_item['wikipedia_link'].'">Wikipedia</a>';
		print '</div>';
	}
	if ($spotter_item['type'] == 'medium_airport' || $spotter_item['type'] == 'large_airport') {
		print '<div><span>'._("Live Air Traffic").'</span>';
		print '<a href="http://www.liveatc.net/search/?icao='.$spotter_item['icao'].'">LiveATC</a>';
		print '</div>';
	}
	print '</div>';
	if (isset($metar_parse)) {
		print '<div class="waypoints">';
		print '<div><span>METAR</span>';
		print '<i>'.$metar_info[0]['metar'].'</i><br />';
		print '<b>'.$metar_info[0]['metar_date'].'</b><br />';
		//    print_r($metar_parse);
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
			print '<br/>';
		}
		if (isset($metar_parse['visibility'])) {
			print _("Visibility:").' '.$metar_parse['visibility'].' m'."<br/>";
		}
		if (isset($metar_parse['weather'])) {
			print _("Weather:").' '.$metar_parse['weather']."<br/>";
		}
		if (isset($metar_parse['temperature'])) {
			print _("Temperature:").' '.$metar_parse['temperature'].' °C'."<br/>";
		}
		if (isset($metar_parse['dew'])) {
			print _("Dew point:").' '.$metar_parse['dew'].' °C'."<br/>";
		}
		if (isset($metar_parse['temperature']) && isset($metar_parse['dew'])) {
			$humidity = round(100 * pow((112 - (0.1 * $metar_parse['temperature']) + $metar_parse['dew']) / (112 + (0.9 * $metar_parse['temperature'])), 8),1);
			print _("Humidity:").' '.$humidity.'%'."<br/>";
		}
		if (isset($metar_parse['QNH'])) {
			print _("Pressure:").' '.$metar_parse['QNH'].' hPa'."<br/>";
		}
/*
if (isset($metar_parse['QNH'])) {
    print 'Pressure : '.$metar_parse['QNH'].' hPa'."<br/>";
}
*/
		print '</div>';
/*
Wind: from the NNE (020 degrees) at 5 MPH (4 KT) (direction variable):0
Visibility: greater than 7 mile(s):0
Sky conditions: mostly cloudy
Temperature: 48 F (9 C)
Dew Point: 44 F (7 C)
Relative Humidity: 87%
Pressure (altimeter): 30.65 in. Hg (1038 hPa)
ob: LSGG 091150Z 02004KT 350V050 9999 FEW008 BKN045 09/07 Q1038 NOSIG
cycle: 12
*/

	}

/*
if (isset($spotter_item['waypoints']) && $spotter_item['waypoints'] != '') print '<div class="waypoints"><span>Route</span>'.$spotter_item['waypoints'].'</div>';
if (isset($spotter_item['acars']['message'])) print '<div class="acars"><span>Latest ACARS message</span>'.trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'<br/>',$spotter_item['acars']['message'])).'</div>';
if (isset($spotter_item['squawk']) && $spotter_item['squawk'] != '' && $spotter_item['squawk'] != 0) print '<div class="bottom">Squawk : '.$spotter_item['squawk'].' - '.$spotter_item['squawk_usage'].'</div>';
*/
	print '</div>';
}
?>
</div>
