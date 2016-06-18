<?php
require_once('require/class.Connection.php');
require_once('require/class.ACARS.php');
require_once('require/class.Language.php');
$ACARS = new ACARS();
$title = _("Parse ACARS messages");
require_once('header.php');

$page_url = $globalURL.'/tools-acars';

$message = filter_input(INPUT_POST,'acars_message',FILTER_SANITIZE_STRING);

print '<div class="info column">';
print '<h1>'._("Parse ACARS messages").'</h1>';
print '</div>';

print '<div class="table column">';	
print '<p>'._("Parse ACARS messages and translate them in human readable format.").'</p>';
print '<div class="pagination">';
print '<form method="post">';
print '<fieldset class="form-group">';
print '<label for="acars_message">'._("ACARS Message").'</label>';
print '<textarea class="form-control" name="acars_message" id="acars_message" rows="5">';
if ($message != '') print $message;
print '</textarea>';
print '</fieldset>';
print '<button type="submit" class="btn btn-primary">Submit</button>';
print '</form>';
if ($message != '') {
	$globalDebug = FALSE;
	$parsed_msg = $ACARS->parse($message);
	if (isset($parsed_msg['decode'])) {
		print '<p>'._("ACARS message in human readable format:").'</p>';
		foreach ($parsed_msg['decode'] as $value => $data) {
			print '<b>'.$value.'</b>: '.$data.' ';
		}
	} else {
		print '<p>'._("This ACARS message can't be translated in human readable format :(").'</p>';
	}
	//var_dump($parsed_msg);
}

print '</div>';
print '</div>';

require_once('footer.php');
?>