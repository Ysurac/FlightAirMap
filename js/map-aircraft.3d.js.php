<?php
	require_once('../require/settings.php');
	require_once('../require/class.Language.php'); 
?>

function clickSanta(cb) {
	if (cb.checked) {
		czmldssanta = new Cesium.CzmlDataSource();
		var livesantadata = czmldssanta.process('<?php print $globalURL; ?>/live-santa-czml.php?now&' + Date.now());
		livesantadata.then(function (data) {
			console.log('Add santa !');
			displayDataSanta(data);
			viewer.trackedEntity = ds.entities.getById('santaclaus');
		});
	} else {
		var dsn;
			for (var i =0; i < viewer.dataSources.length; i++) {
				if (viewer.dataSources.get(i).name == 'famsanta') {
					dsn = i;
					break;
				}
			}
		viewer.dataSources.remove(viewer.dataSources.get(dsn),true);
	}
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
//			billboard.distanceDisplayCondition = new DistanceDisplayCondition(0.0,7000.0);
			data.entities.values[i].billboard = billboard;
			data.entities.values[i].addProperty('type');
			data.entities.values[i].type = 'airport';
		}
		viewer.dataSources.add(data);
	});
}

function update_atcLayer() {
	var atcnb;
	for (var i =0; i < viewer.dataSources.length; i++) {
		if (viewer.dataSources.get(i).name == 'atc') {
			atcnb = i;
			break;
		}
	}

	var atc_geojson = Cesium.loadJson("<?php print $globalURL; ?>/atc-geojson.php");
	atc_geojson.then(function(geojsondata) {
		atc = new Cesium.CustomDataSource('atc');
		for (var i =0;i < geojsondata.features.length; i++) {
			data = geojsondata.features[i].properties;
			//console.log('id : '+data.ref);
			if (data.atc_range > 0) {
				var entity = atc.entities.add({
					ref: data.ref,
					ident: data.ident,
					position: Cesium.Cartesian3.fromDegrees(geojsondata.features[i].geometry.coordinates[0],geojsondata.features[i].geometry.coordinates[1]),
					ellipse : {
						semiMinorAxis : data.atc_range,
						semiMajorAxis : data.atc_range,
						rotation : Cesium.Math.toRadians(30.0),
						material : Cesium.Color.fromCssColorString(data.atccolor).withAlpha(0.5)
					},
					type: 'atc'
				});
			} else {
				var entity = atc.entities.add({
					ref: data.ref,
					ident: data.ident,
					position: Cesium.Cartesian3.fromDegrees(geojsondata.features[i].geometry.coordinates[0],geojsondata.features[i].geometry.coordinates[1]),
					billboard: {
						image: data.icon,
						verticalOrigin: Cesium.VerticalOrigin.BOTTOM
					},
					type: 'atc'
				});
			}
		}
		if (typeof atcnb != 'undefined') var remove = viewer.dataSources.remove(viewer.dataSources.get(atcnb));
		viewer.dataSources.add(atc);
	});
}

$(".showdetails").on("click",".close",function(){
	$(".showdetails").empty();
	$("#aircraft_ident").attr('class','');
	//getLiveData(1);
	return false;
})

