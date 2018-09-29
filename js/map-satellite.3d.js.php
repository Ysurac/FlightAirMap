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

$(".showdetails").on("click",".close",function(){
	$(".showdetails").empty();
	$("#pointident").attr('class','');
	return false;
})

function displayDataSat(data) {
	
	var dsn;
	for (var i =0; i < viewer.dataSources.length; i++) {
		if (viewer.dataSources.get(i).name == 'famsat') {
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
		if (typeof dsn != 'undefined') var existing = viewer.dataSources.get(dsn);
		else var existing;
		var orientation = new Cesium.VelocityOrientationProperty(entity.position)
		entity.orientation = orientation;
		if (typeof existing != 'undefined') {
			var last = viewer.dataSources.get(dsn).entities.getById(entity.id);
			if (typeof last == 'undefined') {
				entity.addProperty('type');
				entity.type = 'sat';
				entity.addProperty('lastupdatesat');
				entity.lastupdatesat = Date.now();
				viewer.dataSources.get(dsn).entities.add(entity);
			} else {
				last.lastupdatesat = Date.now();
				last.addProperty('type');
				last.type = 'sat';
			}
		} else {
			entity.addProperty('type');
			entity.type = 'sat';
			entity.addProperty('lastupdatesat');
			entity.lastupdatesat = Date.now();
		}
	}

	if (typeof dsn == 'undefined') {
		viewer.dataSources.add(data);
		dsn = viewer.dataSources.indexOf(data);
	} else {
		for (var i = 0; i < viewer.dataSources.get(dsn).entities.values.length; i++) {
			var entity = viewer.dataSources.get(dsn).entities.values[i];
			if (parseInt(entity.lastupdatesat) < Math.floor(Date.now()-<?php if (isset($globalMapRefresh)) print $globalMapRefresh*2000; else print '60000'; ?>)) {
				viewer.dataSources.get(dsn).entities.remove(entity);
			}
		}
	}
	
	var MapTrack = getCookie('MapTrackSatellite');
	if (MapTrack != '') {
		viewer.trackedEntity = viewer.dataSources.get(dsn).entities.getById(MapTrack);
		var currenttime = viewer.clock.currentTime;
		$(".showdetails").load("<?php print $globalURL; ?>/space-data.php?"+Math.random()+"&currenttime="+Date.parse(currenttime.toString())+"&sat="+encodeURI(MapTrack));
		$("#pointident").attr('class',MapTrack);
		$("#pointtype").attr('class','satellite');
	}
	
	$("#ibxsatellite").html("<h4>Satellites Displayed</h4><br/><b>"+viewer.dataSources.get(dsn).entities.values.length+"</b>");
};

function updateSat() {
	var livesatdata = czmldssat.process('<?php print $globalURL; ?>/live-sat-czml.php?' + Date.now());
	livesatdata.then(function (data) { 
		displayDataSat(data);
	});
}

function updateISS() {
	var issdata = Cesium.Resource.fetchJson('https://api.wheretheiss.at/v1/satellites/25544');
	issdata.then(function (data) {
		var altitude = Math.round(data.altitude*10000)/10;
		var entity = viewer.entities.getById('iss');
		if (typeof entity == 'undefined') {
			var property = new Cesium.SampledPositionProperty();
			var currenttime = viewer.clock.currentTime;
			var time = currenttime;
			var position = Cesium.Cartesian3.fromDegrees(data.longitude,data.latitude,altitude);
			property.addSample(time, position);
			entity = viewer.entities.add({
			    id: 'iss',
			    name: 'iss',
			    position: property,
			    model : {
				uri : '<?php print $globalURL; ?>/models/iss.glb',
				minimumPixelSize : 5000,
				maximumScale : 30000
			    }
			});
		} else {
			var property = entity.position;
			var currenttime = viewer.clock.currentTime;
			var time = Cesium.JulianDate.addSeconds(currenttime, 30, new Cesium.JulianDate());
			var position = Cesium.Cartesian3.fromDegrees(data.longitude,data.latitude,altitude);
			property.addSample(time, position);
			entity.position = property;
		}
	});
}

<?php
	if (isset($globalSatellite) && $globalSatellite) {
?>
var czmldssat = new Cesium.CzmlDataSource();
updateSat();
setInterval(function(){updateSat()},'20000');
//updateISS();
//setInterval(function(){updateISS()},'10000');
<?php
	}
?>
var handler_satellite = new Cesium.ScreenSpaceEventHandler(viewer.canvas);
handler_satellite.setInputAction(function(click) {
	var pickedObject = viewer.scene.pick(click.position);
	if (Cesium.defined(pickedObject)) {
		var currenttime = viewer.clock.currentTime;
		//console.log(pickedObject.id);
		var type = '';
		if (typeof pickedObject.id.type != 'undefined') {
			type = pickedObject.id.type;
		}
		if (type == 'sat') {
			if (singlemodel == false) {
				delCookie('MapTrackSatellite');
				createCookie('MapTrackSatellite',pickedObject.id.id,1);
			}
			$(".showdetails").load("<?php print $globalURL; ?>/space-data.php?"+Math.random()+"&currenttime="+Date.parse(currenttime.toString())+"&sat="+encodeURI(pickedObject.id.id));
		} else if (singlemodel == false) {
			delCookie('MapTrackSatellite');
		}
	} else if (singlemodel == false) {
		delCookie('MapTrackSatellite');
	}
}, Cesium.ScreenSpaceEventType.LEFT_CLICK);
