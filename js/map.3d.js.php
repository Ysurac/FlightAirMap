<?php
	require_once('../require/settings.php');
	require_once('../require/class.Language.php'); 
?>
function getCookie(cname) {
	var name = cname + "=";
	var ca = document.cookie.split(';');
	for(var i=0; i<ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1);
		if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
	}
	return "";
}

function delCookie(cname) {
	document.cookie = cname + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}

document.cookie =  'MapFormat=3d; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
<?php
	if (isset($_COOKIE['MapType'])) $MapType = $_COOKIE['MapType'];
	else $MapType = $globalMapProvider;

//	unset($_COOKIE['MapType']);
	if ($MapType != 'Mapbox' && $MapType != 'OpenStreetMap' && $MapType != 'Bing-Aerial' && $MapType != 'Bing-Hybrid' && $MapType != 'Bing-Road') {
		if (isset($globalBingMapKey) && $globalBingMapKey != '') $MapType = 'Bing-Aerial';
		else $MapType = 'OpenStreetMap';
	}
	if (($MapType == 'Bing-Aerial' || $MapType == 'Bing-Hybrid' || $MapType == 'Bing-Road') && (!isset($globalBingMapKey) || $globalBingMapKey == '')) {
		$MapType = 'OpenStreetMap';
	}
	if ($MapType == 'Mapbox') {
		if ($_COOKIE['MapTypeId'] == 'default') $MapBoxId = $globalMapboxId;
		else $MapBoxId = $_COOKIE['MapTypeId'];
?>
	var imProv = Cesium.MapboxImageryProvider({
		credit: 'Map data © OpenStreetMap contributors, ' +
	      'CC-BY-SA, ' +
	      'Imagery © Mapbox',
		mapId: '<?php print $MapBoxId; ?>',
		accessToken: '<?php print $globalMapboxToken; ?>'
	}).addTo(map);
<?php
	} elseif ($MapType == 'OpenStreetMap') {
?>
	var imProv = Cesium.createOpenStreetMapImageryProvider({
		url : 'https://a.tile.openstreetmap.org/',
		credit: 'Map data © OpenStreetMap contributors, ' +
	      'Open Database Licence'
	});
<?php
/*
	} elseif ($MapType == 'MapQuest-OSM') {
?>
	var mapquestLayer = new MQ.mapLayer();
	map.addLayer(mapquestLayer);
<?php
	} elseif ($MapType == 'MapQuest-Aerial') {
?>
	var mapquestLayer = new MQ.satelliteLayer();
	map.addLayer(mapquestLayer);
<?php
	} elseif ($MapType == 'MapQuest-Hybrid') {
?>
	var mapquestLayer = new MQ.hybridLayer();
	map.addLayer(mapquestLayer);
<?php
	} elseif ($MapType == 'Google-Roadmap') {
?>
	var googleLayer = new L.Google('ROADMAP');
	map.addLayer(googleLayer);
<?php
	} elseif ($MapType == 'Google-Satellite') {
?>
	var googleLayer = new L.Google('SATELLITE');
	map.addLayer(googleLayer);
<?php
	} elseif ($MapType == 'Google-Hybrid') {
?>
	var googleLayer = new L.Google('HYBRID');
	map.addLayer(googleLayer);
<?php
	} elseif ($MapType == 'Google-Terrain') {
?>
	var googleLayer = new L.Google('TERRAIN');
	map.addLayer(googleLayer);
<?php
	} elseif ($MapType == 'Yandex') {
?>
	var yandexLayer = new L.Yandex();
	map.addLayer(yandexLayer);
<?php
*/
	} elseif ($MapType == 'Bing-Aerial') {
?>
	var imProv = new Cesium.BingMapsImageryProvider({
		url : 'https://dev.virtualearth.net',
		key: '<?php print $globalBingMapKey; ?>',
		mapStyle: Cesium.BingMapsStyle.AERIAL});
<?php
	} elseif ($MapType == 'Bing-Hybrid') {
?>
	var imProv = new Cesium.BingMapsImageryProvider({
		url : 'https://dev.virtualearth.net',
		key: '<?php print $globalBingMapKey; ?>',
		mapStyle: Cesium.BingMapsStyle.AERIAL_WITH_LABELS});
<?php
	} elseif ($MapType == 'Bing-Road') {
?>
	var imProv = new Cesium.BingMapsImageryProvider({
		url : 'https://dev.virtualearth.net',
		key: '<?php print $globalBingMapKey; ?>',
		mapStyle: Cesium.BingMapsStyle.ROAD});
<?php
/*
	} elseif ($MapType == 'Here-Roadmap') {
?>
	var hereLayer = new L.tileLayer.here({appId: '<?php print $globalHereappId; ?>',appcode: '<?php print $globalHereappCode; ?>',scheme: 'normal.day'});
	map.addLayer(hereLayer);
<?php
	} elseif ($MapType == 'Here-Aerial') {
?>
	var hereLayer = new L.tileLayer.here({appId: '<?php print $globalHereappId; ?>',appcode: '<?php print $globalHereappCode; ?>',scheme: 'satellite.day'});
	map.addLayer(hereLayer);
<?php
	} elseif ($MapType == 'Here-Hybrid') {
?>
	var hereLayer = new L.tileLayer.here({appId: '<?php print $globalHereappId; ?>',appcode: '<?php print $globalHereappCode; ?>',scheme: 'hybrid.day'});
	map.addLayer(hereLayer);
<?php
*/
	}
