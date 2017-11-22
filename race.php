<?php
require_once('require/class.Connection.php');
require_once('require/class.Marine.php');
require_once('require/class.Language.php');
require_once('require/class.Stats.php');
require_once('require/class.Common.php');

if (isset($_POST['race']))
{
	header('Location: '.$globalURL.'/race/'.filter_input(INPUT_POST,'race',FILTER_SANITIZE_STRING));
//} else if (isset($_GET['airport'])){
} else {
	$Marine = new Marine();
	$Stats = new Stats();
	$Common = new Common();
	$title = _("Races");
	require_once('header.php');
	print '<div class="column">';
	print '<h1>'._("Races").'</h1>';
	//$race_names = $Stats->getAllPilotNames();
	//if (empty($race_names)) {
		$race_names = $Marine->getAllRaceNames();
	//}
	//ksort($race_names);
	$previous = null;
	print '<div class="alphabet-legend">';
	foreach($race_names as $value) {
		$firstLetter = strtoupper($Common->replace_mb_substr($value['race_name'], 0, 1));
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
	foreach($race_names as $value) {
		$firstLetter = strtoupper($Common->replace_mb_substr($value['race_name'], 0, 1));
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
			if (isset($value['race_id']) && $value['race_id'] != '') print '<a href="'.$globalURL.'/marine/race/'.$value['race_id'].'">'.$value['race_name'].' ('.$value['race_id'].')';
			else print '<a href="'.$globalURL.'/race/'.$value['race_name'].'">'.$value['race_name'];
			print '</a>';
			print '</div>';
		}
	}
	print '</div>';
}

require_once('footer.php');
?>