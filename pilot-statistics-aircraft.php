<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
if (!isset($_GET['owner'])) {
        header('Location: '.$globalURL.'/owner');
        die();
}
$Spotter = new Spotter();
$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
$pilot = filter_input(INPUT_GET,'pilot',FILTER_SANITIZE_STRING);
$year = filter_input(INPUT_GET,'year',FILTER_SANITIZE_NUMBER_INT);
$month = filter_input(INPUT_GET,'month',FILTER_SANITIZE_NUMBER_INT);
$filter = array();
if ($year != '') $filter = array_merge($filter,array('year' => $year));
if ($month != '') $filter = array_merge($filter,array('month' => $month));
if ($sort != '') {
	$spotter_array = $Spotter->getSpotterDataByPilot($pilot,"0,1", $sort,$filter);
} else {
	$spotter_array = $Spotter->getSpotterDataByPilot($pilot,"0,1", '',$filter);
}

if (!empty($spotter_array))
{
	$title = sprintf(_("Most Common Aircraft of %s"),$spotter_array[0]['pilot_name']);
	require_once('header.php');
	print '<div class="info column">';
	print '<h1>'.$spotter_array[0]['pilot_name'].'</h1>';
//	print '<div><span class="label">'._("Ident").'</span>'.$spotter_array[0]['ident'].'</div>';
//	print '<div><span class="label">'._("Airline").'</span><a href="'.$globalURL.'/airline/'.$spotter_array[0]['airline_icao'].'">'.$spotter_array[0]['airline_name'].'</a></div>'; 
	print '</div>';

	include('pilot-sub-menu.php');
	print '<div class="column">';
	print '<h2>'._("Most Common Aircraft").'</h2>';
	print '<p>'.sprintf(_("The statistic below shows the most common aircraft of flights piloted by <strong>%s</strong>."),$spotter_array[0]['pilot_name']).'</p>';

	$aircraft_array = $Spotter->countAllAircraftTypesByPilot($pilot,$filter);
	if (!empty($aircraft_array))
	{
		print '<div class="table-responsive">';
		print '<table class="common-type table-striped">';
		print '<thead>';
		print '<th></th>';
		print '<th>'._("Aircraft Type").'</th>';
		print '<th>'._("# of times").'</th>';
		print '</thead>';
		print '<tbody>';
		$i = 1;
		foreach($aircraft_array as $aircraft_item)
		{
			print '<tr>';
			print '<td><strong>'.$i.'</strong></td>';
			print '<td>';
			print '<a href="'.$globalURL.'/aircraft/'.$aircraft_item['aircraft_icao'].'">'.$aircraft_item['aircraft_manufacturer'].' '.$aircraft_item['aircraft_name'].' ('.$aircraft_item['aircraft_icao'].')</a>';
			print '</td>';
			print '<td>';
			print $aircraft_item['aircraft_icao_count'];
			print '</td>';
			print '</tr>';
			$i++;
		}
		print '<tbody>';
		print '</table>';
		print '</div>';
	}
	print '</div>';
} else {
	$title = _("Pilot");
	require_once('header.php');
	print '<h1>'._("Error").'</h1>';
	print '<p>'._("Sorry, this pilot is not in the database. :(").'</p>';
}

require_once('footer.php');
?>