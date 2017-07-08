<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
require_once('require/class.Stats.php');
require_once('require/class.Common.php');

if (isset($_POST['owner']))
{
	header('Location: '.$globalURL.'/owner/'.filter_input(INPUT_POST,'owner',FILTER_SANITIZE_STRING));
//} else if (isset($_GET['airport'])){
} else {
	$Spotter= new Spotter();
	$Stats = new Stats();
	$Common = new Common();
	$title = _("Owners");
	require_once('header.php');
	print '<div class="column">';
	print '<h1>'._("Owners").'</h1>';
	$owner_names = $Stats->getAllOwnerNames();
	if (empty($owner_names)) {
		$owner_names = $Spotter->getAllOwnerNames();
	}
	ksort($owner_names);
	$previous = null;
	print '<div class="alphabet-legend">';
	foreach($owner_names as $value) {
		$firstLetter = $Common->remove_accents(strtoupper($Common->replace_mb_substr($value['owner_name'], 0, 1)));
		if($previous !== $firstLetter && $firstLetter != "'" && $firstLetter != '"')
		{
			if ($previous !== null){
				print ' | ';
			}
			print '<a href="#'.$firstLetter.'">'.$firstLetter.'</a>';
		}
		if ($firstLetter != "'" && $firstLetter != '"') $previous = $firstLetter;
	}
	print '</div>';
	$previous = null;
	foreach($owner_names as $value) {
		$firstLetter = $Common->remove_accents(strtoupper($Common->replace_mb_substr($value['owner_name'], 0, 1)));
		if ($firstLetter != "")
		{
			if($previous !== $firstLetter && $firstLetter != "'" && $firstLetter != '"')
			{
				if ($previous !== null){
					print '</div>';
				}
				print '<a name="'.$firstLetter.'"></a><h4 class="alphabet-header">'.$firstLetter.'</h4><div class="alphabet">';
			}
			if ($firstLetter != "'" && $firstLetter != '"') $previous = $firstLetter;
			print '<div class="alphabet-item">';
			print '<a href="'.$globalURL.'/owner/'.$value['owner_name'].'">';
			print $value['owner_name'];
			print '</a>';
			print '</div>';
		}
	}
	print '</div>';
}

require_once('footer.php');
?>