var previoustexture;
function changeLiveries(primitive) {
	if (primitive instanceof Cesium.Model) {
		//console.log(primitive);
		//if (typeof primitive.gltf.images != 'undefined') {
		//}
		//console.log(primitive._rendererResources.textures);
		//var imagePath = '/models/gltf2/livreries/A320-AFR.png';
		if (primitive.id.properties.gltf2 && typeof primitive.id.properties.liveries != 'undefined') {
			//console.log(primitive);
			//primitive.cacheKey = primitive.id.id;
			//console.log(primitive);
			console.log('load liveries: '+primitive.id.properties.liveries+' for ident: '+primitive.id.properties.ident.toString());
			var imagePath = primitive.id.properties.liveries.toString();
			previoustexture = texture;
			try {
				var texture = primitive._rendererResources.textures[0];
				Cesium.loadImage(imagePath).then(function(imageData) {
					texture.copyFrom(imageData);
					texture.generateMipmap(); // Also replaces textures in mipmap
				}).otherwise(function(e) {
					console.log(e);
				});
			} catch(e) { console.log(e); }
		} else {
			console.log('No liveries available');
		}
	}
}
function resetLiveries(primitive) {
	if (typeof previoustexture != 'undefined') {
		if (primitive instanceof Cesium.Model) {
			var texture = primitive._rendererResources.textures[0];
			texture.copyFrom(previoustexture);
			texture.generateMipmap(); // Also replaces textures in mipmap
		} else {
			for (var k = 0; k < viewer.scene.primitives.length; k++) {
				if (viewer.scene.primitives[k].cacheKey = primitive.cacheKey) {
				var texture = viewer.scene.primitives[k]._rendererResources.textures[0];
				texture.copyFrom(previoustexture);
				texture.generateMipmap(); // Also replaces textures in mipmap
				break;
				}
			}
		}
	}
}


var lastupdate;
function displayData(data) {
	var flightcnt = 0;
	var dsn;
	for (var i =0; i < viewer.dataSources.length; i++) {
		if (viewer.dataSources.get(i).name == 'fam') {
			dsn = i;
			break;
		}
	}
	if (typeof dsn != 'undefined') {
		//if (Cesium.JulianDate.greaterThan(viewer.clock.currentTime,data.clock.currentTime)) {
			data.clock.currenTime = viewer.clock.currentTime;
		//}
	}
	var entities = data.entities.values;
	for (var i = 0; i < entities.length; i++) {
		var fromground = 0;
		var entity = entities[i];
		
		flightcnt = entity.properties.flightcnt;
		if (typeof dsn != 'undefined') var existing = viewer.dataSources.get(dsn);
		else var existing;
		
		//	var billboard = new Cesium.BillboardGraphics();
		//	var iconURLpath = '/getImages.php?color=FF0000&resize=15&filename='+aircraft_shadow+'&heading='+heading;
		//	entity.point = undefined;
		//	billboard.image = iconURLpath;
		//	entity.billboard = billboard;
		//	entity.billboard = undefined;
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
				} catch(e) { console.log(e); }
				try {
					var height = viewer.scene.globe.getHeight(Cesium.Ellipsoid.WGS84.cartesianToCartographic(cartesian2));
					//console.log(entity.id+' height: '+height);
				} catch(e) { console.log(e); }
				/*
				var cartographicPosition = Cesium.Ellipsoid.WGS84.cartesianToCartographic(cartesian2);
				var height = 100;
				//var promise = Cesium.sampleTerrain(viewer.terrainProvider, 9, cartographicPosition);
				//Cesium.then(promise, function(result) {
				Cesium.sampleTerrain(viewer.terrainProvider, 9, cartographicPosition).then(function(result) {
					console.log('Then ?');
					height = result.height;
				});
				console.log("height:");
				console.log(height);
				console.log('carto: ');
				console.log(cartographic.height);
				*/
				if (!Cesium.defined(height)) height = cartographic.height;
				if (fromground != 0 || cartographic.height < height) {
					if (fromground == 0 || height < fromground) fromground = height;
					var finalHeight = cartographic.height+fromground;
				} else var finalHeight = cartographic.height;
			} else var finalHeight = cartographic.height;
			positionArray.push(new Cesium.Cartesian3.fromDegrees(Cesium.Math.toDegrees(cartographic.longitude),Cesium.Math.toDegrees(cartographic.latitude), finalHeight));
			timeArray.push(times[k]);
		}

		var newPosition = new Cesium.SampledPositionProperty();
		try {
			newPosition.addSamples(timeArray, positionArray);
		} catch(e) { console.log(e); }
		entity.position = newPosition;

		var orientation = new Cesium.VelocityOrientationProperty(entity.position)
		entity.orientation = orientation;
		
		//var hpRoll = new Cesium.HeadingPitchRoll();
		//entity.modelMatrix = Cesium.Transforms.aircraftHeadingPitchRollToFixedFrame(entity.position,hpRoll);
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
			    // Remove flights not in latest CZML
			?>
			if (lastupdateentity != lastupdate) {
				viewer.dataSources.get(dsn).entities.remove(entity);
				czmlds.entities.removeById(entityid);
			}
			<?php
			    } else {
			?>
			if (parseInt(lastupdateentity) < Math.floor(Date.now()-<?php if (isset($globalMapRefresh)) print $globalMapRefresh*2000; else print '60000'; ?>)) {
				viewer.dataSources.get(dsn).entities.remove(entity);
				czmlds.entities.removeById(entityid);
			}
			<?php
			    }
			?>
		}
	}
	
	/*
	// Add liveries to model if available
	var primitives = viewer.scene.primitives;
	for (var i = 0; i < primitives.length; i++) {
		var primitive = primitives.get(i);
		changeLireries(primitive);
	}
	*/
	
	
	var singleflight = getCookie('singlemodel');
	var MapTrack = getCookie('MapTrack');
	if (MapTrack != '') {
		viewer.trackedEntity = viewer.dataSources.get(dsn).entities.getById(MapTrack);
		//viewer.selectedEntity = viewer.dataSources.get(dsn).entities.getById(MapTrack);
		var currenttime = viewer.clock.currentTime;
		$(".showdetails").load("<?php print $globalURL; ?>/aircraft-data.php?"+Math.random()+"&flightaware_id="+MapTrack+"&currenttime="+Date.parse(currenttime.toString()));
		$("#aircraft_ident").attr('class',MapTrack);
	}
	var flightvisible = viewer.dataSources.get(dsn).entities.values.length;
	if (flightcnt != 0 && flightcnt != flightvisible && flightcnt > flightvisible) {
		$("#ibxaircraft").html('<h4><?php echo _("Aircraft detected"); ?></h4><br /><b>'+flightvisible+'/'+flightcnt+'</b>');
	} else {
		$("#ibxaircraft").html('<h4><?php echo _("Aircraft detected"); ?></h4><br /><b>'+flightvisible+'</b>');
	}
};

