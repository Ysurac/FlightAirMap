<?php
require_once('require/class.Connection.php');
require_once('require/class.Marine.php');
//require_once('require/class.Stats.php');
require_once('require/class.Language.php');
$Marine = new Marine();
$type = 'marine';
if (!isset($_GET['type'])){
	header('Location: '.$globalURL.'/');
} else {
	//calculuation for the pagination
	if(!isset($_GET['limit']) || count(explode(",", $_GET['limit'])) < 2)
	{
		$limit_start = 0;
		$limit_end = 25;
		$absolute_difference = 25;
	}  else {
		$limit_explode = explode(",", $_GET['limit']);
		$limit_start = $limit_explode[0];
		$limit_end = $limit_explode[1];
		if (!ctype_digit(strval($limit_start)) || !ctype_digit(strval($limit_end))) {
			$limit_start = 0;
			$limit_end = 25;
		}
	}
	$absolute_difference = abs($limit_start - $limit_end);
	$limit_next = $limit_end + $absolute_difference;
	$limit_previous_1 = $limit_start - $absolute_difference;
	$limit_previous_2 = $limit_end - $absolute_difference;
	
	$marine_type = filter_input(INPUT_GET,'type',FILTER_SANITIZE_STRING);
	$page_url = $globalURL.'/marine/type/'.$marine_type;
	$sort = htmlspecialchars(filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING));
	$spotter_array = $Marine->getMarineDataByType($marine_type,$limit_start.",".$absolute_difference, $sort);
	
	if (!empty($spotter_array))
	{
		$title = sprintf(_("Detailed View for %s"),$spotter_array[0]['type']);
		require_once('header.php');

		print '<div class="info column">';
		print '<h1>'.$spotter_array[0]['type'].'</h1>';
		print '</div>';

		//include('aircraft-sub-menu.php');
		print '<div class="table column">';
		print '<p>'.sprintf(_("The table below shows the detailed information of all marine of type <strong>%s</strong>."),$spotter_array[0]['type']).'</p>';
		  
		include('table-output.php');
		  
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
	  
	} else {
		$title = _("Type");
		require_once('header.php');
		print '<h1>'._("Errors").'</h1>';
		print '<p>'._("Sorry, the aircraft type does not exist in this database. :(").'</p>'; 
	}
}
require_once('footer.php');
?>