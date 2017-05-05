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
			console.log('id : '+data.ref);
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
		//console.log(entity.position);
		//var positionCartographic = Cesium.Ellipsoid.WGS84.cartesianToCartographic(entity.position);
		//console.log(positionCartographic.height.toFixed(2));

		var orientation = new Cesium.VelocityOrientationProperty(entity.position)
		entity.orientation = orientation;
		//var hpRoll = new Cesium.HeadingPitchRoll();
		//entity.modelMatrix = Cesium.Transforms.aircraftHeadingPitchRollToFixedFrame(entity.position,hpRoll);
		//console.log(entity);

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
  //  console.log('Update Data');
//    var geojsonSource = new Cesium.GeoJsonDataSource("geojson");
//    var dataSource = geojsonSource.load('/live/geojson');
//    var dataSource = new Cesium.CzmlDataSource.load('/live-czml.php');
//     var czmlds = new Cesium.CzmlDataSource();
	var livedata = czmlds.process('<?php print $globalURL; ?>/live-czml.php?' + Date.now()+'&bbox='+bbox());
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

function updateSanta() {
	var livesantadata = czmldssanta.process('<?php print $globalURL; ?>/live-santa-czml.php?' + Date.now());
	livesantadata.then(function (data) {
		console.log('Add santa !');
		displayDataSanta(data);
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

function showNotam() {
    if (!$("#notam").hasClass("active"))
    {
	$("#notam").addClass("active");
	console.log('add NOTAM');
	addNOTAM();
    } else {
	console.log('remove NOTAM');
	$("#notam").removeClass("active");
	deleteNOTAM();
    }
}
function showAirspace() {
    if (!$("#airspace").hasClass("active"))
    {
	$("#airspace").addClass("active");
	console.log('add Airspace');
	addAirspace();
    } else {
	console.log('remove Airspace');
	$("#airspace").removeClass("active");
	deleteAirspace();
    }
}
function showWaypoints() {
    if (!$("#waypoints").hasClass("active"))
    {
	$("#waypoints").addClass("active");
	console.log('add Waypoints');
	addWaypoints();
    } else {
	console.log('remove Waypoints');
	$("#waypoints").removeClass("active");
	deleteWaypoints();
    }
}
function notamscope(selectObj) {
    var idx = selectObj.selectedIndex;
    var scope = selectObj.options[idx].value;
    document.cookie = 'notamscope='+scope+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    if ($("#notam").hasClass("active")) {
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
		
//var handler = new Cesium.ScreenSpaceEventHandler(viewer.scene.canvas);
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
		} else if (pickedObject.id.type == 'atc') {
			$(".showdetails").load("<?php print $globalURL; ?>/atc-data.php?"+Math.random()+"&atcid="+encodeURI(pickedObject.id.ref)+"&atcident="+encodeURI(pickedObject.id.ident));
		} else if (pickedObject.id.type == 'notam') {
			$(".showdetails").load("<?php print $globalURL; ?>/notam-data.php?"+Math.random()+"&notam="+encodeURI(pickedObject.id.id));
		} else if (pickedObject.id.type == 'airspace') {
			$(".showdetails").load("<?php print $globalURL; ?>/airspace-data.php?"+Math.random()+"&airspace="+encodeURI(pickedObject.id.id));
//		} else if (pickedObject.id.name == 'iss') {
//			$(".showdetails").load("<?php print $globalURL; ?>/space-data.php?"+Math.random()+"&currenttime="+Date.parse(currenttime.toString()));
//		} else if (pickedObject.id.id == 'ISS (ZARYA)') {
//			$(".showdetails").load("<?php print $globalURL; ?>/space-data.php?"+Math.random()+"&currenttime="+Date.parse(currenttime.toString()));
//		} else if (typeof pickedObject.id.properties.icao != 'undefined') {
		} else if (pickedObject.id.type == 'airport') {
			var icao = pickedObject.id.properties.icao;
			$(".showdetails").load("<?php print $globalURL; ?>/airport-data.php?"+Math.random()+"&airport_icao="+icao);
		} else if (pickedObject.id.id == 'santaclaus') {
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
	}
}, Cesium.ScreenSpaceEventType.LEFT_CLICK);
camera.moveEnd.addEventListener(function() {
	if ($("#notam").hasClass("active"))
	{
		addNOTAM();
	}
	if ($("#airspace").hasClass("active"))
	{
		addAirspace();
	}
	if ($("#waypoints").hasClass("active"))
	{
		addWaypoints();
	}
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

if (getCookie('displayairports') == 'true') {
	update_airportsLayer();
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
    document.cookie =  'IconColor='+color.substring(1)+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    if (getCookie('IconColorForce') == 'true') window.location.reload();
}
function iconColorForce(val) {
    document.cookie =  'IconColorForce='+val.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    if (getCookie('IconColor') != '') document.cookie =  'IconColor=ff0000; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
}

