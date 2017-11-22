<?php
require_once('require/class.Connection.php');
require_once('require/class.Marine.php');
require_once('require/class.Language.php');
require_once('require/class.Stats.php');
require_once('require/class.Common.php');

if (isset($_POST['captain']))
{
	header('Location: '.$globalURL.'/captain/'.filter_input(INPUT_POST,'captain',FILTER_SANITIZE_STRING));
//} else if (isset($_GET['airport'])){
} else {
	$Marine = new Marine();
	$Stats = new Stats();
	$Common = new Common();
	$title = _("Captains");
	require_once('header.php');
	print '<div class="column">';
	print '<h1>'._("Captains").'</h1>';
	//$captain_names = $Stats->getAllPilotNames();
	//if (empty($captain_names)) {
		$captain_names = $Marine->getAllCaptainNames();
	//}
	//ksort($captain_names);
	$previous = null;
	print '<div class="alphabet-legend">';
	foreach($captain_names as $value) {
		$firstLetter = strtoupper($Common->replace_mb_substr($value['captain_name'], 0, 1));
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
	foreach($captain_names as $value) {
		$firstLetter = strtoupper($Common->replace_mb_substr($value['captain_name'], 0, 1));
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
			if (isset($value['captain_id']) && $value['captain_id'] != '') print '<a href="'.$globalURL.'/marine/captain/'.$value['captain_id'].'">'.$value['captain_name'].' ('.$value['captain_id'].')';
			else print '<a href="'.$globalURL.'/captain/'.$value['captain_name'].'">'.$value['captain_name'];
			print '</a>';
			print '</div>';
		}
	}
	print '</div>';
}

require_once('footer.php');
?>