<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.NOTAM.php');
require_once('require/class.Language.php');
$Spotter = new Spotter();
$NOTAM = new NOTAM();
$title = _("Parse NOTAM messages");
require_once('header.php');

$page_url = $globalURL.'/tools-notam';

$message = filter_input(INPUT_POST,'notam_message',FILTER_SANITIZE_STRING);

print '<div class="info column">';
print '<h1>'._("Parse NOTAM messages").'</h1>';
print '</div>';

print '<div class="table column">';	
print '<p>'._("Parse NOTAM messages and translate them in human readable format.").'</p>';
print '<div class="pagination">';
print '<form method="post">';
print '<fieldset class="form-group">';
print '<label for="notam_message">'._("NOTAM Message").'</label>';
print '<textarea class="form-control" name="notam_message" id="notam_message" rows="5">';
if ($message != '') print $message;
print '</textarea>';
print '</fieldset>';
print '<button type="submit" class="btn btn-primary">Submit</button>';
print '</form>';
if ($message != '') {
	$globalDebug = FALSE;
	$notam_parse = $NOTAM->parse($message);
	if (!empty($notam_parse)) {
		print '<p>'._("NOTAM message in human readable format:").'</p>';
		foreach ($notam_parse as $key => $value) {
			print '<b>'.strtoupper(str_replace('_',' ',$key)).'</b>: '.$value.'<br />';
		}
	} else {
		print '<p>'._("This NOTAM message can't be translated in human readable format :(").'</p>';
	}
	//var_dump($parsed_msg);
}

print '</div>';
print '</div>';

require_once('footer.php');
?>