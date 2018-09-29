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
	//getLiveData(1);
	return false;
})

var lastupdatetracker;
function displayTrackerData(data) {
	var dsn;
	var flightcnt = 0;
	var datatable = '';
	for (var i =0; i < viewer.dataSources.length; i++) {
		if (viewer.dataSources.get(i).name == 'tracker') {
			dsn = i;
			break;
		}
	}
	if (typeof dns != 'undefined') {
		data.clock.currentTime = viewer.clock.currentTime;
	}
	var entities = data.entities.values;
	for (var i = 0; i < entities.length; i++) {
		var fromground = 0;
		var entity = entities[i];
		
		var id = entity.id;
		if (Cesium.defined(entity.properties.ident)) var callsign = entity.properties.ident;
		else var callsign = '';
		if (Cesium.defined(entity.properties.tracker_type)) var tracker_type = entity.properties.tracker_type;
		else var tracker_type = '';
		var position = entity.position.getValue(data.clock.currentTime);
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
			datatable += '<tr class="table-row" data-id="'+id+'" data-latitude="'+Cesium.Math.toDegrees(coord.latitude)+'" data-longitude="'+Cesium.Math.toDegrees(coord.longitude)+'"><td>'+callsign+'</td><td>'+tracker_type+'</td><td>'+latitude+'</td><td>'+longitude+'</td><td>'+lastupdatedate+'</td></tr>';
		}
		flightcnt = entity.properties.valueOf('flightcnt')._flightcnt._value;
		var onground = entity.properties.valueOf('onground')._onground._value;
		//console.log(onground);
		if (typeof dsn != 'undefined') var existing = viewer.dataSources.get(dsn);
		else var existing;
		
		if (onground === false) {
			var position = entity.position; 
			var times = position._property._times;
			var positionArray = [];
			var timeArray = [];
			for (var k = 0; k < times.length; k++) {
				var cartesian = position.getValue(times[k]);
				try {
					var cartographic = viewer.scene.globe.ellipsoid.cartesianToCartographic(cartesian);
				} catch(e) { console.log(e); }
				// Check if altitude < ground altitude
				if (fromground != 0 || cartographic.height < 8850) {
					try {
						var cartesian2 = new Cesium.Cartesian3.fromDegrees(Cesium.Math.toDegrees(cartographic.longitude),Cesium.Math.toDegrees(cartographic.latitude));
					} catch(e) { console.log('error: '+e); }
					try {
						var height = viewer.scene.globe.getHeight(Cesium.Ellipsoid.WGS84.cartesianToCartographic(cartesian2));
					} catch(e) { console.log('error: '+e); }
					if (!Cesium.defined(height)) height = cartographic.height;
					if (onground === true) {
						var finalHeight = height;
						//console.log('Old height : '+cartographic.height+' New height : '+height);
					} else {
						if (fromground != 0 || cartographic.height < height) {
							if (fromground == 0 || height < fromground) fromground = height;
							var finalHeight = cartographic.height+fromground;
						} else var finalHeight = cartographic.height;
					}
				} else var finalHeight = cartographic.height;
				positionArray.push(new Cesium.Cartesian3.fromDegrees(Cesium.Math.toDegrees(cartographic.longitude),Cesium.Math.toDegrees(cartographic.latitude), finalHeight));
				timeArray.push(times[k]);
			}
			var newPosition = new Cesium.SampledPositionProperty();
			try {
				newPosition.addSamples(timeArray, positionArray);
			} catch(e) { console.log(e); }
			entity.position = newPosition;
		}

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
			var lastupdateentity = entity.properties.valueOf('lastupdate')._lastupdate._value;
			<?php 
			    if (isset($globalMapUseBbox) && $globalMapUseBbox) {
			?>
			if (lastupdateentity != lastupdatetracker) {
				viewer.dataSources.get(dsn).entities.remove(entity);
				czmldstracker.entities.removeById(entityid);
			}
			<?php
			    } else {
			?>
			if (parseInt(lastupdateentity) < Math.floor(Date.now()-<?php if (isset($globalMapRefresh)) print $globalMapRefresh*2000; else print '60000'; ?>)) {
				viewer.dataSources.get(dsn).entities.remove(entity);
				czmldstracker.entities.removeById(entityid);
			}
			<?php
			    }
			?>
		}
	}
	var MapTracker = getCookie('MapTrackTracker');
	if (MapTracker != '') {
		viewer.trackedEntity = viewer.dataSources.get(dsn).entities.getById(MapTracker);
		$(".showdetails").load("<?php print $globalURL; ?>/tracker-data.php?"+Math.random()+"&famtrackid="+encodeURI(MapTracker)+"&currenttime="+Date.parse(currenttime.toString()));
		$("#pointident").attr('class',MapTracker);
	}
	var flightvisible = viewer.dataSources.get(dsn).entities.values.length;
	if (flightcnt != 0 && flightcnt != flightvisible && flightcnt > flightvisible) {
		$("#ibxtracker").html('<h4><?php echo _("Trackers detected"); ?></h4><br /><b>'+flightvisible+'/'+flightcnt+'</b>');
	} else {
		$("#ibxtracker").html('<h4><?php echo _("Trackers detected"); ?></h4><br /><b>'+flightvisible+'</b>');
	}
	
	if (datatable != '') {
		$('#datatable').css('height','20em');
		$('#datatable').html('<div class="datatabledata"><table id="datatabledatatable" class="table table-striped"><thead><tr><th>Callsign</th><th>Type</th><th>Latitude</th><th>Longitude</th><th>Last update</th></tr></thead><tbody>'+datatable+'</tbody></table></div>');
		$(".table-row").click(function () {
			var currenttime = viewer.clock.currentTime;
			$("#pointident").attr('class',$(this).data('id'));
			$("#pointtype").attr('class','tracker');
			$(".showdetails").load("<?php print $globalURL; ?>/tracker-data.php?"+Math.random()+"&famtrackid="+encodeURI($(this).data('id'))+"&currenttime="+Date.parse(currenttime.toString()));
			viewer.trackedEntity = viewer.dataSources.get(dsn).entities.getById($(this).data('id'));
		});
	}
};

