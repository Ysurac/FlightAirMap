<?php
	require_once('../require/settings.php');
	require_once('../require/class.Language.php'); 
?>

document.cookie =  'MapFormat=3d; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
<?php
	if (isset($_COOKIE['MapType'])) $MapType = $_COOKIE['MapType'];
	else $MapType = $globalMapProvider;
//	unset($_COOKIE['MapType']);

	if ($MapType != 'Mapbox' && $MapType != 'OpenStreetMap' && $MapType != 'Bing-Aerial' && $MapType != 'Bing-Hybrid' && $MapType != 'Bing-Road' && $MapType != 'offline' && $MapType != 'ArcGIS-Streetmap' && $MapType != 'ArcGIS-Satellite' && $MapType != 'NatGeo-Street') {
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
	} elseif ($MapType == 'ArcGIS-Satellite') {
?>
	var imProv = new Cesium.ArcGisMapServerImageryProvider({
		url : 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer',
		credit : 'ESRI'
	});
<?php
	} elseif ($MapType == 'ArcGIS-Streetmap') {
?>
	var imProv = new Cesium.ArcGisMapServerImageryProvider({
		url : 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer',
		credit : 'ESRI'
	});
<?php
	} elseif ($MapType == 'NatGeo-Street') {
?>
	var imProv = new Cesium.ArcGisMapServerImageryProvider({
		url : 'https://server.arcgisonline.com/ArcGIS/rest/services/NatGeo_World_Map/MapServer',
		credit : 'ESRI'
	});
<?php
	} elseif ($MapType == 'offline') {
?>
	var imProv = new Cesium.createTileMapServiceImageryProvider({
		url : Cesium.buildModuleUrl('Assets/Textures/NaturalEarthII'),
		maximumLevel : 2,
		credit : 'Imagery courtesy Natural Earth'
	});
<?php
	}  elseif (isset($globalMapCustomLayer[$MapType])) {
		$customid = $MapType;
?>
	var imProv = Cesium.createOpenStreetMapImageryProvider({
		url : '<?php print $globalMapCustomLayer[$customid]['url']; ?>',
		maximumLevel: <?php if (isset($globalMapCustomLayer[$customid]['maxZoom'])) print $globalMapCustomLayer[$customid]['maxZoom']; else print '99'; ?>,
		minimumLevel: <?php if (isset($globalMapCustomLayer[$customid]['minZoom'])) print $globalMapCustomLayer[$customid]['minZoom']; else print '0'; ?>,
		credit: '<?php print $globalMapCustomLayer[$customid]['attribution']; ?>'
	});
<?php
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

var entitybbox;
function bbox() {
	var position = viewer.scene.camera.positionCartographic;
	var pitch = viewer.scene.camera.pitch;
//	console.log('height: '+position.height);
//	console.log('pitch: '+Math.degrees(pitch));
	if (position.height < 5000000 && pitch < Math.radians(-20)) { 
		//viewer.entities.remove(entitybbox);
		var rectangle = viewer.scene.camera.computeViewRectangle(viewer.scene.globe.ellipsoid);
		var west = Math.degrees(rectangle.west);
		var south = Math.degrees(rectangle.south);
		var east = Math.degrees(rectangle.east);
		var north = Math.degrees(rectangle.north);
		//console.log(west+','+south+','+east+','+north);
		/*
		entitybbox = viewer.entities.add({
			rectangle : {
				coordinates : rectangle,
				material : Cesium.Color.RED.withAlpha(0.2),
				outline : true,
				outlineColor : Cesium.Color.RED
			}
		});
		*/
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

function update_airportsLayer() {
	var getZoom = getCookie('AirportZoom');
	if (getZoom == '') getZoom = 7;
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

function resolutionScale(scale) {
	createCookie('resolutionScale',scale,9999);
	viewer.resolutionScale = scale;
}

function update_locationsLayer() {
	var locnb;
	for (var i =0; i < viewer.dataSources.length; i++) {
		if (viewer.dataSources.get(i).name == 'location') {
			locnb = i;
			break;
		}
	}
<?php
	if (isset($globalMapUseBbox) && $globalMapUseBbox) {
?>
	var loc_geojson = Cesium.loadJson("<?php print $globalURL; ?>/location-geojson.php?coord="+bbox());
<?php
	} else {
?>
	var loc_geojson = Cesium.loadJson("<?php print $globalURL; ?>/location-geojson.php");
<?php
	}
?>
	loc_geojson.then(function(geojsondata) {
		loc = new Cesium.CustomDataSource('location');
		for (var i =0;i < geojsondata.features.length; i++) {
			data = geojsondata.features[i].properties;
			var entity = loc.entities.add({
				id: data.id,
				position: Cesium.Cartesian3.fromDegrees(geojsondata.features[i].geometry.coordinates[0],geojsondata.features[i].geometry.coordinates[1]),
				billboard: {
					image: data.icon,
					verticalOrigin: Cesium.VerticalOrigin.BOTTOM
				},
				type: 'loc'
			});
		}
		if (typeof locnb != 'undefined') var remove = viewer.dataSources.remove(viewer.dataSources.get(locnb));
		viewer.dataSources.add(loc);
	});
}

function update_tsk() {
	var tsknb;
	for (var i =0; i < viewer.dataSources.length; i++) {
		if (viewer.dataSources.get(i).name == 'tsk') {
			tsknb = i;
			break;
		}
	}
	var tsk_geojson = Cesium.loadJson("<?php print $globalURL; ?>/tsk-geojson.php?tsk=<?php print filter_input(INPUT_GET,'tsk',FILTER_SANITIZE_URL); ?>");
	tsk_geojson.then(function(geojsondata) {
		tsk = new Cesium.CustomDataSource('tsk');
		for (var i =0;i < geojsondata.features.length; i++) {
			if (geojsondata.features[i].geometry.type == 'LineString') {
				data = geojsondata.features[i].properties;
				var positionsarray = [];
				for (var j = 0; j < geojsondata.features[i].geometry.coordinates.length; j++) {
					positionsarray.push(Cesium.Cartesian3.fromDegreesArray(geojsondata.features[i].geometry.coordinates[j])[0]);
				}
				var entity = tsk.entities.add({
					polyline: {
						positions: positionsarray,
						width : 5,
						material :  new Cesium.PolylineArrowMaterialProperty(Cesium.Color.BLUE)
					}
				});
			} else {
				data = geojsondata.features[i].properties;
				var entity = tsk.entities.add({
					position: Cesium.Cartesian3.fromDegreesArray(geojsondata.features[i].geometry.coordinates)[0],
					billboard: {
						image: data.icon,
						verticalOrigin: Cesium.VerticalOrigin.BOTTOM
					},
					type: 'tsk'
				});
			}
		}
		if (typeof tsknb != 'undefined') var remove = viewer.dataSources.remove(viewer.dataSources.get(tsknb));
		viewer.dataSources.add(tsk);
	});
}

$(".showdetails").on("click",".close",function(){
	$(".showdetails").empty();
	$("#pointident").attr('class','');
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
	baseLayerPicker: false,
	imageryProvider : imProv,
	timeline : archive,
	animation : false,
	shadows : <?php if ((isset($globalMap3DShadows) && $globalMap3DShadows === FALSE) || (isset($_COOKIE['map3dnoshadows']) && $_COOKIE['map3dnoshadows'] == 'true')) print 'false'; else print 'true'; ?>,
	infoBox : false,
	navigationHelpButton : false,
	geocoder : false,
	fullscreenButton : false
});

// Set initial camera position
var camera = viewer.camera;
<?php
	if (isset($_COOKIE['lastcentercoord']) || (isset($globalCenterLatitude) && isset($globalCenterLongitude) && $globalCenterLatitude != '' && $globalCenterLongitude != '')) {
		if (isset($_COOKIE['lastcentercoord'])) {
			$lastcentercoord = explode(',',$_COOKIE['lastcentercoord']);
			if (!isset($lastcentercoord[3])) $zoom = $lastcentercoord[2]*1000000.0;
			else $zoom = $lastcentercoord[3];
			$viewcenterlatitude = $lastcentercoord[0];
			$viewcenterlongitude = $lastcentercoord[1];
		} else {
			$zoom = $globalLiveZoom*1000000.0;
			$viewcenterlatitude = $globalCenterLatitude;
			$viewcenterlongitude = $globalCenterLongitude;
		}
?>
camera.setView({
	destination : Cesium.Cartesian3.fromDegrees(<?php echo $viewcenterlongitude; ?>,<?php echo $viewcenterlatitude; ?>, <?php echo $zoom; ?>),
});
<?php
	}
?>
<?php
	if (isset($globalMap3DTiles) && $globalMap3DTiles != '') {
?>
var tileset = viewer.scene.primitives.add(new Cesium.Cesium3DTileset({
	url: '<?php print $globalMap3DTiles; ?>'
}));
<?php
	}
?>

var layers = viewer.scene.imageryLayers;
<?php
	if (isset($globalMapOffline) && $globalMapOffline === TRUE) {
?>
var MapTerrain = 'ellipsoid';
<?php
	} else {
?>
var MapTerrain = getCookie('MapTerrain');
<?php
	}
?>

if (MapTerrain == 'stk' || MapTerrain == '') {
	var cesiumTerrainProviderMeshes = new Cesium.CesiumTerrainProvider({
		url : 'https://assets.agi.com/stk-terrain/world',
		requestWaterMask : true,
		requestVertexNormals : true
	});
	viewer.terrainProvider = cesiumTerrainProviderMeshes;
} else if (MapTerrain == 'articdem') {
	var cesiumTerrainProviderMeshesArtic = new Cesium.CesiumTerrainProvider({
		url : 'https://assets.agi.com/stk-terrain/v1/tilesets/ArticDEM/tiles',
		requestWaterMask : true,
		requestVertexNormals : true
	});
	viewer.terrainProvider = cesiumTerrainProviderMeshesArtic;
} else if (MapTerrain == 'ellipsoid') {
	var ellipsoidProvider = new Cesium.EllipsoidTerrainProvider({
		requestWaterMask : true,
		requestVertexNormals : true
	});
	viewer.terrainProvider = ellipsoidProvider;
} else if (MapTerrain == 'vrterrain') {
	var vrTheWorldProvider = new Cesium.VRTheWorldTerrainProvider({
		url : 'http://www.vr-theworld.com/vr-theworld/tiles1.0.0/73/',
		requestWaterMask : true,
		requestVertexNormals : true,
		credit : 'Terrain data courtesy VT MÄK'
	});
	viewer.terrainProvider = vrTheWorldProvider;
}


// Water effect

//viewer.scene.globe.oceanNormalMapUrl = 'js/Cesium/Assets/Textures/waterNormals.jpg';
viewer.scene.globe.oceanNormalMapUrl = 'images/shaders/water/water_new_height.png';
viewer.scene.globe.showWaterEffect = true;

// Lightning
viewer.scene.globe.enableLighting = true;
/*
// Cache
viewer.scene.globe.tileCacheSize = 1000;
*/
// Render size before rescale
if (getCookie('resolutionScale') != '') {
	viewer.resolutionScale = getCookie('resolutionScale');
}
/*
//viewer.scene.globe.maximumScreenSpaceError = 1;

// ShadowMap
viewer.shadowMap.pointLightRadius = 100;
viewer.shadowMap.cascadesEnabled = true;
viewer.shadowMap.maximumDistance = 3E3;
viewer.shadowMap.size = 2048;
viewer.shadowMap.softShadows = true;
viewer.shadowMap.darkness = .3;
*/
// Color
//viewer.scene.globe.imageryLayers._layers[0].contrast = 1.1;
//viewer.scene.globe.imageryLayers._layers[0].saturation = 1.1;
//viewer.scene.skyAtmosphere.brightnessShift = 0.9;
//viewer.scene.skyAtmosphere.saturationShift = 0.7;
  

if (getCookie('displayminimap') == '' || getCookie('displayminimap') == 'true') {
	CesiumMiniMap(viewer, {osm: true});
	viewer.scene.frameState.creditDisplay.addDefaultCredit(new Cesium.Credit('(Minimap: Map data © OpenStreetMap contributors, Open Database Licence)'));
}

<?php
	if (isset($globalTSK) && $globalTSK && isset($_GET['tsk'])) {
?>
update_tsk();
<?php
	}
?>

update_locationsLayer();
setInterval(function(){update_locationsLayer()},<?php if (isset($globalMapRefresh)) print $globalMapRefresh*1000*2; else print '60000'; ?>);
viewer.camera.moveEnd.addEventListener(function() { 
<?php
	if (isset($globalMapUseBbox) && $globalMapUseBbox) {
?>
	update_locationsLayer();
<?php
	}
?>
	var position = viewer.camera.positionCartographic;
	createCookie('lastcentercoord',Cesium.Math.toDegrees(position.latitude)+','+Cesium.Math.toDegrees(position.longitude)+',8,'+position.height,2);
});