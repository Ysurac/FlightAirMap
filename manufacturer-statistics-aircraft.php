<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
if (!isset($_GET['aircraft_manufacturer'])) {
        header('Location: '.$globalURL.'/manufacturer');
        die();
}
$Spotter = new Spotter();
$manufacturer = ucwords(str_replace("-", " ", urldecode(filter_input(INPUT_GET,'aircraft_manufacturer',FILTER_SANITIZE_STRING))));
$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
$spotter_array = $Spotter->getSpotterDataByManufacturer($manufacturer,"0,1", $sort);

if (!empty($spotter_array))
{
	$title = sprintf(_("Most Common Aircraft from %s"),$manufacturer);

	require_once('header.php');
	print '<div class="select-item">';
	print '<form action="'.$globalURL.'/manufacturer" method="post">';
	print '<select name="aircraft_manufacturer" class="selectpicker" data-live-search="true">';
	$Stats = new Stats();
	$all_manufacturers = $Stats->getAllManufacturers();
	if (empty($all_manufacturers)) $all_manufacturers = $Spotter->getAllManufacturers();
	foreach($all_manufacturers as $all_manufacturer)
	{
		if($manufacturer == ucwords($all_manufacturer['aircraft_manufacturer']))
		{
			print '<option value="'.strtolower(str_replace(" ", "-", $all_manufacturer['aircraft_manufacturer'])).'" selected="selected">'.$all_manufacturer['aircraft_manufacturer'].'</option>';
		} else {
			print '<option value="'.strtolower(str_replace(" ", "-", $all_manufacturer['aircraft_manufacturer'])).'">'.$all_manufacturer['aircraft_manufacturer'].'</option>';
		}
	}
	print '</select>';
	print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
	print '</form>';
	print '</div>';

	print '<div class="info column">';
	print '<h1>'.$manufacturer.'</h1>';
	print '</div>';

	include('manufacturer-sub-menu.php');
	print '<div class="column">';
	print '<h2>'._("Most Common Aircraft").'</h2>';
	print '<p>'.sprintf(_("The statistic below shows the most common aircraft of flights from <strong>%s</strong>."),$manufacturer).'</p>';

	$aircraft_array = $Spotter->countAllAircraftTypesByManufacturer($manufacturer);
	if (!empty($aircraft_array))
	{
		print '<div class="table-responsive">';
		print '<table class="common-type table-striped">';
		print '<thead>';
		print '<th></th>';
		print '<th>'._("Aircraft Type").'</th>';
		print '<th>'._("# of times").'</th>';
		print '<th></th>';
		print '</thead>';
		print '<tbody>';
		$i = 1;
		foreach($aircraft_array as $aircraft_item)
		{
			print '<tr>';
			print '<td><strong>'.$i.'</strong></td>';
			print '<td>';
			print '<a href="'.$globalURL.'/aircraft/'.$aircraft_item['aircraft_icao'].'">'.$aircraft_item['aircraft_name'].' ('.$aircraft_item['aircraft_icao'].')</a>';
			print '</td>';
			print '<td>';
			print $aircraft_item['aircraft_icao_count'];
			print '</td>';
			print '<td><a href="'.$globalURL.'/search?aircraft='.$aircraft_item['aircraft_icao'].'&manufacturer='.$manufacturer.'">'._("Search flights").'</a></td>';
			print '</tr>';
			$i++;
		}
		print '<tbody>';
		print '</table>';
		print '</div>';
	}
	print '</div>';
} else {
	$title = _("Manufacturer");
	require_once('header.php');
	print '<h1>'._("Error").'</h1>';
	print '<p>'._("Sorry, the aircraft manufacturer does not exist in this database. :(").'</p>';
}

require_once('footer.php');
?>