function displayDataSanta(data) {
	var entities = data.entities.values;
	for (var i = 0; i < entities.length; i++) {
		var entity = entities[i];
		var orientation = new Cesium.VelocityOrientationProperty(entity.position)
		entity.orientation = orientation;
	}
	viewer.dataSources.add(data);
	dsn = viewer.dataSources.indexOf(data);
};

function updateData() {
	lastupdate = Date.now();
	// Process is used instead of load because flight didn't move smoothy with load
<?php
	if (isset($globalMapUseBbox) && $globalMapUseBbox) {
?>
	var livedata = czmlds.process('<?php print $globalURL; ?>/live-czml.php?update=' + lastupdate+'&coord='+bbox());
<?php
	} else {
?>
	var livedata = czmlds.process('<?php print $globalURL; ?>/live-czml.php?' + Date.now());
<?php
	}
?>
	livedata.then(function (data) { 
		displayData(data);
	});
}

function updateSanta() {
	var livesantadata = czmldssanta.process('<?php print $globalURL; ?>/live-santa-czml.php?' + Date.now());
	livesantadata.then(function (data) {
		console.log('Add santa !');
		displayDataSanta(data);
	});
}

function showNotam(cb) {
	document.cookie =  'notam='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	if (cb.checked == true) {
		addNOTAM();
	} else {
		deleteNOTAM();
	}
}
function showAirspace(cb) {
	document.cookie =  'airspace='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	if (cb.checked == true) {
		addAirspace();
	} else {
		deleteAirspace();
	}
}
function showWaypoints(cb) {
	document.cookie =  'waypoints='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	if (cb.checked == true) {
		addWaypoints();
	} else {
		deleteWaypoints();
	}
}
function notamscope(selectObj) {
	var idx = selectObj.selectedIndex;
	var scope = selectObj.options[idx].value;
	document.cookie = 'notamscope='+scope+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
	if (getCookie("notam") == 'true')
	{
		deleteNOTAM();
		addNOTAM();
	}
}