function updateTrackerData() {
	lastupdatetracker = Date.now();
<?php
    if (isset($globalMapUseBbox) && $globalMapUseBbox) {
?>
	var livetrackerdata = czmldstracker.process('<?php print $globalURL; ?>/live-czml.php?tracker&coord='+bbox()+'&update=' + lastupdatetracker);
<?php
    } else {
?>
	var livetrackerdata = czmldstracker.process('<?php print $globalURL; ?>/live-czml.php?tracker&update=' + lastupdatetracker);
<?php
    }
?>  
	livetrackerdata.then(function (data) { 
		displayTrackerData(data);
	});
}

var czmldstracker = new Cesium.CzmlDataSource();
<?php
	if (!isset($globalMapUseBbox) || !$globalMapUseBbox) {
?>
Cesium.when(viewer.terrainProvider.ready,function() {updateTrackerData(); });
<?php
	}
?>
var handler_tracker = new Cesium.ScreenSpaceEventHandler(viewer.canvas);
handler_tracker.setInputAction(function(click) {
	var pickedObject = viewer.scene.pick(click.position);
	if (Cesium.defined(pickedObject)) {
		if (typeof pickedObject.id.properties != 'undefined') {
			var type = pickedObject.id.properties.valueOf('type')._type._value;
		}
		if (typeof type == 'undefined') {
			var type = pickedObject.id.type;
		}
		var currenttime = viewer.clock.currentTime;
		if (type == 'tracker') {
			flightaware_id = pickedObject.id.id;
			if (singlemodel == false) {
				delCookie('MapTrackTracker');
				createCookie('MapTrackTracker',flightaware_id,1);
			}
			$(".showdetails").load("<?php print $globalURL; ?>/tracker-data.php?"+Math.random()+"&famtrackid="+encodeURI(flightaware_id)+"&currenttime="+Date.parse(currenttime.toString()));
			var dsn;
			for (var i =0; i < viewer.dataSources.length; i++) {
				if (viewer.dataSources.get(i).name == 'tracker') {
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
			//lastid = flightaware_id;
		} else if (type == 'loc') {
			$(".showdetails").load("<?php print $globalURL; ?>/location-data.php?"+Math.random()+"&sourceid="+encodeURI(pickedObject.id.id));
		} else if (singlemodel == false) {
			delCookie('MapTrackTracker');
		}
	} else if (singlemodel == false) {
		delCookie('MapTrackTracker');
	}
}, Cesium.ScreenSpaceEventType.LEFT_CLICK);
camera.moveEnd.addEventListener(function() {
<?php
    if (isset($globalMapUseBbox) && $globalMapUseBbox) {
?>
	if (typeof archive == 'undefined' || archive == false) {
		updateTrackerData();
	}
<?php
    }
?>
});


if (archive == false) {
	var reloadpage = setInterval(
		function(){
			updateTrackerData();
		}
	,<?php if (isset($globalMapRefresh)) print $globalMapRefresh*1000; else print '30000'; ?>);
} else {
	var clockViewModel = new Cesium.ClockViewModel(viewer.clock);
	var animationViewModel = new Cesium.AnimationViewModel(clockViewModel);
	$(".archivebox").html('<h4><?php echo str_replace("'","\'",_("Archive")); ?></h4>' + '<br/><form id="noarchive" method="post"><input type="hidden" name="noarchive" /></form><a href="#" onClick="animationViewModel.playReverseViewModel.command();"><i class="fa fa-play fa-flip-horizontal" aria-hidden="true"></i></a> <a href="#" onClick="'+"document.getElementById('noarchive').submit();"+'"><i class="fa fa-eject" aria-hidden="true"></i></a> <a href="#" onClick="animationViewModel.pauseViewModel.command();"><i class="fa fa-pause" aria-hidden="true"></i></a> <a href="#" onClick="animationViewModel.playForwardViewModel.command();"><i class="fa fa-play" aria-hidden="true"></i></a>');
}
function TrackericonColor(color) {
	document.cookie =  'TrackerIconColor='+color.substring(1)+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	if (getCookie('TrackerIconColorForce') == 'true') window.location.reload();
}
function TrackericonColorForce(val) {
	document.cookie =  'TrackerIconColorForce='+val.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	if (getCookie('TrackerIconColor') != '') document.cookie =  'TrackerIconColor=ff0000; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
}