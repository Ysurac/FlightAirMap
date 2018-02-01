<?php
require_once('require/class.Connection.php');
require_once('require/class.Spotter.php');
require_once('require/class.Stats.php');
require_once('require/class.Language.php');
if (!isset($_GET['airline'])) {
	header('Location: '.$globalURL.'/airline');
	die();
}
$airline = urldecode(filter_input(INPUT_GET,'airline',FILTER_SANITIZE_STRING));
$Spotter = new Spotter();
$alliance = false;
if (strpos($airline,'alliance_') !== FALSE) {
	$alliance = true;
} else {
	$spotter_array = $Spotter->getSpotterDataByAirline($airline,"0,1","");
}

if (!empty($spotter_array) || $alliance === true)
{
	if ($alliance) {
		$title = sprintf(_("Most Common Departure Airports from %s"),str_replace('_',' ',str_replace('alliance_','',$airline)));
	} else {
		$title = sprintf(_("Most Common Departure Airports from %s (%s)"),$spotter_array[0]['airline_name'],$spotter_array[0]['airline_icao']);
	}
	require_once('header.php');
	print '<div class="select-item">';
	print '<form action="'.$globalURL.'/airline" method="post">';
	print '<select name="airline" class="selectpicker" data-live-search="true">';
	print '<option></option>';
	$alliances = $Spotter->getAllAllianceNames();
	if (!empty($alliances)) {
		foreach ($alliances as $al) {
			if ($alliance && str_replace('_',' ',str_replace('alliance_','',$airline)) == $al['alliance']) {
				print '<option value="alliance_'.str_replace(' ','_',$al['alliance']).'" selected>'.$al['alliance'].'</option>';
			} else {
				print '<option value="alliance_'.str_replace(' ','_',$al['alliance']).'">'.$al['alliance'].'</option>';
			}
		}
		print '<option disabled>───────────────────</option>';
	}
	$Stats = new Stats();
	$airline_names = $Stats->getAllAirlineNames();
	if (empty($airline_names)) $airline_names = $Spotter->getAllAirlineNames();
	foreach($airline_names as $airline_name)
	{
		if($airline == $airline_name['airline_icao'])
		{
			print '<option value="'.$airline_name['airline_icao'].'" selected="selected">'.$airline_name['airline_name'].' ('.$airline_name['airline_icao'].')</option>';
		} else {
			print '<option value="'.$airline_name['airline_icao'].'">'.$airline_name['airline_name'].' ('.$airline_name['airline_icao'].')</option>';
		}
	}
	print '</select>';
	print '<button type="submit"><i class="fa fa-angle-double-right"></i></button>';
	print '</form>';
	print '</div>';
	print '<br />';

	if ($airline != "NA")
	{
		if ($alliance === false) {
			print '<div class="info column">';
			print '<h1>'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')</h1>';
			if ($globalIVAO && @getimagesize($globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.gif'))
			{
				print '<img src="'.$globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.gif" alt="'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')" title="'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')" class="logo" />';
			}
			elseif (@getimagesize($globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.png'))
			{
				print '<img src="'.$globalURL.'/images/airlines/'.$spotter_array[0]['airline_icao'].'.png" alt="'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')" title="'.$spotter_array[0]['airline_name'].' ('.$spotter_array[0]['airline_icao'].')" class="logo" />';
			}
			print '<div><span class="label">'._("Name").'</span>'.$spotter_array[0]['airline_name'].'</div>';
			print '<div><span class="label">'._("Country").'</span>'.$spotter_array[0]['airline_country'].'</div>';
			print '<div><span class="label">'._("ICAO").'</span>'.$spotter_array[0]['airline_icao'].'</div>';
			print '<div><span class="label">'._("IATA").'</span>'.$spotter_array[0]['airline_iata'].'</div>';
			print '<div><span class="label">'._("Callsign").'</span>'.$spotter_array[0]['airline_callsign'].'</div>'; 
			print '<div><span class="label">'._("Type").'</span>'.ucwords($spotter_array[0]['airline_type']).'</div>';        
			print '</div>';
		} else {
			print '<div class="info column">';
			print '<h1>'.str_replace('_',' ',str_replace('alliance_','',$airline)).'</h1>';
			if (@getimagesize($globalURL.'/images/airlines/'.str_replace('alliance_','',$airline).'.png') || @getimagesize('images/airlines/'.str_replace('alliance_','',$airline).'.png'))
			{
				print '<img src="'.$globalURL.'/images/airlines/'.str_replace('alliance_','',$airline).'.png" alt="'.str_replace('_',' ',str_replace('alliance_','',$airline)).'" title="'.str_replace('_',' ',str_replace('alliance_','',$airline)).'" class="logo" />';
			}
			print '<div><span class="label">'._("Name").'</span>'.str_replace('_',' ',str_replace('alliance_','',$airline)).'</div>';
			print '</div>';
		}
	} else {
		print '<div class="alert alert-warning">'._("This special airline profile shows all flights that do <u>not</u> have a airline associated with them.").'</div>';
	}

	include('airline-sub-menu.php');
	print '<div class="column">';
	print '<h2>'._("Most Common Departure Airports").'</h2>';
	if ($alliance) {
		print '<p>'.sprintf(_("The statistic below shows all departure airports of flights from <strong>%s</strong>."),str_replace('_',' ',str_replace('alliance_','',$airline))).'</p>';
	} else {
		print '<p>'.sprintf(_("The statistic below shows all departure airports of flights from <strong>%s</strong>."),$spotter_array[0]['airline_name']).'</p>';
	}
	/*
	if ($alliance) {
		$airport_airport_array = $Spotter->countAllDepartureAirportsByAirline('',array('alliance' => str_replace('_',' ',str_replace('alliance_','',$airline))));
	} else {
		$airport_airport_array = $Spotter->countAllDepartureAirportsByAirline($airline);
	}
	*/
	$airport_airport_array = $Stats->countAllDepartureAirports(true,$airline);
	print '<script type="text/javascript" src="'.$globalURL.'/js/d3.min.js"></script>';
	print '<script type="text/javascript" src="'.$globalURL.'/js/topojson.v2.min.js"></script>';
	print '<script type="text/javascript" src="'.$globalURL.'/js/datamaps.world.min.js"></script>';
	print '<div id="chartAirport" class="chart" width="100%"></div>';
	print '<script>';
	print 'var series = [';
	$airport_data = '';
	foreach($airport_airport_array as $airport_item)
	{
		$airport_data .= '[ "'.$airport_item['airport_departure_icao_count'].'", "'.$airport_item['airport_departure_name'].' ('.$airport_item['airport_departure_icao'].')",'.$airport_item['airport_departure_latitude'].','.$airport_item['airport_departure_longitude'].'],';
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
		print '<td><a href="'.$globalURL.'/search?departure_airport_route='.$airport_item['airport_departure_icao'].'&airline='.$airline.'">'._("Search flights").'</a></td>';
		print '</tr>';
		$i++;
	}
	print '<tbody>';
	print '</table>';
	print '</div>';
	print '</div>';
} else {
	$title = _("Airline Statistic");
	require_once('header.php');
	print '<h1>'._("Error").'</h1>';
	print '<p>'._("Sorry, the airline does not exist in this database. :(").'</p>'; 
}

require_once('footer.php');
?>