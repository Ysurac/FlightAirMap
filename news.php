<?php
require_once('require/class.Connection.php');
require_once('require/libs/SimplePie.compiled.php');
require_once('require/class.Language.php');
$SimplePie = new SimplePie();

$title = _("News");
require_once('header.php');

if (isset($_GET['tracker'])) $type = 'tracker';
elseif (isset($_GET['marine'])) $type = 'marine';
elseif (isset($_GET['aircraft'])) $type = 'aircraft';
elseif (isset($_GET['satellite'])) $type = 'satellite';
else $type = 'global';

//calculuation for the pagination
if(!isset($_GET['limit']))
{
  $limit_start = 0;
  $limit_end = 25;
  $absolute_difference = 25;
}  else {
	$limit_explode = explode(",", $_GET['limit']);
	$limit_start = $limit_explode[0];
	$limit_end = $limit_explode[1];
}

$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);

$absolute_difference = abs($limit_start - $limit_end);
$limit_next = $limit_end + $absolute_difference;
$limit_previous_1 = $limit_start - $absolute_difference;
$limit_previous_2 = $limit_end - $absolute_difference;

$page_url = $globalURL.'/news';

print '<div class="info column">';
print '<h1>'._("News").'</h1>';
print '</div>';

print '<div class="table column">';
//print '<p>'._("The table below shows the detailed information sorted by the newest recorded aircraft type. Each aircraft type is grouped and is shown only once, the first time it flew nearby.").'</p>';
print '<p>'._("This page show latest news.").'</p>';
if (count($globalNewsFeeds[$type]) == count($globalNewsFeeds[$type], COUNT_RECURSIVE)) 
{
	$feeds = $globalNewsFeeds[$type];
} else {
	$lg = $lang[1];
	if (isset($globalNewsFeeds[$type][$lg])) $feeds = $globalNewsFeeds[$type][$lg];
	else $feeds = array_shift($globalNewsFeeds[$type]);
}

$SimplePie->set_feed_url($feeds);
$SimplePie->set_cache_duration(3600);
$SimplePie->enable_cache(true);
$SimplePie->init();
$anews = $SimplePie->get_items();
 
if (!empty($anews))
{
	$j = 0;
	foreach($anews as $news) {
		if ($j > 10) break;
		$j++;
		print '<div class="news">';
		print '<h4><a href="'.$news->get_permalink().'">'.$news->get_title().'</a> <span>('.$news->get_date('j M Y, g:i a').')</span></h4>'."\n";
		print $news->get_content()."\n";
		print '</div>';
		print '<br/>'."\n";
	}
	//include('table-output.php');
	/*
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
	*/
}
require_once('footer.php');
?>