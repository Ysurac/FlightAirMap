<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
require_once('require/class.Stats.php');
require_once('require/class.Common.php');

if (isset($_POST['pilot']))
{
	header('Location: '.$globalURL.'/pilot/'.filter_input(INPUT_POST,'pilot',FILTER_SANITIZE_STRING));
//} else if (isset($_GET['airport'])){
} else {
	$Spotter= new Spotter();
	$Stats = new Stats();
	$Common = new Common();
	$title = _("Pilots");
	require_once('header.php');
	print '<div class="column">';
	print '<h1>'._("Pilots").'</h1>';
	$pilot_names = $Stats->getAllPilotNames();
	if (empty($pilot_names)) {
		$pilot_names = $Spotter->getAllPilotNames();
	}
	//ksort($pilot_names);
	$previous = null;
	print '<div class="alphabet-legend">';
	foreach($pilot_names as $value) {
		$firstLetter = strtoupper($Common->replace_mb_substr($value['pilot_name'], 0, 1));
		if($previous !== $firstLetter && $firstLetter != "'")
		{
			if ($previous !== null){
				print ' | ';
			}
			print '<a href="#'.$firstLetter.'">'.$firstLetter.'</a>';
		}
		if ($firstLetter != "'") $previous = $firstLetter;
	}
	print '</div>';
	$previous = null;
	foreach($pilot_names as $value) {
		$firstLetter = strtoupper($Common->replace_mb_substr($value['pilot_name'], 0, 1));
		if ($firstLetter != "")
		{
			if($previous !== $firstLetter && $firstLetter != "'")
			{
				if ($previous !== null){
					print '</div>';
				}
				print '<a name="'.$firstLetter.'"></a><h4 class="alphabet-header">'.$firstLetter.'</h4><div class="alphabet">';
			}
			if ($firstLetter != "'") $previous = $firstLetter;
			print '<div class="alphabet-item">';
			if (isset($value['pilot_id']) && $value['pilot_id'] != '') print '<a href="'.$globalURL.'/pilot/'.$value['pilot_id'].'">'.$value['pilot_name'].' ('.$value['pilot_id'].')';
			else print '<a href="'.$globalURL.'/pilot/'.$value['pilot_name'].'">'.$value['pilot_name'];
			print '</a>';
			print '</div>';
		}
	}
	print '</div>';
}

require_once('footer.php');
?>