var notams;
function addNOTAM() {
	var bbox_value = bbox();
	//console.log('Download NOTAM...');
	if (bbox_value != '') {
		if (getCookie('notamscope') == '' || getCookie('notamscope') == 'All') {
			url = "<?php print $globalURL; ?>/notam-geojson.php?coord="+bbox_value;
		} else {
			url = "<?php print $globalURL; ?>/notam-geojson.php?scope="+getCookie('notamscope')+"&coord="+bbox_value;
		}
		var notamdata = Cesium.loadJson(url);
		notamdata.then(function (geojsondata) {
			deleteNOTAM();
			//console.log(geojsondata.features);
			notams = new Cesium.CustomDataSource('notam');
			for (var i = 0; i < geojsondata.features.length; i++) {
				data = geojsondata.features[i].properties;
				//console.log(data);
				if (data.radius > 0) {
					var clength = Math.round((data.upper_limit-data.lower_limit)*100*0.3048);
					if (clength == 0) clength = 1;
					var mediumalt = Math.round(((data.upper_limit-data.lower_limit)*100*0.3048)/2);
					var radius = Math.round(data.radius*1852);
					if (radius > 40000) radius = 40000;
					var entity = notams.entities.add({
						id: data.ref,
						position: Cesium.Cartesian3.fromDegrees(data.longitude,data.latitude,mediumalt),
						cylinder : {
							length : clength,
							topRadius : radius,
							bottomRadius : radius,
							material : Cesium.Color.fromCssColorString(data.color).withAlpha(0.5)
						},
						type: 'notam'
					});
//					entity.addProperty('type');
//					entity.type = 'notam';
				}
			}
			viewer.dataSources.add(notams);
		});
	}
}

function deleteNOTAM() {
//	var dsn;
//	for (var i =0; i < viewer.dataSources.length; i++) {
//		if (viewer.dataSources.get(i).name == 'notam') {
//			dsn = i;
//			break;
//		}
//	}
//	viewer.dataSources.remove(viewer.dataSources.get(dsn));
	viewer.dataSources.remove(notams,true);
}

var airspace;
function addAirspace() {
	var bbox_value = bbox();
	//console.log('Download Airspace...');
	if (bbox_value != '') {
		if (getCookie('airspacecope') == '' || getCookie('airspacescope') == 'All') {
			url = "<?php print $globalURL; ?>/airspace-geojson.php?coord="+bbox_value;
		} else {
			url = "<?php print $globalURL; ?>/airspace-geojson.php?scope="+getCookie('airspacecope')+"&coord="+bbox_value;
		}
		var airspacedata = Cesium.loadJson(url);
		airspacedata.then(function (geojsondata) {
			deleteAirspace();
			airspace = new Cesium.CustomDataSource('airspace');
			for (var i = 0; i < geojsondata.features.length; i++) {
				data = geojsondata.features[i].properties;
				if (typeof data.upper_limit != 'undefined' && typeof data.lower_limit != 'undefined') {
					var position = [];
					for (j = 0; j < geojsondata.features[i].geometry.coordinates[0].length; j++) {
						//position.push(geojsondata.features[i].geometry.coordinates[0][j][0],geojsondata.features[i].geometry.coordinates[0][j][1],0);
						position.push(geojsondata.features[i].geometry.coordinates[0][j][0],geojsondata.features[i].geometry.coordinates[0][j][1]);
					}
					if (position.length > 3) {
						var entity = airspace.entities.add({
							id: data.id,
							polygon : {
								//hierarchy : new Cesium.PolygonHierarchy(Cesium.Cartesian3.fromDegreesArrayHeights(position)),
								hierarchy : new Cesium.PolygonHierarchy(Cesium.Cartesian3.fromDegreesArray(position)),
								height : data.upper_limit,
								extrudedHeight : data.lower_limit,
								//material : { solidColor : { color : { rgba : [255, 100, 0, 100] } } }
								material : Cesium.Color.fromCssColorString(data.color).withAlpha(0.2)
							},
							type: 'airspace'
						});
//						entity.addProperty('type');
//						entity.type = 'notam';
						//console.log(entity);
					}
				}
			}
			viewer.dataSources.add(airspace);
		});
	}
}
function deleteAirspace() {
	viewer.dataSources.remove(airspace,true);
}