?>


// Converts from radians to degrees.
Math.degrees = function(radians) {
	return radians * 180 / Math.PI;
};

function getCookie(cname) {
	var name = cname + "=";
	var ca = document.cookie.split(';');
	for(var i=0; i<ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1);
		if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
	}
	return "";
}

function mapType(selectObj) {
	var idx = selectObj.selectedIndex;
	var atype = selectObj.options[idx].value;
	var type = atype.split('-');
	document.cookie =  'MapType='+type+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	if (type[0] == 'Mapbox') {
		document.cookie =  'MapType='+type[0]+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
		document.cookie =  'MapTypeId='+type[1]+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	} else {
		document.cookie =  'MapType='+atype+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	}
	window.location.reload();
}
function terrainType(selectObj) {
	var idx = selectObj.selectedIndex;
	var atype = selectObj.options[idx].value;
	var type = atype.split('-');
	document.cookie =  'MapTerrain='+type+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	window.location.reload();
}
function sattypes(selectObj) {
	var sattypes = [], sattype;
	for (var i=0, len=selectObj.options.length; i< len;i++) {
		sattype = selectObj.options[i];
		if (sattype.selected) {
			sattypes.push(sattype.value);
		}
	}
	document.cookie =  'sattypes='+sattypes.join()+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/'
	updateSat();
}
function airlines(selectObj) {
	var airs = [], air;
	for (var i=0, len=selectObj.options.length; i< len;i++) {
		air = selectObj.options[i];
		if (air.selected) {
			airs.push(air.value);
		}
	}
	document.cookie =  'Airlines='+airs.join()+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/'
}
function airlinestype(selectObj) {
	var idx = selectObj.selectedIndex;
	var airtype = selectObj.options[idx].value;
	document.cookie =  'airlinestype='+airtype+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/'
}
function sources(selectObj) {
	var sources = [], source;
	for (var i=0, len=selectObj.options.length; i< len;i++) {
		source = selectObj.options[i];
		if (source.selected) {
			sources.push(source.value);
		}
	}
	//document.cookie =  'Sources='+sources.join()+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	document.cookie =  'Sources='+sources.join()+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/';
}


function show2D() {
	document.cookie =  'MapFormat=2d; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/';
	window.location.reload();
}
function zoomInMap() {
	camera.moveForward();
}
function zoomOutMap() {
	camera.moveBackward();
}

function clickPolar(cb) {
	document.cookie =  'polar='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	window.location.reload();
}


function clickDisplayAirports(cb) {
	document.cookie =  'displayairports='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	window.location.reload();
}
function clickDisplayISS(cb) {
	document.cookie =  'displayiss='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	window.location.reload();
}
function clickDisplayMinimap(cb) {
	document.cookie =  'displayminimap='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	window.location.reload();
}

