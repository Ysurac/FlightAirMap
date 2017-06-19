<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Language.php');

$Spotter = new Spotter();
$sort = filter_input(INPUT_GET,'sort',FILTER_SANITIZE_STRING);
$date = filter_input(INPUT_GET,'date',FILTER_SANITIZE_STRING);
$spotter_array = $Spotter->getSpotterDataByDate($date,"0,1", $sort);

if (!empty($spotter_array))
{
	$title = sprintf(_("Most Common Departure Airports on %s"),date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601'])));

	require_once('header.php');
	print '<div class="select-item">';
	print '<form action="'.$globalURL.'/date" method="post" class="form-inline">';
	print '<div class="form-group">';
	print '<label for="datepickeri">'._("Select a Date").'</label>';
	print '<div class="input-group date" id="datepicker">';
	print '<input type="text" class="form-control" id="datepickeri" name="date" value="" />';
	print '<span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>';
	print '</div>';
	print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
	print '</div>';
	print '</form>';
	print '</div>';
	print '<script type="text/javascript">$(function () { $("#datepicker").datetimepicker({ format: "YYYY-MM-DD", defaultDate: "'.$date.'" }); }); </script>';
	print '<br />';
	print '<div class="info column">';
	print '<h1>'.sprintf(_("Flights from %s"),date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601']))).'</h1>';
	print '</div>';

	include('date-sub-menu.php');
	print '<div class="column">';
	print '<h2>'._("Most Common Departure Airports").'</h2>';
	print '<p>'.sprintf(_("The statistic below shows all departure airports of flights on <strong>%s</strong>."),date("l F j, Y", strtotime($spotter_array[0]['date_iso_8601']))).'</p>';
	$airport_airport_array = $Spotter->countAllDepartureAirportsByDate($date);
	print '<script type="text/javascript" src="'.$globalURL.'/js/d3.min.js"></script>';
	print '<script type="text/javascript" src="'.$globalURL.'/js/topojson.v2.min.js"></script>';
	print '<script type="text/javascript" src="'.$globalURL.'/js/datamaps.world.min.js"></script>';
	print '<div id="chartAirport" class="chart" width="100%"></div>';
	print '<script>';
	print 'var series = [';
	$airport_data = '';
	foreach($airport_airport_array as $airport_item)
	{
		$airport_data .= '[ "'.$airport_item['airport_departure_icao_count'].'", "'.$airport_item['airport_departure_icao'].'",'.$airport_item['airport_departure_latitude'].','.$airport_item['airport_departure_longitude'].'],';
	}
	$airport_data = substr($airport_data, 0, -1);
	print $airport_data;
	print '];'."\n";
	print 'var onlyValues = series.map(function(obj){ return obj[0]; });var minValue = Math.min.apply(null, onlyValues), maxValue = Math.max.apply(null, onlyValues);'."\n";
	print 'var paletteScale = d3.scale.log().domain([minValue,maxValue]).range(["#EFEFFF","#001830"]);'."\n";
	print 'var radiusScale = d3.scale.log().domain([minValue,maxValue]).range([2,20]);'."\n";
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
		print '<a href="'.$globalURL.'/airport/'.$airport_item['airport_departure_icao'].'">'.$airport_item['airport_departure_city'].', '.$airport_item['airport_departure_country'].' ('.$airport_item['airport_departure_icao'].')</a>';
		print '</td>';
		print '<td>';
		print '<a href="'.$globalURL.'/country/'.strtolower(str_replace(" ", "-", $airport_item['airport_departure_country'])).'">'.$airport_item['airport_departure_country'].'</a>';
		print '</td>';
		print '<td>';
		print $airport_item['airport_departure_icao_count'];
		print '</td>';
		print '<td><a href="'.$globalURL.'/search?departure_airport_route='.$airport_item['airport_departure_icao'].'&start_date='.$date.'+00:00&end_date='.$date.'+23:59">'._("Search flights").'</a></td>';
		print '</tr>';
		$i++;
	}
	print '<tbody>';
	print '</table>';
	print '</div>';
	print '</div>';
} else {
	$title = _("Unknown Date");
	require_once('header.php');
	print '<h1>'._("Error").'</h1>';
	print '<p>'._("Sorry, this date does not exist in this database. :(").'</p>'; 
}

require_once('footer.php');
?>