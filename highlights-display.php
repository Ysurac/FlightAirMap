<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');
$Spotter = new Spotter();
$title = _("Special Highlights");
require_once('header.php');

//calculuation for the pagination
if(!isset($_GET['limit']))
{
	$limit_start = 0;
	$limit_end = 28;
	$absolute_difference = 28;
} else {
	$limit_explode = explode(",", $_GET['limit']);
	$limit_start = filter_var($limit_explode[0],FILTER_SANITIZE_NUMBER_INT);
	$limit_end = filter_var($limit_explode[1],FILTER_SANITIZE_NUMBER_INT);
	if (!ctype_digit(strval($limit_start)) || !ctype_digit(strval($limit_end))) {
		$limit_start = 0;
		$limit_end = 25;
	}
}
$absolute_difference = abs($limit_start - $limit_end);
$limit_next = $limit_end + $absolute_difference;
$limit_previous_1 = $limit_start - $absolute_difference;
$limit_previous_2 = $limit_end - $absolute_difference;

$page_url = $globalURL.'/highlights';

print '<div class="info column">';
print '<div class="view-type">';
print '<a href="'.$globalURL.'/highlights" class="active" alt="Display View" title="Display View"><i class="fa fa-th"></i></a>';
print '<a href="'.$globalURL.'/highlights/table" alt="Table View" title="Table View"><i class="fa fa-table"></i></a>';
print '</div>';
print '<h1>'._("Special Highlights").'</h1>';
print '</div>';

print '<div class="column">';	
print '<p>'._("The view below shows all aircraft that have been selected to have some sort of special characteristic about them, such as unique liveries, destinations etc.").'</p>';

$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
if ($sort != '') {
	$spotter_array = $Spotter->getSpotterDataByHighlight($limit_start.",".$absolute_difference, $sort);
} else {
	$spotter_array = $Spotter->getSpotterDataByHighlight($limit_start.",".$absolute_difference, '');
}
if (!empty($spotter_array))
{
	print '<div class="dispay-view">';
	foreach($spotter_array as $spotter_item)
	{
		if (isset($spotter_item['image']) && $spotter_item['image'] != "")
		{
			print '<div>';
			//print '<a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'"><img src="'.$spotter_item['image'].'" alt="'.$spotter_item['highlight'].'" title="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_name'].' ('.$spotter_item['airline_name'].')" data-toggle="popover" data-content="'.$spotter_item['highlight'].'" /></a>';
			print '<a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'"><img src="'.$spotter_item['image_thumbnail'].'" alt="'.$spotter_item['highlight'].'" title="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_name'].' ('.$spotter_item['airline_name'].')" data-toggle="popover" data-content="'.$spotter_item['highlight'].'" /></a>';
			print '</div>';
		} elseif (isset($spotter_item['aircraft_name'])) {
			print '<div>';
			print '<a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'"><img src="'.$globalURL.'/images/placeholder_thumb.png" alt="'.$spotter_item['highlight'].'" title="'.$spotter_item['registration'].' - '.$spotter_item['aircraft_name'].' ('.$spotter_item['airline_name'].')" data-toggle="popover" data-content="'.$spotter_item['highlight'].'" /></a>';
			print '</div>';
		} else {
			print '<div>';
			print '<a href="'.$globalURL.'/flightid/'.$spotter_item['spotter_id'].'"><img src="'.$globalURL.'/images/placeholder_thumb.png" alt="'.$spotter_item['highlight'].'" title="'.$spotter_item['registration'].' ('.$spotter_item['airline_name'].')" data-toggle="popover" data-content="'.$spotter_item['highlight'].'" /></a>';
			print '</div>';
		}
	}
	print '</div>';
	print '<div class="pagination">';
	if ($limit_previous_1 >= 0)
	{
		print '<a href="'.$page_url.'/'.$limit_previous_1.','.$limit_previous_2.'/'.$sort.'">&laquo;'._("Previous Page").'</a>';
	}
	if ($spotter_array[0]['query_number_rows'] == $absolute_difference)
	{
		print '<a href="'.$page_url.'/'.$limit_end.','.$limit_next.'/'.$sort.'">'._("Next Page").'&raquo;</a>';
	}
	print '</div>';
	print '</div>';
}

require_once('footer.php');
?>