function update_polarLayer() {
	var polarnb;
	for (var i =0; i < viewer.dataSources.length; i++) {
		if (viewer.dataSources.get(i).name == 'polar-geojson.php') {
			polarnb = i;
			break;
		}
	}
//	console.log('polarnb 1 : '+polarnb);
	var geojsonSource = new Cesium.GeoJsonDataSource("geojson");
	var polar_geojson = geojsonSource.load("<?php print $globalURL; ?>/polar-geojson.php");
	polar_geojson.then(function (data) {
		if (typeof polarnb != 'undefined') var remove = viewer.dataSources.remove(viewer.dataSources.get(polarnb));
		viewer.dataSources.add(data);
	});
}


function bbox () {
	var rectangle = camera.computeViewRectangle();
	var west = Math.degrees(rectangle.west);
	var south = Math.degrees(rectangle.south);
	var east = Math.degrees(rectangle.east);
	var north = Math.degrees(rectangle.north);
	return west+','+south+','+east+','+north;
}

function update_airportsLayer() {
	var getZoom = getCookie('AirportZoom');
	if (getZoom == '') getZoom = 7;
//		if (map.getZoom() > getZoom) {
			//if (typeof airportsLayer == 'undefined' || map.hasLayer(airportsLayer) == false) {
//			var bbox = map.getBounds().toBBoxString();
//			airportsLayer = new L.GeoJSON.AJAX("<?php print $globalURL; ?>/airport-geojson.php?coord="+bbox,{
//		$(".showdetails").load("airport-data.php?"+Math.random()+"&airport_icao="+feature.properties.icao);

		
	
	//var airport_geojson = new Cesium.GeoJsonDataSource.load("<?php print $globalURL; ?>/airport-geojson.php?coord="+bbox());
	var airport_geojson = new Cesium.GeoJsonDataSource.load("<?php print $globalURL; ?>/airport-geojson.php");
	airport_geojson.then(function(data) {
		for (var i =0;i < data.entities.values.length; i++) {
			var billboard = new Cesium.BillboardGraphics();
			billboard.image = data.entities.values[i].properties.icon;
			billboard.scaleByDistance = new Cesium.NearFarScalar(1.0e2, 1, 2.0e6, 0.0);
			data.entities.values[i].billboard = billboard;
			data.entities.values[i].addProperty('type');
			data.entities.values[i].type = 'airport';
		}
		viewer.dataSources.add(data);
	});
}

//figures out the user's location
function getUserLocation(){
	//if the geocode is currently active then disable it, otherwise enable it
	if (!$(".geocode").hasClass("active"))
	{
		//add the active class
		$(".geocode").addClass("active");
		//check to see if geolocation is possible in the browser
		if (navigator.geolocation) {
			//gets the current position and calls a function to make use of it
			navigator.geolocation.getCurrentPosition(showPosition);
		} else {
			//if the geolocation is not supported by the browser let the user know
			alert("Geolocation is not supported by this browser.");
			//remove the active class
			$(".geocode").removeClass("active");
		}
	} else {
		//remove the user location marker
		removeUserPosition();
	}
}
//plots the users location on the map
function showPosition(position) {
	//creates a leaflet marker based on the coordinates we got from the browser and add it to the map
	var markerUser = viewer.entities.add({
		position : Cesium.Cartesian3.fromDegrees(position.coords.latitude, position.coords.longitude),
		name: "<?php echo _("Your location"); ?>",
		billboard : {
			image : '<?php print $globalURL; ?>/images/map-user.png',
			verticalOrigin : Cesium.VerticalOrigin.BOTTOM
		}
	});
	viewer.DataSource.add(markerUser);
	//pan the map to the users location
	//map.panTo([position.coords.latitude, position.coords.longitude]);
}

//removes the user postion off the map
function removeUserPosition(){
	//remove the marker off the map
	viewer.entities.remove(markerUser);
	//remove the active class
	$(".geocode").removeClass("active");
}

