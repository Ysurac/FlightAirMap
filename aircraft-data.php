<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');
require('require/class.SpotterLive.php');
require('require/class.SpotterArchive.php');
$SpotterLive = new SpotterLive();
$SpotterArchive = new SpotterArchive();

$from_archive = false;
if (isset($_GET['ident'])) {
	$ident = $_GET['ident'];
	$spotter_array = $SpotterLive->getLastLiveSpotterDataByIdent($ident);
	if (empty($spotter_array)) {
		$from_archive = true;
		$spotter_array = $SpotterArchive->getLastArchiveSpotterDataByIdent($ident);
	}
}
if (isset($_GET['flightaware_id'])) {
	$flightaware_id = $_GET['flightaware_id'];
	$spotter_array = $SpotterLive->getLastLiveSpotterDataById($flightaware_id);
	if (empty($spotter_array)) {
		$from_archive = true;
		$spotter_array = $SpotterArchive->getLastArchiveSpotterDataById($flightaware_id);
	}
}
 ?>
<div class="alldetails">
<button type="button" class="close">&times;</button>
<?php
$spotter_item = $spotter_array[0];
date_default_timezone_set('UTC');
if (isset($spotter_item['image_thumbnail']) && $spotter_item['image_thumbnail'] != "")
{
	if ($spotter_item['image_source'] == 'flickr' || $spotter_item['image_source'] == 'wikimedia' || $spotter_item['image_source'] == 'devianart') {
	    $image = preg_replace("/^http:/i","https:",$spotter_item['image_thumbnail']);
	} else $image = $spotter_item['image_thumbnail'];

}
/* else {
	$image = "images/placeholder_thumb.png";
} */

print '<div class="top">';
if (isset($image)) {
    print '<div class="left"><img src="'.$image.'" alt="'.$spotter_item['registration'].' '.$spotter_item['aircraft_name'].'" title="'.$spotter_item['registration'].' '.$spotter_item['aircraft_name'].' Image &copy; '.$spotter_item['image_copyright'].'"/><br />Image &copy; '.$spotter_item['image_copyright'].'</div>';
}
print '<div class="right"><div class="callsign-details"><div class="callsign"><a href="/redirect/'.$spotter_item['flightaware_id'].'" target="_blank">'.$spotter_item['ident'].'</a></div>';
if (isset($spotter_item['airline_name'])) print '<div class="airline">'.$spotter_item['airline_name'].'</div>';
print '</div>';
print '<div class="nomobile airports"><div class="airport"><span class="code"><a href="/airport/'.$spotter_item['departure_airport'].'" target="_blank">'.$spotter_item['departure_airport'].'</a></span>'.$spotter_item['departure_airport_city'].' '.$spotter_item['departure_airport_country'];
if (isset($spotter_item['departure_airport_time'])) print '<br /><span class="time">'.$spotter_item['departure_airport_time'].'</span>';
print '</div><i class="fa fa-long-arrow-right"></i><div class="airport">';
print '<span class="code"><a href="/airport/'.$spotter_item['arrival_airport'].'" target="_blank">'.$spotter_item['arrival_airport'].'</a></span>'.$spotter_item['arrival_airport_city'].' '.$spotter_item['arrival_airport_country'];
if (isset($spotter_item['arrival_airport_time'])) print '<br /><span class="time">'.$spotter_item['arrival_airport_time'].'</span>';
print '</div></div>';
//if (isset($spotter_item['route_stop'])) print 'Route stop : '.$spotter_item['route_stop'];
print '</div></div>';
print '<div class="details"><div class="mobile airports"><div class="airport">';
print '<span class="code"><a href="/airport/'.$spotter_item['departure_airport'].'" target="_blank">'.$spotter_item['departure_airport'].'</a></span>'.$spotter_item['departure_airport_city'].' '.$spotter_item['departure_airport_country'];
print '</div><i class="fa fa-long-arrow-right"></i><div class="airport">';
print '<span class="code"><a href="/airport/'.$spotter_item['arrival_airport'].'" target="_blank">'.$spotter_item['arrival_airport'].'</a></span>'.$spotter_item['arrival_airport_city'].' '.$spotter_item['arrival_airport_country'];
print '</div></div><div>';
print '<span>Aircraft</span>';
if (isset($spotter_item['aircraft_wiki'])) print '<a href="'.$spotter_item['aircraft_wiki'].'">'.$spotter_item['aircraft_name'].'</a>';
print $spotter_item['aircraft_manufacturer'].' '.$spotter_item['aircraft_name'];
print '</div>';
print '<div><span>Altitude</span>';
print $spotter_item['altitude'].'00 feet - '.round($spotter_item['altitude']*30.48).' m (FL'.$spotter_item['altitude'].')';
print '</div>';
if (isset($spotter_item['registration']) && $spotter_item['registration'] != '') print '<div><span>Registration</span><a href="/registration/'.$spotter_item['registration'].'" target="_blank">'.$spotter_item['registration'].'</a></div>';
print '<div><span>Speed</span>'.$spotter_item['ground_speed'].' knots - '.round($spotter_item['ground_speed']*1.852).' km/h</div>';
print '<div><span>Coordinates</span>'.$spotter_item['latitude'].', '.$spotter_item['longitude'].'</div>';
print '<div><span>Heading</span>'.$spotter_item['heading'].'</div>';
if (isset($spotter_item['pilot_name'])) {
	print '<div><span>Pilot</span>';
	if (isset($spotter_item['pilot_id'])) print $spotter_item['pilot_name']." (".$spotter_item['pilot_id'].")";
	else print $spotter_item['pilot_name'];
	print '</div>';
}

if (isset($spotter_item['aircraft_owner'])) {
	print '<div><span>Owner</span>';
	print $spotter_item['aircraft_owner'];
	print '</div>';
}

print '</div>';
if (isset($spotter_item['waypoints']) && $spotter_item['waypoints'] != '') print '<div class="waypoints"><span>Route</span>'.$spotter_item['waypoints'].'</div>';
if (isset($spotter_item['acars']['message'])) print '<div class="acars"><span>Latest ACARS message</span>'.trim(str_replace(array("\r\n","\r","\n","\\r","\\n","\\r\\n"),'<br/>',$spotter_item['acars']['message'])).'</div>';
if (isset($spotter_item['squawk']) && $spotter_item['squawk'] != '' && $spotter_item['squawk'] != 0) print '<div class="bottom">Squawk : '.$spotter_item['squawk'].' - '.$spotter_item['squawk_usage'].'</div>';
print '</div>';
?>
</div>

<?php
/*
</body>
</html>
*/
?>