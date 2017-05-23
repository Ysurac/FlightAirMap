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
else print '<div class="right"><div class="callsign-details"><div class="callsign">'.$spotter_item['location_id'].'</div>';
print '</div>';

print '</div></div>';
print '<div class="details"><div class="mobile airports"><div class="airport">';
print '</div></div><div>';

print '<span>'._("Altitude").'</span>';
print $spotter_item['altitude'];
print '</div>';

print '<div><span>'._("Last Seen").'</span>';
print $spotter_item['last_seen'];
print '</div>';

if ($spotter_item['city'] != '') print '<div><span>'._("City").'</span>'.$spotter_item['city'].'</div>';
if ($spotter_item['country'] !='') print '<div><span>'._("Country").'</span>'.$spotter_item['country'].'</div>';
print '<div><span>'._("Coordinates").'</span>'.round($spotter_item['latitude'],3).', '.round($spotter_item['longitude'],3).'</div>';
/*
if ($spotter_item['atc_range'] > 0) {
    print '<div><span>'._("Range").'</span>';
    print $spotter_item['atc_range'];
    print '</div>';
}
*/
print '</div>';

if ($spotter_item['description'] != '') {
    print '<div class="notamtext"><span>'._("Info").'</span>';
    print $spotter_item['description'];
    print '</div>';
}

print '</div>';
}
print '</div>';
}
?>