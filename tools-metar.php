<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.METAR.php');
require_once('require/class.Language.php');
$Spotter = new Spotter();
$METAR = new METAR();
$title = _("Parse METAR messages");
require_once('header.php');

$page_url = $globalURL.'/tools-metar';

$message = filter_input(INPUT_POST,'metar_message',FILTER_SANITIZE_STRING);

print '<div class="info column">';
print '<h1>'._("Parse METAR messages").'</h1>';
print '</div>';

print '<div class="table column">';	
print '<p>'._("Parse METAR messages and translate them in human readable format.").'</p>';
print '<div class="pagination">';
print '<form method="post">';
print '<fieldset class="form-group">';
print '<label for="metar_message">'._("METAR Message").'</label>';
print '<textarea class="form-control" name="metar_message" id="metar_message" rows="5">';
if ($message != '') print $message;
print '</textarea>';
print '</fieldset>';
print '<button type="submit" class="btn btn-primary">Submit</button>';
print '</form>';
if ($message != '') {
	$globalDebug = FALSE;
	$metar_parse = $METAR->parse($message);
	if (!empty($metar_parse)) {
		//print_r($metar_parse);
		print '<p>'._("METAR message in human readable format:").'</p>';
		if (isset($metar_parse['location'])) {
			print '<b>'._("Location:").'</b> ';
			print $metar_parse['location']." ";
		}
		if (isset($metar_parse['wind'])) {
			print '<b>'._("Wind:").'</b> ';
			if (isset($metar_parse['wind']['direction'])) {
				$direction = $Spotter->parseDirection($metar_parse['wind']['direction']);
				print $direction[0]['direction_fullname'];
				print ' ('.$metar_parse['wind']['direction'].'°) ';
			}
			if (isset($metar_parse['wind']['speed'])) {
				print $metar_parse['wind']['speed'].' m/s';
			}
			print ' ';
		}
		if (isset($metar_parse['visibility'])) {
			print '<b>'._("Visibility:").'</b> '.$metar_parse['visibility'].' m'." ";
		}
		if (isset($metar_parse['weather'])) {
			print '<b>'._("Weather:").'</b> '.$metar_parse['weather']." ";
		}
		if (isset($metar_parse['cloud'])) {
			print '<b>'._("Cloud:").'</b> ';
			foreach ($metar_parse['cloud'] as $key => $cloud) {
				if ($key > 0) print ' / ';
				print $cloud['type'].' at '.$cloud['level'].' m';
			}
			print " ";
		}
		if (isset($metar_parse['temperature'])) {
			print '<b>'._("Temperature:").'</b> '.$metar_parse['temperature'].' °C'." ";
		}
		if (isset($metar_parse['dew'])) {
			print '<b>'._("Dew point:").'</b> '.$metar_parse['dew'].' °C'." ";
		}
		if (isset($metar_parse['temperature']) && isset($metar_parse['dew'])) {
			$humidity = round(100 * pow((112 - (0.1 * $metar_parse['temperature']) + $metar_parse['dew']) / (112 + (0.9 * $metar_parse['temperature'])), 8),1);
			print '<b>'._("Humidity:").'</b> '.$humidity.'%'." ";
		}
		if (isset($metar_parse['QNH'])) {
			print '<b>'._("Pressure:").'</b> '.$metar_parse['QNH'].' hPa';
		}
	} else {
		print '<p>'._("This METAR message can't be translated in human readable format :(").'</p>';
	}
	//var_dump($parsed_msg);
}

print '</div>';
print '</div>';

require_once('footer.php');
?>