var waypoints;
function addWaypoints() {
	var bbox_value = bbox();
	//console.log('Download Airspace...');
	if (bbox_value != '') {
		url = "<?php print $globalURL; ?>/waypoints-geojson.php?coord="+bbox_value;
		waypoints = new Cesium.GeoJsonDataSource('waypoints');
		waypoints.load(url);
		deleteWaypoints();
		viewer.dataSources.add(waypoints);
		
		/*
		var waypointsdata = Cesium.loadJson(url);
		waypointsdata.then(function (geojsondata) {
			deleteWaypoints();
			waypoints = new Cesium.CustomDataSource('waypoints');
			for (var i = 0; i < geojsondata.features.length; i++) {
				data = geojsondata.features[i].properties;
				if (typeof data.upper_limit != 'undefined' && typeof data.lower_limit != 'undefined') {
					var position = [];
					for (j = 0; j < geojsondata.features[i].geometry.coordinates[0].length; j++) {
						//position.push(geojsondata.features[i].geometry.coordinates[0][j][0],geojsondata.features[i].geometry.coordinates[0][j][1],0);
						position.push(geojsondata.features[i].geometry.coordinates[0][j][0],geojsondata.features[i].geometry.coordinates[0][j][1]);
					}
					if (position.length > 3) {
						var entity = airspace.entities.add({
							id: data.id,
							polygon : {
								//hierarchy : new Cesium.PolygonHierarchy(Cesium.Cartesian3.fromDegreesArrayHeights(position)),
								hierarchy : new Cesium.PolygonHierarchy(Cesium.Cartesian3.fromDegreesArray(position)),
								height : data.upper_limit,
								extrudedHeight : data.lower_limit,
								//material : { solidColor : { color : { rgba : [255, 100, 0, 100] } } }
								material : Cesium.Color.fromCssColorString(data.color).withAlpha(0.5)
							},
							type: 'waypoints'
						});
//						entity.addProperty('type');
//						entity.type = 'notam';
						//console.log(entity);
					}
				}
			}
			viewer.dataSources.add(waypoints);
		});
		*/
	}
}
function deleteWaypoints() {
	var dsn;
	for (var i =0; i < viewer.dataSources.length; i++) {
		if (viewer.dataSources.get(i).name == 'waypoints-geojson.php') {
			dsn = i;
			break;
		}
	}
	viewer.dataSources.remove(viewer.dataSources.get(dsn),true);
//	viewer.dataSources.remove(waypoints);
}