//determines the users heading based on the iphone
function getCompassDirection(){

	//if the compass is currently active then disable it, otherwise enable it
	if (!$(".compass").hasClass("active"))
	{
		//add the active class
		$(".compass").addClass("active");
		//check to see if the device orietntation event is possible on the browser
		if (window.DeviceOrientationEvent) {
			//first lets get the user location to mak it more user friendly
			getUserLocation();
			//add event listener for device orientation and call the function to actually get the values
			window.addEventListener('deviceorientation', capture_orientation, false);
		} else {
			//if the browser is not capable for device orientation let the user know
			alert("<?php echo _("Compass is not supported by this browser."); ?>");
			//remove the active class
			$(".compass").removeClass("active");
		}
	} else {
		//remove the event listener to disable the device orientation
		window.removeEventListener('deviceorientation', capture_orientation, false);
		//reset the orientation to be again north to south
		$("#live-map").css({ WebkitTransform: 'rotate(360deg)'});
		$("#live-map").css({'-moz-transform': 'rotate(360deg)'});
		$("#live-map").css({'-ms-transform': 'rotate(360deg)'});
		//remove the active class
		$(".compass").removeClass("active");
		//remove the user location marker
		removeUserPosition();
	}
}

//gets the users heading information
function capture_orientation (event) {
	//store the values of each of the recorded elements in a variable
	var alpha;
	var css;
	//Check for iOS property
	if(event.webkitCompassHeading) {
		alpha = event.webkitCompassHeading;
		//Rotation is reversed for iOS
		css = 'rotate(-' + alpha + 'deg)';
	}
	//non iOS
	else {
		alpha = event.alpha;
		webkitAlpha = alpha;
		if(!window.chrome) {
			//Assume Android stock and apply offset
			webkitAlpha = alpha-270;
			css = 'rotate(' + alpha + 'deg)';
		}
	}    
  
	//we use the "alpha" variable for the rotation effect
	$("#live-map").css({ WebkitTransform: css});
	$("#live-map").css({'-moz-transform': css});
	$("#live-map").css({'-ms-transform': css});
}




function clickVATSIM(cb) {
	//document.cookie =  'ShowVATSIM='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	document.cookie =  'ShowVATSIM='+cb.checked+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/';
}
function clickIVAO(cb) {
	//document.cookie =  'ShowIVAO='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	document.cookie =  'ShowIVAO='+cb.checked+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/';
}
function clickphpVMS(cb) {
	//document.cookie =  'ShowVMS='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	document.cookie =  'ShowVMS='+cb.checked+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/';
}
function clickSBS1(cb) {
	//document.cookie =  'ShowSBS1='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	document.cookie =  'ShowSBS1='+cb.checked+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/';
}
function clickAPRS(cb) {
	//document.cookie =  'ShowAPRS='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	document.cookie =  'ShowAPRS='+cb.checked+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/';
}
function unitdistance(selectObj) {
	var idx = selectObj.selectedIndex;
	var unit = selectObj.options[idx].value;
	document.cookie =  'unitdistance='+unit+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/';
}
function unitspeed(selectObj) {
	var idx = selectObj.selectedIndex;
	var unit = selectObj.options[idx].value;
	document.cookie =  'unitspeed='+unit+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/';
}
function unitaltitude(selectObj) {
	var idx = selectObj.selectedIndex;
	var unit = selectObj.options[idx].value;
	document.cookie =  'unitaltitude='+unit+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/';
}


$(".showdetails").on("click",".close",function(){
	$(".showdetails").empty();
	$("#aircraft_ident").attr('class','');
	//getLiveData(1);
	return false;
})


