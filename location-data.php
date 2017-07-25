<?php
require_once('require/class.Connection.php');
require_once('require/class.Language.php');
require_once('require/class.Source.php');
$Source = new Source();

if (isset($_GET['sourceid'])) {
	$sourceid = filter_input(INPUT_GET,'sourceid',FILTER_SANITIZE_NUMBER_INT);
	$source_data = $Source->getLocationInfoById($sourceid);
	if (isset($source_data[0])) {
 ?>
<div class="alldetails">
<button type="button" class="close">&times;</button>
<?php
$spotter_item = $source_data[0];

date_default_timezone_set('UTC');

print '<div class="top">';
if ($spotter_item['name'] != '') print '<div class="right"><div class="callsign-details"><div class="callsign">'.$spotter_item['name'].'</div>';
elseif ($spotter_item['location_id'] != 0) print '<div class="right"><div class="callsign-details"><div class="callsign">'.$spotter_item['location_id'].'</div>';
elseif ($spotter_item['type'] == 'lightning') print '<div class="right"><div class="callsign-details"><div class="callsign">'._("Lightning").'</div>';
elseif ($spotter_item['type'] == 'wx') print '<div class="right"><div class="callsign-details"><div class="callsign">'._("Weather Station").'</div>';
elseif ($spotter_item['type'] == 'fires') print '<div class="right"><div class="callsign-details"><div class="callsign">'._("Fire").'</div>';
else print '<div class="right"><div class="callsign-details"><div class="callsign"></div>';
print '</div>';

print '</div></div>';
print '<div class="details"><div class="mobile airports"><div class="airport">';
print '</div></div>';

if ($spotter_item['type'] != 'fires' && $spotter_item['type'] != 'lightning') {
	print '<div>';
	print '<span>'._("Altitude").'</span>';
	print $spotter_item['altitude'];
	print '</div>';
}

print '<div><span>'._("Last Seen").'</span>';
print $spotter_item['last_seen'].' UTC';
print '</div>';

if ($spotter_item['city'] != '') print '<div><span>'._("City").'</span>'.$spotter_item['city'].'</div>';
if ($spotter_item['country'] !='') print '<div><span>'._("Country").'</span>'.$spotter_item['country'].'</div>';
print '<div><span>'._("Coordinates").'</span>'.round($spotter_item['latitude'],4).', '.round($spotter_item['longitude'],4).'</div>';
/*
if ($spotter_item['atc_range'] > 0) {
    print '<div><span>'._("Range").'</span>';
    print $spotter_item['atc_range'];
    print '</div>';
}
*/
if ($spotter_item['type'] == 'wx') {
	$weather = json_decode($spotter_item['description'],true);
	//print_r($weather);
	if (isset($weather['temp'])) print '<div><span>'._("Temperature").'</span>'.$weather['temp'].'°C</div>';
	if (isset($weather['pressure'])) print '<div><span>'._("Pressure").'</span>'.$weather['pressure'].'hPa</div>';
	if (isset($weather['wind_gust'])) print '<div><span>'._("Wind Gust").'</span>'.$weather['wind_gust'].' km/h</div>';
	if (isset($weather['humidity'])) print '<div><span>'._("Humidity").'</span>'.$weather['humidity'].'%</div>';
	if (isset($weather['rain'])) print '<div><span>'._("Rain").'</span>'.$weather['rain'].' mm</div>';
	if (isset($weather['precipitation'])) print '<div><span>'._("Precipitation 24H").'</span>'.$weather['precipitation'].' mm</div>';
	if (isset($weather['precipitation24h'])) print '<div><span>'._("Precipitation Today").'</span>'.$weather['precipitation24h'].' mm</div>';
	$spotter_item['description'] = $weather['comment'];
} elseif ($spotter_item['type'] == 'fires') {
	//print_r(json_decode($spotter_item['description'],true));
}
print '</div>';
if ($spotter_item['type'] != 'wx' && $spotter_item['type'] != 'fires') {
	if ($spotter_item['description'] != '') {
		print '<div class="notamtext"><span>'._("Info").'</span>';
		print $spotter_item['description'];
		print '</div>';
	}
}


print '</div>';
}
print '</div>';
}
?>