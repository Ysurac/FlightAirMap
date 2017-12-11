<?php
require_once('require/class.Connection.php');
require_once('require/class.Language.php');
require_once('require/class.Satellite.php');
$Satellite = new Satellite();

?>
<div class="alldetails">
<button type="button" class="close">&times;</button>
<?php

$sat = filter_input(INPUT_GET,'sat',FILTER_SANITIZE_STRING);
$sat = urldecode($sat);
//$info = $Satellite->get_info(str_replace(' ','-',$sat));
//print_r($info);
if ($sat == 'ISS (ZARYA)') {
	$image = 'https://upload.wikimedia.org/wikipedia/commons/0/04/International_Space_Station_after_undocking_of_STS-132.jpg';
	$image_copyright = 'NASA/Crew of STS-132';
	$ident = 'International Space Station';
	$satname = 'International Space Station';
	$aircraft_wiki = 'https://en.wikipedia.org/wiki/International_Space_Station';
	$aircraft_name = 'ISS';
//	$ground_speed = 14970;
	$launch_date = '20 November 1998';
} elseif ($sat == 'TIANGONG 1') {
	$image = 'https://upload.wikimedia.org/wikipedia/commons/6/64/Tiangong_1_drawing_%28cropped%29.png';
	$image_copyright = 'Craigboy';
	$ident = 'Tiangong 1';
	$satname = 'Tiangong-1';
	$aircraft_wiki = 'https://en.wikipedia.org/wiki/Tiangong-1';
	$aircraft_name = 'Tiangong-1';
//	$ground_speed = 14970;
	$launch_date = '29 September 2011';
} elseif ($sat == 'TIANGONG-2') {
	$image = 'https://upload.wikimedia.org/wikipedia/commons/4/4a/Model_of_the_Chinese_Tiangong_Shenzhou.jpg';
	$image_copyright = 'Leebrandoncremer';
	$ident = 'Tiangong-2';
	$satname = 'Tiangong-2';
	$aircraft_wiki = 'https://en.wikipedia.org/wiki/Tiangong-2';
	$aircraft_name = 'Tiangong-2';
//	$ground_speed = 27648;
	$launch_date = '15 September 2016';
} elseif ($sat == 'INTEGRAL') {
	$image = 'https://upload.wikimedia.org/wikipedia/en/0/02/INTEGRAL.jpg';
	$image_copyright = 'ESA-Medialab';
	$ident = 'INTEGRAL';
	$aircraft_wiki = 'https://en.wikipedia.org/wiki/INTEGRAL';
	$aircraft_name = 'INTEGRAL';
//	$ground_speed = 14970;
	$launch_date = '17 October 2002';
} elseif (strpos($sat,'IRIDIUM') !== false) {
	$image = 'https://upload.wikimedia.org/wikipedia/commons/b/b6/Iridium_Satellite.jpg';
	$image_copyright = 'Cliff';
	$ident = 'Iridium satellite constellation';
	$aircraft_wiki = 'https://en.wikipedia.org/wiki/Iridium_satellite_constellation';
	$aircraft_name = $sat;
//	$ground_speed = 14970;
//	$launch_date = '29 september 2011';
} elseif (strpos($sat,'ORBCOMM') !== false) {
	$ident = 'Orbcomm';
	$aircraft_wiki = 'https://en.wikipedia.org/wiki/Orbcomm_(satellite)';
	$aircraft_name = $sat;
} elseif (strpos($sat,'GLOBALSTAR') !== false) {
	$ident = 'Globalstar';
	$aircraft_wiki = 'https://en.wikipedia.org/wiki/Globalstar';
	$aircraft_name = $sat;
	$satname = str_replace(array('[+]','[-]'),'',$sat);
} elseif (strpos($sat,'OSCAR 7') !== false) {
	$image = 'https://upload.wikimedia.org/wikipedia/en/a/ad/AMSAT-OSCAR_7.jpg';
	$image_copyright = 'Amsat.org';
	$ident = 'AMSAT-OSCAR 7';
	$aircraft_wiki = 'https://en.wikipedia.org/wiki/AMSAT-OSCAR_7';
	$aircraft_name = $sat;
	$launch_date = '15 November 1974';
} elseif (strpos($sat,'santaclaus') !== false) {
	$image = 'https://upload.wikimedia.org/wikipedia/commons/4/49/Jonathan_G_Meath_portrays_Santa_Claus.jpg';
	$image_copyright = 'Jonathan G Meath';
	$ident = 'Santa Claus';
	$aircraft_wiki = 'https://en.wikipedia.org/wiki/Santa_Claus';
	$aircraft_name = 'Sleigh led by eight reindeer';
//	$launch_date = '15 November 1974';
} else {
	$ident = $sat;
	if (strpos($sat,'(')) $satname = $sat;
	else $satname = str_replace(array(' '),'-',$sat);
}
if (!isset($satname)) $satname = $sat;
if ($satname != 'santaclaus') {
	$info = $Satellite->get_info(strtolower(trim($satname)));
	$position = $Satellite->position($sat);
	$ground_speed = $position['speed'];
	$altitude = $position['altitude'];
}
date_default_timezone_set('UTC');
print '<div class="top">';
if (isset($image)) {
	print '<div class="left"><img src="'.$image.'" /><br />Image &copy; '.$image_copyright.'</div>';
}
print '<div class="right"><div class="callsign-details"><div class="callsign">'.$ident.'</a></div>';
print '</div>';
print '<div class="details">';
if (isset($aircraft_wiki)) {
	print '<div>';
	print '<span>'._("Spacecraft").'</span>';
	print '<a href="'.$aircraft_wiki.'">'.$aircraft_name.'</a>';
	print '</div>';
}
if (isset($altitude)) {
	print '<div><span>'._("Altitude").'</span>';
	print '<span class="altitude">';
	if ((!isset($_COOKIE['unitaltitude']) && isset($globalUnitAltitude) && $globalUnitAltitude == 'feet') || (isset($_COOKIE['unitaltitude']) && $_COOKIE['unitaltitude'] == 'feet')) {
		print round($altitude*3280.84).' feet';
	} else {
		print round($altitude).' km';
	}
	print '</span>';
	print '</div>';
}