function displayData(data) {
	
	var dsn;
	for (var i =0; i < viewer.dataSources.length; i++) {
		if (viewer.dataSources.get(i).name == 'fam') {
			dsn = i;
			break;
		}
	}
	//console.log('dsn : '+dsn);
	
	var entities = data.entities.values;
//	j = 0;
	for (var i = 0; i < entities.length; i++) {
		var entity = entities[i];
		if (typeof dsn != 'undefined') var existing = viewer.dataSources.get(dsn);
		else var existing;

//    	var billboard = new Cesium.BillboardGraphics();
//	var iconURLpath = '/getImages.php?color=FF0000&resize=15&filename='+aircraft_shadow+'&heading='+heading;
	//var iconURLpath = '/getImages.php?color=FF0000&resize=15&filename='+aircraft_shadow;
//    	entity.point = undefined;
//    	billboard.image = iconURLpath;
//	entity.billboard = billboard;
//	entity.billboard = undefined;



		var orientation = new Cesium.VelocityOrientationProperty(entity.position)
		entity.orientation = orientation;

		if (typeof existing != 'undefined') {
			// console.log(entity.id);
			var last = viewer.dataSources.get(dsn).entities.getById(entity.id);
			if (typeof last == 'undefined') {
				//console.log('Not exist !');
				entity.addProperty('lastupdate');
				entity.lastupdate = Date.now();
				entity.addProperty('type');
				entity.type = 'flight';
				viewer.dataSources.get(dsn).entities.add(entity);
			} else {
				//last.addProperty('lastupdate');
				last.lastupdate = Date.now();
				last.type = 'flight';
			}
		} else {
			//console.log('First time');
			entity.addProperty('lastupdate');
			entity.lastupdate = Date.now();
			entity.addProperty('type');
			entity.type = 'flight';
		}
	}

	//console.log('end data');

	if (typeof dsn == 'undefined') {
		viewer.dataSources.add(data);
		dsn = viewer.dataSources.indexOf(data);
		//console.log(viewer.dataSources);
	} else {
		for (var i = 0; i < viewer.dataSources.get(dsn).entities.values.length; i++) {
			var entity = viewer.dataSources.get(dsn).entities.values[i];
			// console.log(entity);
//			if (entity.isShowing === false) {
//				console.log('Remove an entity show');
//				viewer.dataSources.get(dsn).entities.remove(entity);
//			}
			//console.log(entity.isAvailable(Cesium.JulianDate.now()));
//			if (entity.isAvailable(Cesium.JulianDate.now()) === false) {
//				console.log('Remove an entity julian');
//				viewer.dataSources.get(dsn).entities.remove(entity);
//			}
			//console.log(entity.lastupdate);
			if (parseInt(entity.lastupdate) < Math.floor(Date.now()-<?php if (isset($globalMapRefresh)) print $globalMapRefresh*2000; else print '60000'; ?>)) {
//				console.log('Remove an entity date');
				viewer.dataSources.get(dsn).entities.remove(entity);
			} else {
				//console.log(parseInt(entity.lastupdate)+' > '+Math.floor(Date.now()-100));
			}
	//    	    console.log(Math.floor(Date.now()-1000));
	    //console.log(entity);

		}
	}
	var MapTrack = getCookie('MapTrack');
	if (MapTrack != '') {
		viewer.trackedEntity = viewer.dataSources.get(dsn).entities.getById(MapTrack);
		$(".showdetails").load("<?php print $globalURL; ?>/aircraft-data.php?"+Math.random()+"&flightaware_id="+flightaware_id+"&currenttime="+Date.parse(currenttime.toString()));
		$("#aircraft_ident").attr('class',flightaware_id);
		//lastid = MapTrack;
	}


//    viewer.dataSources.add(data);

//    }
    //console.log(viewer.dataSources.get(dsn).name);
	$(".infobox").html("<h4>Aircrafts detected</h4><br /><b>"+viewer.dataSources.get(dsn).entities.values.length+"</b>");
    //console.log(viewer.dataSources.get(dsn).entities.values.length);
    //console.log(viewer.dataSources.length);
    //console.log(dsn);
};

