<?php
require_once('require/class.Connection.php');
require_once('require/class.Language.php');
require_once('require/class.NOTAM.php');
?>
<div class="alldetails">
<button type="button" class="close">&times;</button>
<?php

$notamref = filter_input(INPUT_GET,'notam',FILTER_SANITIZE_STRING);
$notamref = urldecode($notamref);
$NOTAM = new NOTAM();
$notam = $NOTAM->getNOTAMbyRef($notamref);
//print_r($notam);

date_default_timezone_set('UTC');
print '<div class="top">';
print '<div class="right"><div class="callsign-details"><div class="callsign">'.$notamref.'</a></div>';
print '</div>';

if ($notam['permanent'] == 0 && $notam['date_begin'] != '') {
	print '<div class="date">';
	print '<span>'._("Begin/End").'</span>';
	print $notam['date_begin'];
	print ' <i class="fa fa-long-arrow-right"></i> ';
	print $notam['date_end'];
	print '</div>';
}
print '<div class="details">';
if ($notam['fir'] != '') {
	print '<div>';
	print '<span>'._("FIR").'</span>';
	print $notam['fir'];
	print '</div>';
}
if ($notam['code'] != '') {
	print '<div>';
	print '<span>'._("Code").'</span>';
	print $notam['code'];
	print '</div>';
}
if ($notam['title'] != '') {
	print '<div>';
	print '<span>'._("Subject").'</span>';
	print $notam['title'];
	print '</div>';
}
if ($notam['scope'] != '') {
	print '<div>';
	print '<span>'._("Scope").'</span>';
	print $notam['scope'];
	print '</div>';
}
//if ($notam['lower_limit'] != '') {
	print '<div>';
	print '<span>'._("Limits").'</span>';
	print 'FL'.$notam['lower_limit'].'/FL'.$notam['upper_limit'].' ('.$notam['radius'].'Nm)';
	print '</div>';
//}
print '</div>';
print '<div class="notamtext">';
print '<span>'._("Text").'</span>';
print $notam['notam_text'];
print '</div>';

print '</div>';
?>
</div>