var czmlds = new Cesium.CzmlDataSource();
<?php
		if (!isset($globalMapUseBbox) || !$globalMapUseBbox) {
?>
Cesium.when(viewer.terrainProvider.ready,function() {updateData(); });
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
		
var lastpick;
var handler_aircraft = new Cesium.ScreenSpaceEventHandler(viewer.scene.canvas);
handler_aircraft.setInputAction(function(click) {
	var pickedObject = viewer.scene.pick(click.position);
	if (Cesium.defined(pickedObject)) {
		var currenttime = viewer.clock.currentTime;
		if (typeof pickedObject.id.properties != 'undefined') {
			var type = pickedObject.id.properties.valueOf('type')._type._value
		}
		if (typeof type == 'undefined') {
			var type = pickedObject.id.type;
		}
		//console.log(pickedObject.id.position.getValue(viewer.clock.currentTime));
//		console.log(pickedObject.id);
//		if (typeof pickedObject.id.lastupdate != 'undefined') {
		delCookie('MapTrack');
		if (type == 'flight') {
			flightaware_id = pickedObject.id.id;
			createCookie('MapTrack',flightaware_id,1);
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
				if (typeof plast != 'undefined') {
					plast.path.show = false;
				}
			}
			var pnew = viewer.dataSources.get(dsn).entities.getById(flightaware_id);
			pnew.path.show = true;
			$("#aircraft_ident").attr('class',flightaware_id);
			//lastid = flightaware_id;
			<?php
				if (isset($globalMap3DLiveries) && $globalMap3DLiveries) {
			?>
			if (getCookie('UseLiveries') == 'true') {
				if (typeof lastpick != 'undefined') resetLiveries(lastpick.primitive);
				changeLiveries(pickedObject.primitive);
				lastpick = pickedObject;
			}
			<?php
				}
			?>
		} else if (type == 'atc') {
			$(".showdetails").load("<?php print $globalURL; ?>/atc-data.php?"+Math.random()+"&atcid="+encodeURI(pickedObject.id.ref)+"&atcident="+encodeURI(pickedObject.id.ident));
		} else if (type == 'notam') {
			$(".showdetails").load("<?php print $globalURL; ?>/notam-data.php?"+Math.random()+"&notam="+encodeURI(pickedObject.id.id));
		} else if (type == 'loc') {
			$(".showdetails").load("<?php print $globalURL; ?>/location-data.php?"+Math.random()+"&sourceid="+encodeURI(pickedObject.id.id));
		} else if (type == 'airspace') {
			$(".showdetails").load("<?php print $globalURL; ?>/airspace-data.php?"+Math.random()+"&airspace="+encodeURI(pickedObject.id.id));
//		} else if (pickedObject.id.name == 'iss') {
//			$(".showdetails").load("<?php print $globalURL; ?>/space-data.php?"+Math.random()+"&currenttime="+Date.parse(currenttime.toString()));
//		} else if (pickedObject.id.id == 'ISS (ZARYA)') {
//			$(".showdetails").load("<?php print $globalURL; ?>/space-data.php?"+Math.random()+"&currenttime="+Date.parse(currenttime.toString()));
//		} else if (typeof pickedObject.id.properties.icao != 'undefined') {
		} else if (type == 'airport') {
			var icao = pickedObject.id.properties.icao;
			$(".showdetails").load("<?php print $globalURL; ?>/airport-data.php?"+Math.random()+"&airport_icao="+icao);
		} else if (pickedObject.id == 'santaclaus') {
			console.log('santa');
			$(".showdetails").load("<?php print $globalURL; ?>/space-data.php?"+Math.random()+"&currenttime="+Date.parse(currenttime.toString())+"&sat="+encodeURI(pickedObject.id.id));
			var dsn;
			for (var i =0; i < viewer.dataSources.length; i++) {
				if (viewer.dataSources.get(i).name == 'famsanta') {
					dsn = i;
					break;
				}
			}
			console.log('dsn : '+dsn);
			var pnew = viewer.dataSources.get(dsn).entities.getById(pickedObject.id.id);
			pnew.path.show = true;
		}
	} else {
		delCookie('MapTrack');
	}
}, Cesium.ScreenSpaceEventType.LEFT_CLICK);
camera.moveEnd.addEventListener(function() {
	if (getCookie("notam") == 'true')
	{
		addNOTAM();
	}
	if (getCookie("airspace") == 'true')
	{
		addAirspace();
	}
	if (getCookie("waypoints") == 'true')
	{
		addWaypoints();
	}
<?php
	if (isset($globalMapUseBbox) && $globalMapUseBbox) {
?>
	console.log("Camera move...");
	updateData();
<?php
	}
?>
});

