<?php
require_once('require/class.Connection.php');
require_once('require/class.Language.php');

?>
<div class="alldetails">
<button type="button" class="close">&times;</button>
<?php
$image = 'https://upload.wikimedia.org/wikipedia/commons/0/04/International_Space_Station_after_undocking_of_STS-132.jpg';
$image_copyright = 'NASA/Crew of STS-132';
$ident = 'International Space Station';
$aircraft_wiki = 'https://en.wikipedia.org/wiki/International_Space_Station';
$aircraft_name = 'ISS';
$ground_speed = 14970;
$launch_date = '20 November 1998';
date_default_timezone_set('UTC');
print '<div class="top">';
print '<div class="left"><img src="'.$image.'" /><br />Image &copy; '.$image_copyright.'</div>';
print '<div class="right"><div class="callsign-details"><div class="callsign">'.$ident.'</a></div>';
print '</div>';
print '<div class="details">';
print '<div>';
print '<span>'._("Aircraft").'</span>';
print '<a href="'.$aircraft_wiki.'">'.$aircraft_name.'</a>';
print '</div>';
/*
print '<div><span>'._("Altitude").'</span>';
if ((!isset($_COOKIE['unitaltitude']) && isset($globalUnitAltitude) && $globalUnitAltitude == 'feet') || (isset($_COOKIE['unitaltitude']) && $_COOKIE['unitaltitude'] == 'feet')) {
	print $spotter_item['altitude'].'00 feet (FL'.$spotter_item['altitude'].')';
} else {
	print round($spotter_item['altitude']*30.48).' m (FL'.$spotter_item['altitude'].')';
}
print '</div>';
*/
print '<div><span>'._("Speed").'</span>';
if ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'mph') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'mph')) {
	print round($ground_speed*1.15078).' mph';
} elseif ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'knots') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'knots')) {
	print $ground_speed.' knots';
} else {
	print round($ground_speed*1.852).' km/h';
}
print '</div>';
//print '<div><span>'._("Coordinates").'</span>'.$latitude.', '.$longitude.'</div>';
//print '<div><span>'._("Heading").'</span>'.$spotter_item['heading'].'°</div>';
print '<div><span>'._("Launch Date").'</span>'.$launch_date.'</div>';
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
