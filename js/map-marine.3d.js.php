<?php
	require_once('../require/settings.php');
	require_once('../require/class.Language.php'); 
?>

$(".showdetails").on("click",".close",function(){
	$(".showdetails").empty();
	$("#aircraft_ident").attr('class','');
	//getLiveData(1);
	return false;
})


function displayMarineData(data) {
	var dsn;
	var flightcnt = 0;
	for (var i =0; i < viewer.dataSources.length; i++) {
		if (viewer.dataSources.get(i).name == 'marine') {
			dsn = i;
			break;
		}
	}
	var entities = data.entities.values;
	for (var i = 0; i < entities.length; i++) {
		var entity = entities[i];
		if (typeof dsn != 'undefined') var existing = viewer.dataSources.get(dsn);
		else var existing;
		flightcnt = entity.properties.valueOf('flightcnt')._flightcnt._value;
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
	var MapTrack = getCookie('MapTrack');
	if (MapTrack != '') {
		viewer.trackedEntity = viewer.dataSources.get(dsn).entities.getById(MapTrack);
		$(".showdetails").load("<?php print $globalURL; ?>/marine-data.php?"+Math.random()+"&fammarine_id="+flightaware_id+"&currenttime="+Date.parse(currenttime.toString()));
		$("#aircraft_ident").attr('class',flightaware_id);
	}
	var flightvisible = viewer.dataSources.get(dsn).entities.values.length;
	if (flightcnt != 0 && flightcnt != flightvisible && flightcnt > flightvisible) {
		$("#ibxmarine").html('<h4><?php echo _("Marines detected"); ?></h4><br /><b>'+flightvisible+'/'+flightcnt+'</b>');
	} else {
		$("#ibxmarine").html('<h4><?php echo _("Marines detected"); ?></h4><br /><b>'+flightvisible+'</b>');
	}
};

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
		console.log(pickedObject.id);
		delCookie('MapTrack');
		if (type == 'marine') {
			flightaware_id = pickedObject.id.id;
			$(".showdetails").load("<?php print $globalURL; ?>/marine-data.php?"+Math.random()+"&fammarine_id="+flightaware_id+"&currenttime="+Date.parse(currenttime.toString()));
			var dsn;
			for (var i =0; i < viewer.dataSources.length; i++) {
				if (viewer.dataSources.get(i).name == 'marine') {
					dsn = i;
					break;
				}
			}
			var lastid = document.getElementById('aircraft_ident').className;
			if (typeof lastid != 'undefined' && lastid != '') {
				var plast = viewer.dataSources.get(dsn).entities.getById(lastid);
				plast.path.show = false;
			}
			var pnew = viewer.dataSources.get(dsn).entities.getById(flightaware_id);
			pnew.path.show = true;
			$("#aircraft_ident").attr('class',flightaware_id);
			//lastid = flightaware_id;
		}
	}
}, Cesium.ScreenSpaceEventType.LEFT_CLICK);

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