<?php
require_once('require/class.Connection.php');
require_once('require/class.GeoidHeight.php');
require_once('require/class.Language.php');
try {
	$GeoidHeight = new GeoidHeight();
} catch (Exception $e) {
	$title = _("Geoid Height Calculator");
	require_once('header.php');
	print '<div class="info column">';
	print '<h1>'._("Geoid Height Calculator").'</h1>';
	print '</div>';
	print '<p>Not available</p>';
	if (isset($globalDebug) && $globalDebug) echo '<p>'.$e.'</p>';
	require_once('footer.php');
	exit();
}
$title = _("Geoid Height Calculator");
require_once('header.php');

$page_url = $globalURL.'/tools-geoid';

$latitude = filter_input(INPUT_POST,'latitude',FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
$longitude = filter_input(INPUT_POST,'longitude',FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
$altitude = filter_input(INPUT_POST,'altitude',FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);

print '<div class="info column">';
print '<h1>'._("Geoid Height Calculator").'</h1>';
print '</div>';

print '<div class="table column">';
print '<p>'._("Calculate geoid at a point. GPS use a theoretical sea level estimated by a World Geodetic System (WGS84), Earth Gravity Model is better.").'</p>';
print '<div class="pagination">';
print '<form method="post" class="form-horizontal">';
print '<div class="form-group">';
print '<label class="control-label col-sm-2" for="latitude">'._("Latitude").'</label>';
print '<div class="col-sm-10">';
print '<input type="text" class="form-control" name="latitude" id="latitude" value="'.$latitude.'">';
print '</div>';
print '</div>';
print '<div class="form-group">';
print '<label class="control-label col-sm-2" for="longitude">'._("Longitude").'</label>';
print '<div class="col-sm-10">';
print '<input type="text" class="form-control" name="longitude" id="longitude" value="'.$longitude.'">';
print '</div>';
print '</div>';
print '<div class="form-group">';
print '<label class="control-label col-sm-2" for="altitude">'._("GPS Elevation").'</label>';
print '<div class="col-sm-10">';
print '<input type="text" class="form-control" name="altitude" id="altitude" value="'.$altitude.'">';
print '</div>';
print '</div>';
print '<div class="form-group">';
print '<div class="col-sm-offset-1 col-sm-10">';
print '<button type="submit" class="btn btn-primary">Submit</button>';
print '</div>';
print '</div>';
print '</form>';

if ($latitude != '' && $longitude != '') {
	$globalDebug = FALSE;
	$geoid = $GeoidHeight->get($latitude,$longitude);
	print '<div class="row">';
	print '<div class="col-md-3 col-md-offset-5">';
	print '<div class="col-sm-6"><b>Geoid</b></div>';
	print '<div class="col-sm-6">'.$geoid.'</div>';
	if ($altitude != '') {
		print '<div class="col-sm-6"><b>AMSL Elevation</b></div>';
		print '<div class="col-sm-6">'.round($altitude-$geoid,3).'</div>';
	}
	print '<div class="col-sm-6"><b>Earth Gravity Model</b></div>';
	if (isset($globalGeoidSource) && $globalGeoidSource != '') $geoidsource = $globalGeoidSource;
	else $geoidsource = 'EGM96-15';
	print '<div class="col-sm-6">'.$geoidsource.'</div>';
	print '</div>';
}

print '</div>';
print '</div>';

require_once('footer.php');
?>