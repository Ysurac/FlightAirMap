<?php
require_once('require/class.Connection.php');
require_once('require/class.Language.php');
require_once('require/class.NOTAM.php');
?>
<div class="alldetails">
<button type="button" class="close">&times;</button>
<?php

$airspaceid = filter_input(INPUT_GET,'airspace',FILTER_SANITIZE_NUMBER_INT);
//$notamref = urldecode($notamref);
if ($globalDBdriver == 'mysql') {
	$query = "SELECT * FROM airspace WHERE ogr_fid = :id";
} else {
	$query = "SELECT * FROM airspace WHERE ogc_fid = :id";
}
$Connection = new Connection();
try {
	$sth = $Connection->db->prepare($query);
	$sth->execute(array(':id' => $airspaceid));
} catch(PDOException $e) {
	echo "error";
}
$result=$sth->fetchAll(PDO::FETCH_ASSOC);
$airspace = $result[0];
date_default_timezone_set('UTC');
print '<div class="top">';
if (isset($airspace['name'])) $airspace['title'] = $airspace['name']; 
print '<div class="right"><div class="callsign-details"><div class="callsign">'.$airspace['title'].'</a></div>';
print '</div>';
print '<div class="details">';

if (isset($airspace['type'])) {
	print '<div>';
	print '<span>'._("Type").'</span>';
	print $airspace['type'];
	print '</div>';
}

if ($airspace['class'] != '') {
	print '<div>';
	print '<span>'._("Class").'</span>';
	print $airspace['class'];
	print '</div>';
}

if (isset($airspace['ceiling'])) $airspace['tops'] = $airspace['ceiling'];
print '<div>';
print '<span>'._("Tops").'</span>';
print $airspace['tops'];
print '</div>';

if (isset($airspace['floor'])) $airspace['base'] = $airspace['floor'];
print '<div>';
print '<span>'._("Base").'</span>';
print $airspace['base'];
print '</div>';
print '</div>';
/*
print '<div class="notamtext">';
print '<span>'._("Text").'</span>';
print $notam['notam_text'];
print '</div>';
*/
print '</div>';
?>
</div>
