<?php
require_once('require/class.Connection.php');
require_once('require/class.Language.php');

?>
<div class="alldetails">
<button type="button" class="close">&times;</button>
<?php

$sat = filter_input(INPUT_GET,'sat',FILTER_SANITIZE_STRING);
$sat = urldecode($sat);

if ($sat == 'ISS (ZARYA)') {
	$image = 'https://upload.wikimedia.org/wikipedia/commons/0/04/International_Space_Station_after_undocking_of_STS-132.jpg';
	$image_copyright = 'NASA/Crew of STS-132';
	$ident = 'International Space Station';
	$aircraft_wiki = 'https://en.wikipedia.org/wiki/International_Space_Station';
	$aircraft_name = 'ISS';
	$ground_speed = 14970;
	$launch_date = '20 November 1998';
} elseif ($sat == 'TIANGONG 1') {
	$image = 'https://upload.wikimedia.org/wikipedia/commons/6/64/Tiangong_1_drawing_%28cropped%29.png';
	$image_copyright = 'Craigboy';
	$ident = 'Tiangong 1';
	$aircraft_wiki = 'https://en.wikipedia.org/wiki/Tiangong-1';
	$aircraft_name = 'Tiangong-1';
//	$ground_speed = 14970;
	$launch_date = '29 September 2011';
} elseif ($sat == 'TIANGONG-2') {
	$image = 'https://en.wikipedia.org/wiki/Tiangong-2#/media/File:Model_of_the_Chinese_Tiangong_Shenzhou.jpg';
	$image_copyright = 'Leebrandoncremer';
	$ident = 'Tiangong-2';
	$aircraft_wiki = 'https://en.wikipedia.org/wiki/Tiangong-2';
	$aircraft_name = 'Tiangong-2';
	$ground_speed = 27648;
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
} elseif (strpos($sat,'OSCAR 7') !== false) {
	$image = 'https://upload.wikimedia.org/wikipedia/en/a/ad/AMSAT-OSCAR_7.jpg';
	$image_copyright = 'Amsat.org';
	$ident = 'AMSAT-OSCAR 7';
	$aircraft_wiki = 'https://en.wikipedia.org/wiki/AMSAT-OSCAR_7';
	$aircraft_name = $sat;
	$launch_date = '15 November 1974';
} else {
	$ident = $sat;
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
	print '<span>'._("Aircraft").'</span>';
	print '<a href="'.$aircraft_wiki.'">'.$aircraft_name.'</a>';
	print '</div>';
}
/*
print '<div><span>'._("Altitude").'</span>';
if ((!isset($_COOKIE['unitaltitude']) && isset($globalUnitAltitude) && $globalUnitAltitude == 'feet') || (isset($_COOKIE['unitaltitude']) && $_COOKIE['unitaltitude'] == 'feet')) {
	print $spotter_item['altitude'].'00 feet (FL'.$spotter_item['altitude'].')';
} else {
	print round($spotter_item['altitude']*30.48).' m (FL'.$spotter_item['altitude'].')';
}
print '</div>';
*/
if (isset($ground_speed)) {
	print '<div><span>'._("Speed").'</span>';
	if ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'mph') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'mph')) {
		print round($ground_speed*1.15078).' mph';
	} elseif ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'knots') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'knots')) {
		print $ground_speed.' knots';
	} else {
		print round($ground_speed*1.852).' km/h';
	}
	print '</div>';
}
//print '<div><span>'._("Coordinates").'</span>'.$latitude.', '.$longitude.'</div>';
//print '<div><span>'._("Heading").'</span>'.$spotter_item['heading'].'°</div>';
if (isset($launch_date)) {
	print '<div><span>'._("Launch Date").'</span>'.$launch_date.'</div>';
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
