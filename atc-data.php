<?php
require_once('require/class.Connection.php');
require_once('require/class.Language.php');
require_once('require/class.ATC.php');
$ATC = new ATC();

if (isset($_GET['atcid'])) {
	$atcid = filter_input(INPUT_GET,'atcid',FILTER_SANITIZE_NUMBER_INT);
	$atcident = filter_input(INPUT_GET,'atcident',FILTER_SANITIZE_STRING);
	$atc_data = $ATC->getById($atcid);
	if (!isset($atc_data[0])) $atc_data = $ATC->getByIdent($atcident);
 ?>
<div class="alldetails">
<button type="button" class="close">&times;</button>
<?php
$spotter_item = $atc_data[0];

date_default_timezone_set('UTC');

print '<div class="top">';
print '<div class="right"><div class="callsign-details"><div class="callsign">'.$spotter_item['ident'].'</div>';
print '</div>';

print '</div></div>';
print '<div class="details"><div class="mobile airports"><div class="airport">';
print '</div></div><div>';

if (isset($spotter_item['frequency']) && $spotter_item['frequency'] != '') {
	print '<span>'._("Frequency").'</span>';
	print $spotter_item['frequency'];
	print '</div>';
}
if (isset($spotter_item['ivao_name']) && $spotter_item['ivao_name'] != '') {
	print '<div><span>'._("Name").'</span>';
	print $spotter_item['ivao_name'];
	print '</div>';
}
print '<div><span>'._("Type").'</span>';
print $spotter_item['type'];
print '</div>';

//print '<div><span>'._("Country").'</span>'.$spotter_item['country'].'</div>';
print '<div><span>'._("Coordinates").'</span>'.round($spotter_item['latitude'],3).', '.round($spotter_item['longitude'],3).'</div>';
if ($spotter_item['atc_range'] > 0) {
    print '<div><span>'._("Range").'</span>';
    print $spotter_item['atc_range'];
    print '</div>';
}
print '</div>';
if ($spotter_item['info'] != '') {
    print '<div class="notamtext"><span>'._("Info").'</span>';
    print $spotter_item['info'];
    print '</div>';
}
print '</div>';
}
?>
</div>