function displayDataSat(data) {
	
	var dsn;
	for (var i =0; i < viewer.dataSources.length; i++) {
		if (viewer.dataSources.get(i).name == 'famsat') {
			dsn = i;
			break;
		}
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
			//console.log('First time');
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
	
//    viewer.dataSources.add(data);

//    }
    //console.log(viewer.dataSources.get(dsn).name);
//	$(".infobox").html("<h4>Aircrafts detected</h4><br /><b>"+viewer.dataSources.get(dsn).entities.values.length+"</b>");
    //console.log(viewer.dataSources.get(dsn).entities.values.length);
    //console.log(viewer.dataSources.length);
    //console.log(dsn);
};

function updateData() {
  //  console.log('Update Data');
//    var geojsonSource = new Cesium.GeoJsonDataSource("geojson");
//    var dataSource = geojsonSource.load('/live/geojson');
//    var dataSource = new Cesium.CzmlDataSource.load('/live-czml.php');
//     var czmlds = new Cesium.CzmlDataSource();
	var livedata = czmlds.process('<?php print $globalURL; ?>/live-czml.php?' + Date.now());
//    viewer.dataSources.add(dataSource);
    
	livedata.then(function (data) { 
//		console.log(data);
		displayData(data);
	});
//    viewer.zoomTo(dataSource);
}

function updateSat() {
	var livesatdata = czmldssat.process('<?php print $globalURL; ?>/live-sat-czml.php?' + Date.now());
	livesatdata.then(function (data) { 
		displayDataSat(data);
	});
}

function updateISS() {
	var issdata = Cesium.loadJson('https://api.wheretheiss.at/v1/satellites/25544');
	issdata.then(function (data) {
		//console.log(data);
		var altitude = Math.round(data.altitude*10000)/10;
		var entity = viewer.entities.getById('iss');
		if (typeof entity == 'undefined') {
			//var time = Cesium.JulianDate.now();
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
			
			//entity.position.setInterpolationOptions({
			//    interpolationDegree : 30,
			//    interpolationAlgorithm : Cesium.HermitePolynomialApproximation
			//});
		} else {
			var property = entity.position;
			var currenttime = viewer.clock.currentTime;
			var time = Cesium.JulianDate.addSeconds(currenttime, 30, new Cesium.JulianDate());
    			var position = Cesium.Cartesian3.fromDegrees(data.longitude,data.latitude,altitude);
			property.addSample(time, position);
			entity.position = property;
			//entity.position = Cesium.Cartesian3.fromDegrees(data.longitude,data.latitude,altitude);
		}
		//viewer.trackedEntity = entity;
	});
}

<?php
	if (isset($globalBingMapKey) && $globalBingMapKey != '') {
?>
Cesium.BingMapsApi.defaultKey = '<?php print $globalBingMapKey; ?>';
<?php
	}
?>

//var lastid;

var viewer = new Cesium.Viewer('live-map', {
    sceneMode : Cesium.SceneMode.SCENE3D,
    imageryProvider : imProv,
//    imageryProvider : Cesium.createTileMapServiceImageryProvider({
//        url : Cesium.buildModuleUrl('Assets/Textures/NaturalEarthII')
//    }),
    timeline : false,
    animation : false,
    shadows : true,
//    selectionIndicator : false,
    baseLayerPicker: false,
    infoBox: false,
   navigationHelpButton: false,
    geocoder: false,
//    scene3DOnly: true,
    fullscreenButton: false,
//    terrainProvider : new Cesium.CesiumTerrainProvider({
//        url : 'https://assets.agi.com/stk-terrain/world',
//	requestWaterMask : true,
//        requestVertexNormals : true
//    }),
    terrainShadows: Cesium.ShadowMode.DISABLED
//    automaticallyTrackDataSourceClocks: false
});

// Set initial camera position
var camera = viewer.camera;
<?php
	if (isset($globalCenterLatitude) && isset($globalCenterLongitude)) {

?>
camera.setView({
	destination : Cesium.Cartesian3.fromDegrees(<?php echo $globalCenterLongitude; ?>,<?php echo $globalCenterLatitude; ?>, 5000000.0),
});
<?php

	}
?>

var layers = viewer.scene.imageryLayers;
//var clouds = layers.addImageryProvider(
//new Cesium.createOpenStreetMapImageryProvider({
//		url : 'http://b.tile.openweathermap.org/map/clouds',
//		fileExtension : 'png',
//		tileMatrixSetID : 'a'
//	}
//));




<?php
//	if (!isset($_COOKIE['MapTerrain']) || $_COOKIE['MapTerrain'] == 'stk') {
?>
var MapTerrain = getCookie('MapTerrain');
if (MapTerrain == 'stk' || MapTerrain == '') {
	var cesiumTerrainProviderMeshes = new Cesium.CesiumTerrainProvider({
	    url : 'https://assets.agi.com/stk-terrain/world',
	    requestWaterMask : true,
	    requestVertexNormals : true
	});
	viewer.terrainProvider = cesiumTerrainProviderMeshes;
} else if (MapTerrain == 'ellipsoid') {
<?php
//	} elseif (isset($_COOKIE['MapTerrain']) && $_COOKIE['MapTerrain'] == 'ellipsoid') {
?>
	var ellipsoidProvider = new Cesium.EllipsoidTerrainProvider({
	    requestWaterMask : true,
	    requestVertexNormals : true
	});
	viewer.terrainProvider = ellipsoidProvider;
<?php 
//	} elseif (isset($_COOKIE['MapTerrain']) && $_COOKIE['MapTerrain'] == 'vrterrain') {
?>
} else if (MapTerrain == 'vrterrain') {
	var vrTheWorldProvider = new Cesium.VRTheWorldTerrainProvider({
	    url : 'https://www.vr-theworld.com/vr-theworld/tiles1.0.0/73/',
	    requestWaterMask : true,
	    requestVertexNormals : true,
	    credit : 'Terrain data courtesy VT MÃ„K'
	});
	viewer.terrainProvider = vrTheWorldProvider;
}
<?php
//	}
?>
viewer.scene.globe.enableLighting = true;
viewer.scene.globe.depthTestAgainstTerrain = true;
//var dataSource = new Cesium.CzmlDataSource.load('/live-czml.php');
//dataSource.then(function (data) { 
//    displayData(data);
//});
 var czmlds = new Cesium.CzmlDataSource();


updateData();
<?php
	if (isset($globalMapSatellites) && $globalMapSatellites) {
?>
var czmldssat = new Cesium.CzmlDataSource();
updateSat();
setInterval(function(){updateSat()},'20000');
//updateISS();
//setInterval(function(){updateISS()},'10000');
<?php
	}
?>


<?php
		if (!((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM) || (isset($globalphpVMS) && $globalphpVMS)) && (isset($_COOKIE['polar']) && $_COOKIE['polar'] == 'true')) {
?>
update_polarLayer();
setInterval(function(){update_polarLayer()},<?php if (isset($globalMapRefresh)) print $globalMapRefresh*1000*2; else print '60000'; ?>);
<?php
		}
?>
		
var handler = new Cesium.ScreenSpaceEventHandler(viewer.scene.canvas);
handler.setInputAction(function(click) {
	var pickedObject = viewer.scene.pick(click.position);
	if (Cesium.defined(pickedObject)) {
		//console.log(pickedObject.id);
		var currenttime = viewer.clock.currentTime;
		//console.log(pickedObject.id.position.getValue(viewer.clock.currentTime));
		console.log(pickedObject.id);
//		if (typeof pickedObject.id.lastupdate != 'undefined') {
		delCookie('MapTrack');
		if (pickedObject.id.type == 'flight') {
			flightaware_id = pickedObject.id.id;
			$(".showdetails").load("<?php print $globalURL; ?>/aircraft-data.php?"+Math.random()+"&flightaware_id="+flightaware_id+"&currenttime="+Date.parse(currenttime.toString()));
			var dsn;
			for (var i =0; i < viewer.dataSources.length; i++) {
				if (viewer.dataSources.get(i).name == 'fam') {
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
		} else if (pickedObject.id.type == 'sat') {
			$(".showdetails").load("<?php print $globalURL; ?>/space-data.php?"+Math.random()+"&currenttime="+Date.parse(currenttime.toString())+"&sat="+encodeURI(pickedObject.id.id));
//		} else if (pickedObject.id.name == 'iss') {
//			$(".showdetails").load("<?php print $globalURL; ?>/space-data.php?"+Math.random()+"&currenttime="+Date.parse(currenttime.toString()));
//		} else if (pickedObject.id.id == 'ISS (ZARYA)') {
//			$(".showdetails").load("<?php print $globalURL; ?>/space-data.php?"+Math.random()+"&currenttime="+Date.parse(currenttime.toString()));
//		} else if (typeof pickedObject.id.properties.icao != 'undefined') {
		} else if (pickedObject.id.type == 'airport') {
			var icao = pickedObject.id.properties.icao;
			$(".showdetails").load("<?php print $globalURL; ?>/airport-data.php?"+Math.random()+"&airport_icao="+icao);
		}
	}
}, Cesium.ScreenSpaceEventType.LEFT_CLICK);


//var reloadpage = setInterval(function() { updateData(); },30000);
var reloadpage = setInterval(function(){updateData()},<?php if (isset($globalMapRefresh)) print $globalMapRefresh*1000; else print '30000'; ?>);

if (getCookie('displayairports') == 'true') {
	update_airportsLayer();
}
if (getCookie('displayminimap') == '' || getCookie('displayminimap') == 'true') {
	CesiumMiniMap(viewer);
}
