<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
require_once('require/class.Stats.php');

if (isset($_POST['airline']))
{
	header('Location: '.$globalURL.'/airline/'.$_POST['airline']);
} else {
	$title = "Airlines";
	require_once('header.php');
	$Spotter = new Spotter();
	print '<div class="column">';
	print '<h1>'._("Airlines").'</h1>';
	if (isset($_POST['airline_type'])) {
		$airline_type = filter_input(INPUT_POST,'airline_type',FILTER_SANITIZE_STRING);
		//$airline_names = $Spotter->getAllAirlineNames($airline_type);
	} else {
		//$airline_names = $Spotter->getAllAirlineNames();
		$airline_type = 'all';
	}

	print '<div class="select-item"><form action="'.$globalURL.'/airline" method="post"><select name="airline_type" class="selectpicker" data-live-search="true">';
	print '<option value="all"';
	if ($airline_type == 'all') print 'selected="selected" ';
	print '>'._("All").'</option><option value="passenger"';
	if ($airline_type == 'passenger') print 'selected="selected" ';
	print '>'._("Passenger").'</option><option value="cargo"';
	if ($airline_type == 'cargo') print 'selected="selected" ';
	print '>'._("Cargo").'</option><option value="military"';
	if ($airline_type == 'military') print 'selected="selected" ';
	print '>'._("Military").'</option></select>';
	print '<button type="submit"><i class="fa fa-angle-double-right"></i></button></form></div>';

	if (isset($_POST['airline_type'])) 
	{
		$airline_type = filter_input(INPUT_POST,'airline_type',FILTER_SANITIZE_STRING);
		$airline_names = $Spotter->getAllAirlineNames($airline_type);
	} else {
		$Stats = new Stats();
		//$airline_names = $Spotter->getAllAirlineNames();
		$airline_names = $Stats->getAllAirlineNames();
		if (empty($airline_names)) $airline_names = $Spotter->getAllAirlineNames();
	}
	$previous = null;
	print '<div class="alphabet-legend">';
	foreach($airline_names as $value) 
	{
		//echo $value['airline_name']."\n";
		//echo mb_substr($value['airline_name'],0,1).' - '.$value['airline_name']."\n";
		$firstLetter = mb_strtoupper(mb_substr($value['airline_name'], 0, 1),'UTF-8');
		if($previous !== $firstLetter)
		{
			if ($previous !== null) print ' | ';
			print '<a href="#'.$firstLetter.'">'.$firstLetter.'</a>';
		}
		$previous = $firstLetter;
	}
	print '</div>';
	$previous = null;
	foreach($airline_names as $value) {
		$firstLetter = strtoupper(substr($value['airline_name'], 0, 1));
		if ($firstLetter != "")
		{
			if($previous !== $firstLetter)
			{
				if ($previous !== null) print '</div>';
				print '<a name="'.$firstLetter.'"></a><h4 class="alphabet-header">'.$firstLetter.'</h4><div class="alphabet">';
			}
			$previous = $firstLetter;
			print '<div class="alphabet-airline alphabet-item">';
			print '<a href="'.$globalURL.'/airline/'.$value['airline_icao'].'">';
			if (isset($globalIVAO) && $globalIVAO && (@getimagesize('images/airlines/'.$value['airline_icao'].'.gif') || @getimagesize($globalURL.'/images/airlines/'.$value['airline_icao'].'.gif')))
			{
				print '<img src="'.$globalURL.'/images/airlines/'.$value['airline_icao'].'.gif" alt="'._("Click to see airline activity").'" title="'._("Click to see airline activity").'" /> ';
			} elseif (@getimagesize('images/airlines/'.$value['airline_icao'].'.png') || @getimagesize($globalURL.'/images/airlines/'.$value['airline_icao'].'.png'))
			{
				print '<img src="'.$globalURL.'/images/airlines/'.$value['airline_icao'].'.png" alt="'._("Click to see airline activity").'" title="'._("Click to see airline activity").'" /> ';
			} else {
				print $value['airline_name'];
			}
			print '</a>';
			print '</div>';
		}
	}
	print '</div>';
	require_once('footer.php');
}
?>