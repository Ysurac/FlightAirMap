<?php
	require_once('../require/settings.php');
	require_once('../require/class.Language.php'); 
?>

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
	});
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
Math.radians = function(degrees) {
	return degrees * Math.PI / 180;
};


function zoomInMap() {
	camera.moveForward();
}
function zoomOutMap() {
	camera.moveBackward();
}

function bbox () {
	var position = viewer.camera.positionCartographic;
	var pitch = viewer.camera.pitch;
//	console.log('height: '+position.height);
//	console.log('pitch: '+Math.degrees(pitch));
	if (position.height < 1000000 && pitch < Math.radians(-25)) { 
		var rectangle = camera.computeViewRectangle();
		var west = Math.degrees(rectangle.west);
		var south = Math.degrees(rectangle.south);
		var east = Math.degrees(rectangle.east);
		var north = Math.degrees(rectangle.north);
		return west+','+south+','+east+','+north;
	} else {
		return '';
	}
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

$(".showdetails").on("click",".close",function(){
	$(".showdetails").empty();
	$("#aircraft_ident").attr('class','');
	//getLiveData(1);
	return false;
})

<?php
	if (isset($globalBingMapKey) && $globalBingMapKey != '') {
?>
Cesium.BingMapsApi.defaultKey = '<?php print $globalBingMapKey; ?>';
<?php
	}
?>

if (getCookie('archive') == '' || getCookie('archive') == 'false') {
	var archive = false;
} else {
	var archive = true;
	document.getElementById("archivebox").style.display = "inline";
}

var viewer = new Cesium.Viewer('live-map', {
    sceneMode : Cesium.SceneMode.SCENE3D,
    imageryProvider : imProv,
//    imageryProvider : Cesium.createTileMapServiceImageryProvider({
//        url : Cesium.buildModuleUrl('Assets/Textures/NaturalEarthII')
//    }),
    timeline : archive,
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
//    terrainShadows: Cesium.ShadowMode.DISABLED
//    automaticallyTrackDataSourceClocks: false
});

// Set initial camera position
var camera = viewer.camera;
<?php
	if (isset($globalCenterLatitude) && isset($globalCenterLongitude) && $globalCenterLatitude != '' && $globalCenterLongitude != '') {
		$zoom = $globalLiveZoom*1000000.0;
?>
camera.setView({
	destination : Cesium.Cartesian3.fromDegrees(<?php echo $globalCenterLongitude; ?>,<?php echo $globalCenterLatitude; ?>, <?php echo $zoom; ?>),
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
	    url : 'http://www.vr-theworld.com/vr-theworld/tiles1.0.0/73/',
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
		
var handler = new Cesium.ScreenSpaceEventHandler(viewer.scene.canvas);
if (getCookie('displayminimap') == '' || getCookie('displayminimap') == 'true') {
	CesiumMiniMap(viewer);
}


