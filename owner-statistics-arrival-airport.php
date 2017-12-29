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
	$archive = true;
	$spotter_array = $SpotterArchive->getSpotterDataByOwner($owner,"0,1", $sort,$filter);
}
if (!empty($spotter_array))
{
	$title = sprintf(_("Most Common Arrival Airports of %s"),$spotter_array[0]['aircraft_owner']);
	require_once('header.php');
	print '<div class="info column">';
	print '<h1>'.$spotter_array[0]['aircraft_owner'].'</h1>';
//	print '<div><span class="label">'._("Ident").'</span>'.$spotter_array[0]['ident'].'</div>';
//	print '<div><span class="label">'._("Airline").'</span><a href="'.$globalURL.'/airline/'.$spotter_array[0]['airline_icao'].'">'.$spotter_array[0]['airline_name'].'</a></div>'; 
	print '</div>';

	include('owner-sub-menu.php');
	print '<div class="column">';
	print '<h2>'._("Most Common Arrival Airports").'</h2>';
	print '<p>'.sprintf(_("The statistic below shows all arrival airports of flights owned by <strong>%s</strong>."),$spotter_array[0]['aircraft_owner']).'</p>';
	if ($archive === false) {
		$airport_airport_array = $Spotter->countAllArrivalAirportsByOwner($owner,$filter);
	} else {
		$airport_airport_array = $SpotterArchive->countAllArrivalAirportsByOwner($owner,$filter);
	}
	print '<script type="text/javascript" src="'.$globalURL.'/js/d3.min.js"></script>';
	print '<script type="text/javascript" src="'.$globalURL.'/js/topojson.v2.min.js"></script>';
	print '<script type="text/javascript" src="'.$globalURL.'/js/datamaps.world.min.js"></script>';
	print '<div id="chartAirport" class="chart" width="100%"></div>';
	print '<script>';
	print 'var series = [';
	$airport_data = '';
	foreach($airport_airport_array as $airport_item)
	{
		$airport_data .= '[ "'.$airport_item['airport_arrival_icao_count'].'", "'.$airport_item['airport_arrival_name'].' ('.$airport_item['airport_arrival_icao'].')",'.$airport_item['airport_arrival_latitude'].','.$airport_item['airport_arrival_longitude'].'],';
	}
	$airport_data = substr($airport_data, 0, -1);
	print $airport_data;
	print '];'."\n";
	print 'var onlyValues = series.map(function(obj){ return obj[0]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);'."\n";
	print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#EFEFFF","#001830"]);'."\n";
	print 'var radiusScale = d3.scale.log().domain([minValue,maxValue]).range([0,10]);'."\n";
	print 'var dataset = [];'."\n";
	print 'var colorset = [];'."\n";
	print 'colorset["defaultFill"] = "#F5F5F5";';
	print 'series.forEach(function(item){'."\n";
	print 'var cnt = item[0], nm = item[1], lat = item[2], long = item[3];'."\n";
	print 'colorset[nm] = paletteScale(cnt);';
	print 'dataset.push({ count: cnt, name: nm, radius: Math.floor(radiusScale(cnt)), latitude: lat, longitude: long, fillKey: nm });'."\n";
	print '});'."\n";
	print 'var bbl = new Datamap({
	    element: document.getElementById("chartAirport"),
	    projection: "mercator", // big world map
	    fills: colorset,
	    responsive: true,
	    geographyConfig: {
		borderColor: "#DEDEDE",
		highlightBorderWidth: 2,
		highlightFillColor: function(geo) {
		    return geo["fillColor"] || "#F5F5F5";
		},
		highlightBorderColor: "#B7B7B7"},
		done: function(datamap) {
		    datamap.svg.call(d3.behavior.zoom().on("zoom", redraw));
		    function redraw() {
			datamap.svg.selectAll("g").attr("transform", "translate(" + d3.event.translate + ")scale(" + d3.event.scale + ")");
		    }
		}
	    });
	    bbl.bubbles(dataset,{
		popupTemplate: function(geo, data) {
		    if (!data) { return ; }
		    return ['."'".'<div class="hoverinfo">'."','<strong>', data.name, '</strong>','<br>Count: <strong>', data.count, '</strong>','</div>'].join('');
		}
	    });";
	print '</script>';
	print '<div class="table-responsive">';
	print '<table class="common-airport table-striped">';
	print '<thead>';
	print '<th></th>';
	print '<th>'._("Airport").'</th>';
	print '<th>'._("Country").'</th>';
	print '<th>'._("# of times").'</th>';
	print '<th></th>';
	print '</thead>';
	print '<tbody>';
	$i = 1;
	foreach($airport_airport_array as $airport_item)
	{
		print '<tr>';
		print '<td><strong>'.$i.'</strong></td>';
		print '<td>';
		print '<a href="'.$globalURL.'/airport/'.$airport_item['airport_arrival_icao'].'">'.$airport_item['airport_arrival_name'].', '.$airport_item['airport_arrival_country'].' ('.$airport_item['airport_arrival_icao'].')</a>';
		print '</td>';
		print '<td>';
		print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airport_item['airport_arrival_country'])).'">'.$airport_item['airport_arrival_country'].'</a>';
		print '</td>';
		print '<td>';
		print $airport_item['airport_arrival_icao_count'];
		print '</td>';
		print '<td><a href="'.$globalURL.'/search?arrival_airport_route='.$airport_item['airport_arrival_icao'].'&owner='.$owner.'">'._("Search flights").'</a></td>';
		print '</tr>';
		$i++;
	}
	print '<tbody>';
	print '</table>';
	print '</div>';
	print '</div>';
} else {
	$title = _("Owner");
	require_once('header.php');
	print '<h1>'._("Error").'</h1>';
	print '<p>'._("Sorry, this owner is not in the database. :(").'</p>';  
}

require_once('footer.php');
?>