//var reloadpage = setInterval(function() { updateData(); },30000);
if (archive == false) {
	var czmldssanta;
	if (Cesium.JulianDate.greaterThanOrEquals(viewer.clock.currentTime,Cesium.JulianDate.fromIso8601('<?php echo date("Y"); ?>-12-24T02:00Z')) && Cesium.JulianDate.lessThan(viewer.clock.currentTime,Cesium.JulianDate.fromIso8601('<?php echo date("Y"); ?>-12-25T02:00Z'))) {
		czmldssanta = new Cesium.CzmlDataSource();
		updateSanta();
	}
	var reloadpage = setInterval(
		function(){
			console.log('Reload...');
			updateData();
			if (typeof czmldssanta == 'undefined') {
				if (Cesium.JulianDate.greaterThanOrEquals(viewer.clock.currentTime,Cesium.JulianDate.fromIso8601('<?php echo date("Y"); ?>-12-24T02:00Z')) && Cesium.JulianDate.lessThan(viewer.clock.currentTime,Cesium.JulianDate.fromIso8601('<?php echo date("Y"); ?>-12-25T02:00Z'))) {
					czmldssanta = new Cesium.CzmlDataSource();
					updateSanta();
				}
			}
		}
	,<?php if (isset($globalMapRefresh)) print $globalMapRefresh*1000; else print '30000'; ?>);
} else {
	//var widget = new Cesium.CesiumWidget('archivebox');
//	var timeline = new Cesium.Timeline(viewer);
	var clockViewModel = new Cesium.ClockViewModel(viewer.clock);
	var animationViewModel = new Cesium.AnimationViewModel(clockViewModel);
	//this._div.innerHTML = '<h4><?php echo str_replace("'","\'",_("Archive Date & Time")); ?></h4>' +  '<b>' + props.archive_date + ' UTC </b>' + '<br/><i class="fa fa-fast-backward" aria-hidden="true"></i> <i class="fa fa-backward" aria-hidden="true"></i>  <a href="#" onClick="archivePause();"><i class="fa fa-pause" aria-hidden="true"></i></a> <a href="#" onClick="archivePlay();"><i class="fa fa-play" aria-hidden="true"></i></a>  <i class="fa fa-forward" aria-hidden="true"></i> <i class="fa fa-fast-forward" aria-hidden="true"></i>';
	$(".archivebox").html('<h4><?php echo str_replace("'","\'",_("Archive")); ?></h4>' + '<br/><form id="noarchive" method="post"><input type="hidden" name="noarchive" /></form><a href="#" onClick="animationViewModel.playReverseViewModel.command();"><i class="fa fa-play fa-flip-horizontal" aria-hidden="true"></i></a> <a href="#" onClick="'+"document.getElementById('noarchive').submit();"+'"><i class="fa fa-eject" aria-hidden="true"></i></a> <a href="#" onClick="animationViewModel.pauseViewModel.command();"><i class="fa fa-pause" aria-hidden="true"></i></a> <a href="#" onClick="animationViewModel.playForwardViewModel.command();"><i class="fa fa-play" aria-hidden="true"></i></a>');
	//		this._div.innerHTML = '<h4><?php echo str_replace("'","\'",_("Archive Date & Time")); ?></h4>' +  '<b><i class="fa fa-spinner fa-pulse fa-2x fa-fw margin-bottom"></i></b>';

}

if (getCookie('displayairports') == 'true') 
{
	update_airportsLayer();
}
if (getCookie("notam") == 'true')
{
	addNOTAM();
}
if (getCookie("airspace") == 'true')
{
	addAirspace();
}
if (getCookie("waypoints") == 'true')
{
	addWaypoints();
}

<?php
    if ((isset($globalIVAO) && $globalIVAO) || (isset($globalVATSIM) && $globalVATSIM)) {
?>
update_atcLayer();
setInterval(function(){update_atcLayer()},<?php if (isset($globalMapRefresh)) print $globalMapRefresh*1000*2; else print '60000'; ?>);
<?php
    }
?>

function iconColor(color) {
    document.cookie =  'IconColor='+color.substring(1)+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/';
    if (getCookie('IconColorForce') == 'true') window.location.reload();
}
function iconColorForce(val) {
    document.cookie =  'IconColorForce='+val.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/';
    if (getCookie('IconColor') != '') document.cookie =  'IconColor=ff0000; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/';
}
function useLiveries(val) {
    document.cookie =  'UseLiveries='+val.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/';
}
function useOne3Dmodel(val) {
    document.cookie =  'one3dmodel='+val.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/';
    window.location.reload();
}