if (isset($ground_speed)) {
	print '<div><span>'._("Speed").'</span>';
	if ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'mph') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'mph')) {
		print round($ground_speed*0.621371).' mph';
	} elseif ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'knots') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'knots')) {
		print round($ground_speed*0.539957).' knots';
	} else {
		print round($ground_speed).' km/h';
	}
	//print '<span class="realspeed"></span>';
	print '</div>';
} else {
	print '<div id="realspeed"><span>'._("Speed").'</span>';
	print '<span class="realspeed"></span>';
	print '</div>';
}

//print '<div><span>'._("Coordinates").'</span>'.$latitude.', '.$longitude.'</div>';
//print '<div><span>'._("Heading").'</span>'.$spotter_item['heading'].'°</div>';
if (isset($launch_date)) {
	print '<div><span>'._("Launch Date").'</span>'.$launch_date.'</div>';
} 
if (!empty($info)) {
	if ($info['country_owner'] != '') {
		print '<div><span>'._("Owner Country").'</span>'.$info['country_owner'].'</div>';
	}
	if ($info['owner'] != '') {
		print '<div><span>'._("Owner").'</span>'.$info['owner'].'</div>';
	}
	if ($info['users'] != '') {
		print '<div><span>'._("Users").'</span>'.$info['users'].'</div>';
	}
	if ($info['purpose'] != '') {
		print '<div><span>'._("Purpose").'</span>'.$info['purpose'].'</div>';
	}
	if ($info['orbit'] != '') {
		print '<div><span>'._("Orbit").'</span>'.$info['orbit'].'</div>';
	}
	if ($info['launch_date'] != '') {
		print '<div><span>'._("Launch Date").'</span>'.date('Y-m-d',strtotime($info['launch_date'])).'</div>';
	}
	if ($info['launch_site'] != '') {
		print '<div><span>'._("Launch Site").'</span>'.$info['launch_site'].'</div>';
	}
	if ($info['launch_vehicule'] != '') {
		print '<div><span>'._("Launch Vehicule").'</span>'.$info['launch_vehicule'].'</div>';
	}
}
/*
if (isset($spotter_item['aircraft_owner']) && $spotter_item['aircraft_owner'] != '') {
	print '<div><span>'._("Owner").'</span>';
	print $spotter_item['aircraft_owner'];
	print '</div>';
}
*/
/*
if (isset($spotter_item['source_name']) && $spotter_item['source_name'] != '') {
	print '<div><span>'._("Source").'</span>';
	print $spotter_item['source_name'];
	print '</div>';
}
*/
print '</div>';
print '</div>';
?>
</div>
