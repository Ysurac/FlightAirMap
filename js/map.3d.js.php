<?php
	require_once('../require/settings.php');
	require_once('../require/class.Language.php'); 
	if ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'mph') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'mph')) {
?>
    var unitspeed = 'mph';
<?php
	} elseif ((!isset($_COOKIE['unitspeed']) && isset($globalUnitSpeed) && $globalUnitSpeed == 'knots') || (isset($_COOKIE['unitspeed']) && $_COOKIE['unitspeed'] == 'knots')) {
?>
    var unitspeed = 'knots';
<?php
	} else {
?>
    var unitspeed = 'kmh';
<?php
	}
	if ((!isset($_COOKIE['unitaltitude']) && isset($globalUnitAltitude) && $globalUnitAltitude == 'feet') || (isset($_COOKIE['unitaltitude']) && $_COOKIE['unitaltitude'] == 'feet')) {
?>
    var unitaltitude = 'feet';
<?php
	} else {
?>
    var unitaltitude = 'm';
<?php
	}
?>

document.cookie =  'MapFormat=3d; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
<?php
	if (isset($_COOKIE['MapType3D'])) $MapType = $_COOKIE['MapType3D'];
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
		else $MapBoxId = $_COOKIE['MapType3DId'];
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
	} elseif ($MapType == 'OpenSeaMap') {
?>
	var imProv = Cesium.createOpenStreetMapImageryProvider({
		url : 'https://tiles.openseamap.org/seamark/',
		credit: 'Map data © OpenSeaMap contributors, © OpenStreetMap contributors, ' +
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
	} elseif ($MapType == 'ArcGIS-Ocean') {
?>
	var imProv = new Cesium.ArcGisMapServerImageryProvider({
		url : 'https://server.arcgisonline.com/ArcGIS/rest/services/Ocean_Basemap/MapServer',
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

var cloudscenter;
function delete_clouds() {
	for (var i =0; i < viewer.dataSources.length; i++) {
		if (viewer.dataSources.get(i).name == 'clouds') {
			viewer.dataSources.remove(viewer.dataSources.get(i),true);
			break;
		}
	}
}

function create_clouds(cposition) {
	//console.log('Create clouds');
	cloudscenter = cposition;
	$.getJSON('/weather-json.php?latitude='+Cesium.Math.toDegrees(cposition.latitude)+'&longitude='+Cesium.Math.toDegrees(cposition.longitude),function(data) {
		//delete_clouds();
		var ctime = Cesium.JulianDate.toGregorianDate(viewer.clock.currentTime);
		var chour = ctime['hour'];
		var cminute = ctime['minute'];
		var datasource = new Cesium.CustomDataSource('clouds');
		var clouds = {ci: ['cirrocumulus1.glb','cirrocumulus2.glb','cirrocumulus3.glb','cirrocumulus4.glb','cirrocumulus5.glb','cirrocumulus6.glb','cirrocumulus7.glb','cirrocumulus8.glb','cirrocumulus9.glb']}; 
		    //ac: ['altocumulus1.glb','altocumulus2.glb','altocumulus3.glb','altocumulus4.glb','altocumulus5.glb','altocumulus6.glb'], 
		    //ns: ['nimbus1.glb','nimbus_sl1.glb','nimbus_sl2.glb','nimbus_sl3.glb','nimbus_sl4.glb','nimbus_sl5.glb','nimbus_sl6.glb']};
		    //st: ['stratus1.glb','stratus2.glb','stratus3.glb','stratus4.glb','stratus5.glb']};
		    // st need to follow camera
		var cloudsb = {ac: ['altocumulus1.png','altocumulus2.png','altocumulus3.png','altocumulus4.png','altocumulus5.png','altocumulus6.png','altocumulus7.png','altocumulus8.png','altocumulus9.png'], 
		    st: ['stratus1.png','stratus2.png','stratus3.png','stratus4.png','stratus5.png','stratus6.png'],
		    sc: ['congestus1.png','congestus2.png','congestus3.png'],
		    cu: ['cumulus1.png','cumulus2.png','cumulus3.png','cumulus4.png','cumulus5.png','cumulus6.png','cumulus7.png','cumulus8.png','cumulus9.png']};
		for (var i = 0; i < data.length; i++) {
			var height = data[i]['alt'];
			var cov = data[i]['cov'];
			var cloud = clouds[data[i]['type']];
			//var cloud = clouds['ci'];
			//var cloudb = cloudsb['fg'];
			var cloudb = cloudsb[data[i]['type']];
			var rh = data[i]['rh'];
			var timecolors = [[100,100,100],[100,100,100],[255,150,100],[255,255,255],[255,255,255],[255,255,255],[255,150,100],[100,100,100],[100,100,100],[100,100,100],[100,100,100]];
			var timecolorsstep = chour/24*10;
			if (Math.round(timecolorsstep) > Math.ceil(timecolorsstep)) {
				console.log(Math.ceil(timecolorsstep));
				var prevcolor = timecolors[Math.ceil(timecolorsstep)];
				var nextcolor = timecolors[Math.round(timecolorsstep)];
			} else {
				if (Math.round(timecolorsstep) == 0) {
					var prevcolor = timecolors[0];
				} else {
					var prevcolor = timecolors[Math.round(timecolorsstep)-1];
				}
				var nextcolor = timecolors[Math.round(timecolorsstep)];
			}
			var currentcolor = getColor(prevcolor,nextcolor,3*60,(timecolorsstep%3)*60+cminute);
			var color = new Cesium.Color.multiply(new Cesium.Color(rh/100,rh/100,rh/100,1),new Cesium.Color.fromBytes(currentcolor['r'],currentcolor['v'],currentcolor['b'],255), new Cesium.Color());

			if (typeof cloudb != 'undefined') {
				for (j = 0; j < 2000*cov; j++) {
					var cloudcoord = generateRandomPoint(Cesium.Math.toDegrees(cposition.latitude),Cesium.Math.toDegrees(cposition.longitude), height,240,70000);
					var position = Cesium.Cartesian3.fromDegrees(cloudcoord['longitude'],cloudcoord['latitude'],cloudcoord['alt']);
					var heading = Cesium.Math.toRadians(135);
					var pitch = 0;
					var roll = 0;
					var hpr = new Cesium.HeadingPitchRoll(heading, pitch, roll);
					var orientation = Cesium.Transforms.headingPitchRollQuaternion(position, hpr);
					var urlb = '/images/weather/clouds/'+cloudb[Math.floor((Math.random() * cloudb.length))];
					var entity = datasource.entities.add({
					    name : url,
					    position : position,
					    orientation : orientation,
					    billboard: {
						image : urlb,
						sizeInMeters: true,
						scale: Math.random()*10.0,
						horizontalOrigin: Cesium.HorizontalOrigin.CENTER,
						verticalOrigin: Cesium.VerticalOrigin.BOTTOM,
						eyeOffset: new Cesium.Cartesian3(0,6,0),
						heightReference: Cesium.HeightReference.RELATIVE_TO_GROUND,
						//fillColor: Cesium.Color.fromCssColorString("#ffc107"),
						//translucencyByDistance: new Cesium.NearFarScalar(200,.8,5E4,.2)
						distanceDisplayCondition: new Cesium.DistanceDisplayCondition(0.0,70000.0),
						translucencyByDistance: new Cesium.NearFarScalar(1E5/2,.9,1E5,.3),
						color: color,
						opacity: .9
					    }
					});
					/*
					var position = Cesium.Cartesian3.fromDegrees(cloudcoord['longitude'],cloudcoord['latitude'],cloudcoord['alt']);
					var urlb = '/images/weather/clouds/rain.png';
					var entity = datasource.entities.add({
					    name : url,
					    position : position,
					    orientation : orientation,
					    billboard: {
						image : urlb,
						sizeInMeters: true,
						scale: Math.random()*10.0,
						horizontalOrigin: Cesium.HorizontalOrigin.CENTER,
						verticalOrigin: Cesium.VerticalOrigin.TOP,
						eyeOffset: new Cesium.Cartesian3(0,6,0),
						heightReference: Cesium.HeightReference.RELATIVE_TO_GROUND,
						//fillColor: Cesium.Color.fromCssColorString("#ffc107"),
						//translucencyByDistance: new Cesium.NearFarScalar(200,.8,5E4,.2)
						distanceDisplayCondition: new Cesium.DistanceDisplayCondition(0.0,70000.0),
						translucencyByDistance: new Cesium.NearFarScalar(1E5/2,.9,1E5,.3),
						//color: color,
						opacity: .9
					    }
					});
					*/
				}
			}
			//console.log(data[i]);
			//console.log(cloud);
			if (typeof cloud != 'undefined') {
				console.log('models');
				for (j = 0; j < 1000*cov; j++) {
					var cloudcoord = generateRandomPoint(Cesium.Math.toDegrees(cposition.latitude),Cesium.Math.toDegrees(cposition.longitude), height,240,70000);
					//console.log(cloudcoord);
					var position = Cesium.Cartesian3.fromDegrees(cloudcoord['longitude'],cloudcoord['latitude'],cloudcoord['alt']);
					if (data[i]['type'] == 'st') {
						var heading = camera.heading;
					} else {
						var heading = Cesium.Math.toRadians(135);
					}
					var pitch = 0;
					var roll = 0;
					var hpr = new Cesium.HeadingPitchRoll(heading, pitch, roll);
					var orientation = Cesium.Transforms.headingPitchRollQuaternion(position, hpr);
					var url = '/models/gltf2/weather/'+cloud[Math.floor((Math.random() * cloud.length))];
					var entity = datasource.entities.add({
					    name : url,
					    position : position,
					    orientation : orientation,
					    model : {
						uri : url,
						minimumPixelSize : 1,
						maximumScale : 20000,
						heightReference: Cesium.HeightReference.RELATIVE_TO_GROUND,
						color: color,
						colorBlendMode: Cesium.ColorBlendMode.MIX,
						distanceDisplayCondition: new Cesium.DistanceDisplayCondition(0.0,70000.0),
						allowPicking: false
					    }
					});
				}
			}
		}
		viewer.dataSources.add(datasource);
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
	fullscreenButton : false,
	scene3DOnly: true,
	showRenderLoopErrors: false
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
function stkterrain() {
	var cesiumTerrainProviderMeshes = new Cesium.CesiumTerrainProvider({
		url : 'https://assets.agi.com/stk-terrain/world',
		requestWaterMask : true,
		requestVertexNormals : true
	});
	viewer.terrainProvider = cesiumTerrainProviderMeshes;
}
function articterrain() {
	var cesiumTerrainProviderMeshesArtic = new Cesium.CesiumTerrainProvider({
		url : 'https://assets.agi.com/stk-terrain/v1/tilesets/ArticDEM/tiles',
		requestWaterMask : true,
		requestVertexNormals : true
	});
	viewer.terrainProvider = cesiumTerrainProviderMeshesArtic;
}
function ellipsoidterrain() {
	var ellipsoidProvider = new Cesium.EllipsoidTerrainProvider({
		requestWaterMask : true,
		requestVertexNormals : true
	});
	viewer.terrainProvider = ellipsoidProvider;
}
function vrtheworldterrain() {
	var vrTheWorldProvider = new Cesium.VRTheWorldTerrainProvider({
		url : 'http://www.vr-theworld.com/vr-theworld/tiles1.0.0/73/',
		requestWaterMask : true,
		requestVertexNormals : true,
		credit : 'Terrain data courtesy VT MÄK'
	});
	viewer.terrainProvider = vrTheWorldProvider;
}
if (MapTerrain == 'stk' || MapTerrain == '') {
	stkterrain();
} else if (MapTerrain == 'articdem') {
	articterrain();
} else if (MapTerrain == 'ellipsoid') {
	ellipsoidterrain();
} else if (MapTerrain == 'vrterrain') {
	vrtheworldterrain();
}

// Water effect

//viewer.scene.globe.oceanNormalMapUrl = 'js/Cesium/Assets/Textures/waterNormals.jpg';
viewer.scene.globe.oceanNormalMapUrl = 'images/shaders/water/water_new_height.png';
viewer.scene.globe.showWaterEffect = true;

// Lightning
viewer.scene.globe.enableLighting = true;


viewer.scene.globe.depthTestAgainstTerrain = true;
/*
// Cache
viewer.scene.globe.tileCacheSize = 1000;
*/
// Render size before rescale
if (getCookie('resolutionScale') != '') {
	viewer.resolutionScale = getCookie('resolutionScale');
}

//viewer.scene.globe.maximumScreenSpaceError = 1;

/*
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
viewer.scene.skyAtmosphere.brightnessShift = 0.4;
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

var currentposition;
viewer.camera.moveEnd.addEventListener(function() { 
<?php
	if (isset($globalMapUseBbox) && $globalMapUseBbox) {
?>
	update_locationsLayer();
<?php
	}
?>
	currentposition = viewer.camera.positionCartographic;
	createCookie('lastcentercoord',Cesium.Math.toDegrees(currentposition.latitude)+','+Cesium.Math.toDegrees(currentposition.longitude)+',8,'+currentposition.height,2);
});

var handler_all = new Cesium.ScreenSpaceEventHandler(viewer.canvas);
handler_all.setInputAction(function(click) {
	var pickedObject = viewer.scene.pick(click.position);
	if (Cesium.defined(pickedObject) && getCookie('show_Weather')) {
		delete_clouds();
		var cposition = pickedObject.id.position.getValue(viewer.clock.currentTime);
		create_clouds(viewer.scene.globe.ellipsoid.cartesianToCartographic(cposition));
	}
}, Cesium.ScreenSpaceEventType.LEFT_CLICK);

viewer.clock.onTick.addEventListener(function(clock) {
	if (getCookie('updaterealtime') == true || getCookie('updaterealtime') == '') {
		if (Cesium.defined(viewer.trackedEntity)) {
			var position = viewer.trackedEntity.position.getValue(clock.currentTime);
			var nexttime = Cesium.JulianDate.addSeconds(clock.currentTime,2,new Cesium.JulianDate());
			var positionn = viewer.trackedEntity.position.getValue(nexttime);
			if (Cesium.defined(position)) {
				var coord = viewer.scene.globe.ellipsoid.cartesianToCartographic(position);
				$(".latitude").html(Cesium.Math.toDegrees(coord.latitude).toFixed(5));
				$(".longitude").html(Cesium.Math.toDegrees(coord.longitude).toFixed(5));
				if (Cesium.defined(positionn)) {
					var ellipsoidGeodesic = new Cesium.EllipsoidGeodesic(Cesium.Cartographic.fromCartesian(position),Cesium.Cartographic.fromCartesian(positionn));
					var distance = ellipsoidGeodesic.surfaceDistance;
					var speedbox = document.getElementById("realspeed");
					if (speedbox != null) speedbox.style.visibility = "visible";
					if (unitspeed = 'kmh') {
						$(".realspeed").html(Math.round(distance/2*3.6)+' km/h');
					} else if (unitspeed = 'knots') {
						$(".realspeed").html(Math.round(distance/2*3.6*0,539957)+' knots');
					} else if (unitspeed = 'mph') {
						$(".realspeed").html(Math.round(distance/2*3.6*0,621371)+' mph');
					}
				}
				
				if (Cesium.defined(viewer.trackedEntity.orientation.getValue(clock.currentTime))) {
					var heading = Cesium.Math.toDegrees(Cesium.Quaternion.computeAngle(viewer.trackedEntity.orientation.getValue(clock.currentTime))).toFixed(0);
					$(".heading").html(heading);
					if (unitaltitude == 'm') {
						if (Cesium.defined(viewer.trackedEntity.properties) && Cesium.defined(viewer.trackedEntity.properties.type) && viewer.trackedEntity.properties.type == 'flight') {
							$(".altitude").html(Math.round(coord.height)+' m (FL'+Math.round(coord.height*3.28084/100)+')');
						} else {
							$(".altitude").html(Math.round(coord.height)+' m');
						}
					} else {
						if (Cesium.defined(viewer.trackedEntity.properties) && Cesium.defined(viewer.trackedEntity.properties.type) && viewer.trackedEntity.properties.type == 'flight') {
							$(".altitude").html(Math.round(coord.height*3.28084)+' feet (FL'+Math.round(coord.height*3.28084/100)+')');
						} else {
							$(".altitude").html(Math.round(coord.height*3.28084)+' feet');
						}
					}
				}
				try {
					var cartesian2 = new Cesium.Cartesian3.fromDegrees(Cesium.Math.toDegrees(coord.longitude),Cesium.Math.toDegrees(coord.latitude));
				} catch(e) { console.log(e); }
				try {
					var height = viewer.scene.globe.getHeight(Cesium.Ellipsoid.WGS84.cartesianToCartographic(cartesian2));
				} catch(e) { console.log(e); }
				if (typeof height != 'undefined') {
					if (unitaltitude == 'm') {
						$(".groundaltitude").html(Math.round(height)+' m');
					} else {
						$(".groundaltitude").html(Math.round(height*3.28084)+' feet');
					}
				}
			}
		}
	}
	if (getCookie('show_Weather')) {
		if (Cesium.defined(viewer.trackedEntity)) {
			if (typeof cloudscenter == 'undefined') {
				var cposition = viewer.trackedEntity.position.getValue(viewer.clock.currentTime);
				create_clouds(viewer.scene.globe.ellipsoid.cartesianToCartographic(cposition));
			} else {
				var cposition = viewer.trackedEntity.position.getValue(viewer.clock.currentTime);
				var ellipsoidGeodesic = new Cesium.EllipsoidGeodesic(Cesium.Cartographic.fromCartesian(cposition),cloudscenter);
				var distance = ellipsoidGeodesic.surfaceDistance;
				if (distance > 25000) {
					create_clouds(viewer.scene.globe.ellipsoid.cartesianToCartographic(cposition));
				}
			}
		} else {
			delete_clouds();
		}
	}
});
