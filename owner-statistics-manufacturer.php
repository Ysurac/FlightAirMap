<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.SpotterArchive.php');
require_once('require/class.Language.php');
if (!isset($_GET['owner'])) {
        header('Location: '.$globalURL.'/owner');
        die();
}
$Spotter = new Spotter();
$SpotterArchive = new SpotterArchive();
$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
$owner = urldecode(filter_input(INPUT_GET,'owner',FILTER_SANITIZE_STRING));
$year = filter_input(INPUT_GET,'year',FILTER_SANITIZE_NUMBER_INT);
$month = filter_input(INPUT_GET,'month',FILTER_SANITIZE_NUMBER_INT);
$filter = array();
if ($year != '') $filter = array_merge($filter,array('year' => $year));
if ($month != '') $filter = array_merge($filter,array('month' => $month));
$archive = false;
$spotter_array = $Spotter->getSpotterDataByOwner($owner,"0,1", $sort,$filter);
if (empty($spotter_array) && isset($globalArchiveResults) && $globalArchiveResults) {
	$spotter_array = $SpotterArchive->getSpotterDataByOwner($owner,"0,1", $sort,$filter);
}
if (!empty($spotter_array))
{
	$title = sprintf(_("Most Common Aircraft Manufacturer of %s"),$spotter_array[0]['aircraft_owner']);
	require_once('header.php');
	print '<div class="info column">';
	print '<h1>'.$spotter_array[0]['aircraft_owner'].'</h1>';
//	print '<div><span class="label">'._("Ident").'</span>'.$spotter_array[0]['ident'].'</div>';
//	print '<div><span class="label">'._("Airline").'</span><a href="'.$globalURL.'/airline/'.$spotter_array[0]['airline_icao'].'">'.$spotter_array[0]['airline_name'].'</a></div>'; 
	print '</div>';

	include('owner-sub-menu.php');
	print '<div class="column">';
	print '<h2>'._("Most Common Aircraft Manufacturer").'</h2>';
	print '<p>'.sprintf(_("The statistic below shows the most common Aircraft Manufacturer of flights owned by <strong>%s</strong>."),$spotter_array[0]['aircraft_owner']).'</p>';

	if ($archive === false) {
		$manufacturers_array = $Spotter->countAllAircraftManufacturerByOwner($owner,$filter);
	} else {
		$manufacturers_array = $SpotterArchive->countAllAircraftManufacturerByOwner($owner,$filter);
	}
	if (!empty($manufacturers_array))
	{
		print '<div class="table-responsive">';
		print '<table class="common-manufacturer table-striped">';
		print '<thead>';
		print '<th></th>';
		print '<th>'._("Aircraft Manufacturer").'</th>';
		print '<th>'._("# of times").'</th>';
		print '<th></th>';
		print '</thead>';
		print '<tbody>';
		$i = 1;
		foreach($manufacturers_array as $manufacturer_item)
		{
			print '<tr>';
			print '<td><strong>'.$i.'</strong></td>';
			print '<td>';
			print '<a href="'.$globalURL.'/manufacturer/'.strtolower(str_replace(" ", "-", $manufacturer_item['aircraft_manufacturer'])).'">'.$manufacturer_item['aircraft_manufacturer'].'</a>';
			print '</td>';
			print '<td>';
			print $manufacturer_item['aircraft_manufacturer_count'];
			print '</td>';
			print '<td><a href="'.$globalURL.'/search?manufacturer='.strtolower(str_replace(" ", "-", $manufacturer_item['aircraft_manufacturer'])).'&owner='.$owner.'">'._("Search flights").'</a></td>';
			print '</tr>';
			$i++;
		}
		print '<tbody>';
		print '</table>';
		print '</div>';
	}
	print '</div>';
} else {
	$title = _("Owner");
	require_once('header.php');
	print '<h1>'._("Error").'</h1>';
	print '<p>'._("Sorry, this owner is not in the database. :(").'</p>';
}

require_once('footer.php');
?>