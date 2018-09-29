<?php
require_once('../require/settings.php');
require_once('../require/class.Language.php');
header('Content-Type: text/javascript');
?>
/**
 * This javascript is part of FlightAirmap.
 *
 * Copyright (c) Ycarus (Yannick Chabanois) <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
"use strict";
<?php
if (isset($globalVM) && $globalVM) {
?>
var globalVM = true;
<?php
} else {
?>
var globalVM = false;
<?php
}
?>

$(".showdetails").on("click",".close",function(){
	$(".showdetails").empty();
	$("#pointident").attr('class','');
	//getLiveData(1);
	return false;
})


function displayMarineData(data) {
	var dsn;
	var marinecnt = 0;
	var datatablemarine = [];
	for (var i =0; i < viewer.dataSources.length; i++) {
		if (viewer.dataSources.get(i).name == 'marine') {
			dsn = i;
			break;
		}
	}
	if (typeof dsn != 'undefined') {
		data.clock.currentTime = viewer.clock.currentTime;
	}
	var entities = data.entities.values;
	for (var i = 0; i < entities.length; i++) {
		var entity = entities[i];
		
		var id = entity.id;
		if (Cesium.defined(entity.properties.ident)) var callsign = entity.properties.ident;
		else var callsign = '';
		if (Cesium.defined(entity.properties.marine_type)) var marine_type = entity.properties.marine_type;
		else var marine_type = '';
		var position = entity.position.getValue(data.clock.currentTime)
		if (Cesium.defined(position)) {
			var coord = viewer.scene.globe.ellipsoid.cartesianToCartographic(position);
			var lastupdatet = entity.position._property._times[entity.position._property._times.length-1].toString();
			var lastupdatedate = new moment.tz(lastupdatet,moment.tz.guess()).format("HH:mm:ss");
			if (unitcoordinatevalue == 'dms') {
				var latitude = convertDMS(Cesium.Math.toDegrees(coord.latitude),'latitude');
				var longitude = convertDMS(Cesium.Math.toDegrees(coord.longitude),'longitude');
			} else if (unitcoordinatevalue == 'dm') {
				var latitude = convertDM(Cesium.Math.toDegrees(coord.latitude),'latitude');
				var longitude = convertDM(Cesium.Math.toDegrees(coord.longitude),'longitude');
			} else {
				var latitude = Cesium.Math.toDegrees(coord.latitude);
				var longitude = Cesium.Math.toDegrees(coord.longitude);
			}
			if (globalVM) {
				var rank = parseInt(entity.properties.rank);
				var captain = entity.properties.captain;
				var race = entity.properties.race;
				//datatablemarine += '<tr class="table-row" data-id="'+id+'" data-latitude="'+Cesium.Math.toDegrees(coord.latitude)+'" data-longitude="'+Cesium.Math.toDegrees(coord.longitude)+'"><td>'+rank+'</td><td>'+race+'</td><td>'+captain+'</td><td>'+callsign+'</td><td>'+marine_type+'</td><td>'+Cesium.Math.toDegrees(coord.latitude)+'</td><td>'+Cesium.Math.toDegrees(coord.longitude)+'</td><td>'+lastupdatedate+'</td></tr>';
				datatablemarine.push({id:id,rank: rank,race: race,captain: captain,callsign: callsign,marine_type: marine_type,latitude: latitude,longitude: longitude,lastupdate:lastupdatedate});
			} else {
				datatablemarine.push({id:id,callsign: callsign,marine_type:marine_type,latitude: latitude,longitude: longitude,lastupdate:lastupdatedate});
				//datatablemarine += '<tr class="table-row" data-id="'+id+'" data-latitude="'+Cesium.Math.toDegrees(coord.latitude)+'" data-longitude="'+Cesium.Math.toDegrees(coord.longitude)+'"><td>'+callsign+'</td><td>'+marine_type+'</td><td>'+Cesium.Math.toDegrees(coord.latitude)+'</td><td>'+Cesium.Math.toDegrees(coord.longitude)+'</td><td>'+lastupdatedate+'</td></tr>';
			}
		}
		
		if (typeof dsn != 'undefined') var existing = viewer.dataSources.get(dsn);
		else var existing;
		marinecnt = entity.properties.flightcnt;
		var orientation = new Cesium.VelocityOrientationProperty(entity.position)
		entity.orientation = orientation;
	}
	if (typeof dsn == 'undefined') {
		viewer.dataSources.add(data);
		dsn = viewer.dataSources.indexOf(data);
	} else {
		for (var i = 0; i < viewer.dataSources.get(dsn).entities.values.length; i++) {
			var entity = viewer.dataSources.get(dsn).entities.values[i];
			var entityid = entity.id;
			var lastupdateentity = entity.properties.lastupdate;
			<?php 
			    if (isset($globalMapUseBbox) && $globalMapUseBbox) {
			?>
			if (lastupdateentity != lastupdatemarine) {
				viewer.dataSources.get(dsn).entities.remove(entity);
				czmldsmarine.entities.removeById(entityid);
			}
			<?php
			    } else {
			?>
			if (parseInt(lastupdateentity) < Math.floor(Date.now()-<?php if (isset($globalMapRefresh)) print $globalMapRefresh*2000; else print '60000'; ?>)) {
				viewer.dataSources.get(dsn).entities.remove(entity);
			}
			<?php
			    }
			?>
		}
	}
	var MapTrackMarine = getCookie('MapTrackMarine');
	if (MapTrackMarine != '') {
		viewer.trackedEntity = viewer.dataSources.get(dsn).entities.getById(MapTrackMarine);
		$(".showdetails").load("<?php print $globalURL; ?>/marine-data.php?"+Math.random()+"&fammarine_id="+flightaware_id+"&currenttime="+Date.parse(currenttime.toString()));
		$("#pointident").attr('class',flightaware_id);
		$("#pointtype").attr('class','marine');
	}
	var marinevisible = viewer.dataSources.get(dsn).entities.values.length;
	if (marinecnt != 0 && marinecnt != marinevisible && marinecnt > marinevisible) {
		$("#ibxmarine").html('<h4><?php echo _("Marines detected"); ?></h4><br /><b>'+marinevisible+'/'+marinecnt+'</b>');
	} else {
		$("#ibxmarine").html('<h4><?php echo _("Marines detected"); ?></h4><br /><b>'+marinevisible+'</b>');
	}
	
	if (datatable != '') {
		$('#datatablemarine').css('height','20em');
		var datatablemarinetxt = '';
		if (globalVM) {
			var index,len,dt;
			datatablemarine.sort(dynamicSortMultiple("race","rank"));
			for (index = 0, len = datatablemarine.length; index < len; ++index) {
				dt = datatablemarine[index];
				datatablemarinetxt += '<tr class="table-row" data-id="'+dt.id+'" data-latitude="'+dt.latitude+'" data-longitude="'+dt.longitude+'"><td>'+dt.rank+'</td><td>'+dt.race+'</td><td>'+dt.captain+'</td><td>'+dt.callsign+'</td><td>'+dt.marine_type+'</td><td>'+dt.latitude+'</td><td>'+dt.longitude+'</td><td>'+dt.lastupdate+'</td></tr>';
			}
			$('#datatablemarine').html('<div class="datatabledata"><table id="datatabledatatable" class="table table-striped"><thead><tr><th><?php echo _("Rank"); ?></th><th><?php echo _("Race"); ?></th><th><?php echo _("Captain"); ?></th><th><?php echo _("Callsign"); ?></th><th><?php echo _("Type"); ?><th><?php echo _("Latitude"); ?></th><th><?php echo _("Longitude"); ?></th><th><?php echo _("Last update"); ?></th></tr></thead><tbody>'+datatablemarinetxt+'</tbody></table></div>');
		} else {
			var index,len,dt;
			for (index = 0, len = datatablemarine.length; index < len; ++index) {
				dt = datatablemarine[index];
				datatablemarinetxt += '<tr class="table-row" data-id="'+dt.id+'" data-latitude="'+dt.latitude+'" data-longitude="'+dt.longitude+'"><td>'+dt.callsign+'</td><td>'+dt.marine_type+'</td><td>'+dt.latitude+'</td><td>'+dt.longitude+'</td><td>'+dt.lastupdate+'</td></tr>';
			}
			$('#datatablemarine').html('<div class="datatabledata"><table id="datatabledatatable" class="table table-striped"><thead><tr><th><?php echo _("Callsign"); ?></th><th><?php echo _("Type"); ?><th><?php echo _("Latitude"); ?></th><th><?php echo _("Longitude"); ?></th><th><?php echo _("Last update"); ?></th></tr></thead><tbody>'+datatablemarinetxt+'</tbody></table></div>');
		}
		$(".table-row").click(function () {
			$("#pointident").attr('class',$(this).data('id'));
			$("#pointtype").attr('class','marine');
			var currenttime = viewer.clock.currentTime;
			$(".showdetails").load("<?php print $globalURL; ?>/tracker-data.php?"+Math.random()+"&famtrackid="+encodeURI($(this).data('id'))+"&currenttime="+Date.parse(currenttime.toString()));
			viewer.trackedEntity = viewer.dataSources.get(dsn).entities.getById($(this).data('id'));
		});
	}
};

function update_raceLayer(raceid) {
	var racenb;
	for (var i =0; i < viewer.dataSources.length; i++) {
		if (viewer.dataSources.get(i).name == 'race') {
			racenb = i;
			break;
		}
	}

	var race_geojson = Cesium.Resource.fetchJson("<?php print $globalURL; ?>/races-geojson.php?race_id="+raceid);
	race_geojson.then(function(geojsondata) {
	race = new Cesium.CustomDataSource('atc');
	for (var i =0;i < geojsondata.features.length; i++) {
	    data = geojsondata.features[i].properties;
		var entity = race.entities.add({
		    ref: data.ref,
		    ident: data.race,
		    position: Cesium.Cartesian3.fromDegrees(geojsondata.features[i].geometry.coordinates[0],geojsondata.features[i].geometry.coordinates[1]),
		    billboard: {
			image: data.icon,
			verticalOrigin: Cesium.VerticalOrigin.BOTTOM
		    },
		    type: 'race'
		});
	}
	if (typeof racenb != 'undefined') var remove = viewer.dataSources.remove(viewer.dataSources.get(racenb));
	viewer.dataSources.add(race);
    });
}


var lastupdatemarine;
function updateMarineData() {
	lastupdatemarine = Date.now();
<?php
    if (isset($globalMapUseBbox) && $globalMapUseBbox) {
?>
	var livemarinedata = czmldsmarine.process('<?php print $globalURL; ?>/live-czml.php?marine&coord='+bbox()+'&update=' + lastupdatemarine);
<?php
    } else {
?>
	var livemarinedata = czmldsmarine.process('<?php print $globalURL; ?>/live-czml.php?marine&update=' + lastupdatemarine);
<?php
    }
?>
	livemarinedata.then(function (data) { 
		displayMarineData(data);
	});
}


var czmldsmarine = new Cesium.CzmlDataSource();
updateMarineData();
var handler_marine = new Cesium.ScreenSpaceEventHandler(viewer.canvas);
handler_marine.setInputAction(function(click) {
	var pickedObject = viewer.scene.pick(click.position);
	if (Cesium.defined(pickedObject)) {
		if (typeof pickedObject.id.properties != 'undefined') {
			var type = pickedObject.id.properties.valueOf('type')._type._value;
		}
		if (typeof type == 'undefined') {
			var type = pickedObject.id.type;
		}
		var currenttime = viewer.clock.currentTime;
		if (type == 'marine') {
			flightaware_id = pickedObject.id.id;
			if (singlemodel == false) {
				delCookie('MapTrackMarine');
				createCookie('MapTrackMarine',flightaware_id,1);
			}
			if (Cesium.defined(pickedObject.id.properties.raceid) && pickedObject.id.properties.raceid != '') {
				update_raceLayer(pickedObject.id.properties.raceid);
			}
			$(".showdetails").load("<?php print $globalURL; ?>/marine-data.php?"+Math.random()+"&fammarine_id="+flightaware_id+"&currenttime="+Date.parse(currenttime.toString()));
			var dsn;
			for (var i =0; i < viewer.dataSources.length; i++) {
				if (viewer.dataSources.get(i).name == 'marine') {
					dsn = i;
					break;
				}
			}
			var lastid = document.getElementById('pointident').className;
			if (typeof lastid != 'undefined' && lastid != '') {
				var plast = viewer.dataSources.get(dsn).entities.getById(lastid);
				plast.path.show = false;
			}
			var pnew = viewer.dataSources.get(dsn).entities.getById(flightaware_id);
			pnew.path.show = true;
			$("#pointident").attr('class',flightaware_id);
			$("#pointtype").attr('class','marine');
			//lastid = flightaware_id;
		} else if (singlemodel == false) {
			delCookie('MapTrackMarine');
		}
	} else if (singlemodel == false) {
		delCookie('MapTrackMarine');
	}
}, Cesium.ScreenSpaceEventType.LEFT_CLICK);
camera.moveEnd.addEventListener(function() {
<?php
    if (isset($globalMapUseBbox) && $globalMapUseBbox) {
?>
	updateMarineData();
<?php
    }
?>
});

if (archive == false) {
	var reloadpage = setInterval(
		function(){
			updateMarineData();
		}
	,<?php if (isset($globalMapRefresh)) print $globalMapRefresh*1000; else print '30000'; ?>);
} else {
	var clockViewModel = new Cesium.ClockViewModel(viewer.clock);
	var animationViewModel = new Cesium.AnimationViewModel(clockViewModel);
	$(".archivebox").html('<h4><?php echo str_replace("'","\'",_("Archive")); ?></h4>' + '<br/><form id="noarchive" method="post"><input type="hidden" name="noarchive" /></form><a href="#" onClick="animationViewModel.playReverseViewModel.command();"><i class="fa fa-play fa-flip-horizontal" aria-hidden="true"></i></a> <a href="#" onClick="'+"document.getElementById('noarchive').submit();"+'"><i class="fa fa-eject" aria-hidden="true"></i></a> <a href="#" onClick="animationViewModel.pauseViewModel.command();"><i class="fa fa-pause" aria-hidden="true"></i></a> <a href="#" onClick="animationViewModel.playForwardViewModel.command();"><i class="fa fa-play" aria-hidden="true"></i></a>');
}
function MarineiconColor(color) {
	document.cookie =  'MarineIconColor='+color.substring(1)+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	if (getCookie('MarineIconColorForce') == 'true') window.location.reload();
}
function MarineiconColorForce(val) {
	document.cookie =  'MarineIconColorForce='+val.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	if (getCookie('MarineIconColor') != '') document.cookie =  'MarineIconColor=ff0000; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
}
if (getCookie('openseamap') == 'true') loadOpenSeaMap(getCookie('openseamap'));
function clickOpenSeaMap(cb) {
    loadOpenSeaMap(cb.checked);
}
function loadOpenSeaMap(val) {
    var openseamapc = getCookie('openseamap');
    if (openseamapc == 'true' && val != 'true') {
	layers.remove(openseamap,true);
	delCookie('openseamap');
    } else {
	console.log('Add openseamap');
	createCookie('openseamap',val,999);
	openseamap = layers.addImageryProvider(Cesium.createOpenStreetMapImageryProvider({
	    url : 'http://tiles.openseamap.org/seamark/',
	    credit: 'Map data: &copy; <a href="http://www.openseamap.org">OpenSeaMap</a> contributors'
	}));
	openseamap.brightness = 2.0;
	openseamap.contrast = 2.0;
	console.log('added...');
    }
}