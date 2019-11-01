/**
 * This javascript is part of FlightAirmap.
 *
 * Copyright (c) Ycarus (Yannick Chabanois) <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
/** global: Cesium */
/** global: viewer */
/** global: A */

function clickDisplayWeather(cb) {
    createCookie('show_Weather',cb.checked,2);
//    window.location.reload();
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

// Define how the particles will be updated
function snowParticleUpdateFunction (particle, dt) {
    var gravityScratch = new Cesium.Cartesian3();
    var position = particle.position;
    Cesium.Cartesian3.normalize(position, gravityScratch);
    var magnitude = Cesium.Math.randomBetween(-5500.0, -1500.0);
    Cesium.Cartesian3.multiplyByScalar(gravityScratch, magnitude * dt, gravityScratch);
    particle.velocity = Cesium.Cartesian3.add(particle.velocity, gravityScratch, particle.velocity);
}
var snowSystem;
function create_snow()  {
	if (Cesium.defined(snowSystem)) {
		viewer.scene.primitives.remove(snowSystem);
	}
	var minParticleSize = 20.0;
	var cposition = viewer.scene.camera.positionWC;
	snowSystem = new Cesium.ParticleSystem({
	    modelMatrix : new Cesium.Matrix4.fromTranslation(cposition),
	    minimumSpeed : -2.0,
	    maximumSpeed : 2.0,
	    lifeTime : 15.0,
	    emitter : new Cesium.SphereEmitter(100000.0),
	    startScale : 1.0,
	    endScale : 0.0,
	    startColor : Cesium.Color.WHITE.withAlpha(0.0),
	    endColor : Cesium.Color.WHITE.withAlpha(0.9),
	    minimumWidth : minParticleSize,
	    minimumHeight : minParticleSize,
	    maximumWidth : minParticleSize * 2.0,
	    maximumHeight : minParticleSize * 2.0,
	    forces : [snowParticleUpdateFunction],
	    image : 'images/weather/snowparticle.png',
	    rate : 2500.0
	});
	var addsnow = viewer.scene.primitives.add(snowSystem);
}

// Define how the particles will be updated
function rainParticleUpdateFunction (particle, dt) {
    var gravityScratch = new Cesium.Cartesian3();
    var position = particle.position;
    Cesium.Cartesian3.normalize(position, gravityScratch);
    var magnitude = Cesium.Math.randomBetween(-1500.0, -500.0);
    Cesium.Cartesian3.multiplyByScalar(gravityScratch, magnitude * dt, gravityScratch);
    particle.velocity = Cesium.Cartesian3.add(particle.velocity, gravityScratch, particle.velocity);
}
var rainSystem;
function create_rain()  {
	if (Cesium.defined(rainSystem)) {
		viewer.scene.primitives.remove(rainSystem);
	}
	var minParticleSize = 4.0;
	var cposition = viewer.scene.camera.positionWC;
	rainSystem = new Cesium.ParticleSystem({
	    modelMatrix : new Cesium.Matrix4.fromTranslation(cposition),
	    minimumSpeed : 1.0,
	    maximumSpeed : 4.0,
	    lifeTime : 15.0,
	    emitter : new Cesium.SphereEmitter(100000.0),
	    startScale : 1.0,
	    endScale : 0.0,
	    startColor : Cesium.Color.LIGHTSTEELBLUE.withAlpha(0.0),
	    endColor : Cesium.Color.LIGHTSTEELBLUE.withAlpha(0.9),
	    minimumWidth : minParticleSize,
	    minimumHeight : minParticleSize,
	    maximumWidth : minParticleSize * 2.0,
	    maximumHeight : minParticleSize * 2.0,
	    forces : [snowParticleUpdateFunction],
	    image : 'images/weather/waterdrop.png',
	    rate : 15000.0
	});
	var addrain = viewer.scene.primitives.add(rainSystem);
}
function create_clouds(cposition) {
	//console.log('Create clouds');
	cloudscenter = cposition;
	$.getJSON('/weather-json.php?latitude='+Cesium.Math.toDegrees(cposition.latitude)+'&longitude='+Cesium.Math.toDegrees(cposition.longitude),function(alldata) {
		console.log(alldata);
		var rain = alldata['rain'];
		if (typeof rain['rh'] != 'undefined' && rain['rh'] > 95) {
			if (rain['temp'] > 3) {
				console.log('Add rain');
				create_rain();
			} else {
				console.log('Add snow');
				create_snow();
			}
		}
		var data = alldata['clouds'];
		//delete_clouds();
		var coord = A.EclCoord.fromWgs84(Cesium.Math.toDegrees(cposition.latitude),Cesium.Math.toDegrees(cposition.longitude),0);
		var tp = A.Solar.topocentricPosition(new A.JulianDay(new Date(viewer.clock.currentTime.toString())),coord,true);
		var tpn = A.Solar.topocentricPosition(new A.JulianDay(new Date(Cesium.JulianDate.addSeconds(viewer.clock.currentTime,60,new Cesium.JulianDate()).toString())),coord,true);
		//console.log(tp.hz);
		//console.log(tpn.hz);
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
			/*
			var timecolors = [[100,100,100],[255,150,100],[255,255,255],[255,255,255],[255,255,255],[255,255,255],[255,150,100],[100,100,100],[100,100,100],[100,100,100],[100,100,100]];
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
			*/
			//var color = new Cesium.Color(rh/100,rh/100,rh/100,1);
			
			// 17:17 => az : 1.008 - alt : -0.021
			
			var prevcolor = [255,255,255];
			if (tp.hz.alt < 0) {
				prevcolor = [100,100,100];
			} else if (tp.hz.alt < 0.172) {
				prevcolor = [255,150,100];
			} else if (tp.hz.alt < Math.PI/2) {
				prevcolor = [255,255,255];
			} else if (tp.hz.alt < 2.9) {
				prevcolor = [255,255,255];
			} else if (tp.hz.alt < 3.0) {
				prevcolor = [255,150,100];
			} else {
				prevcolor = [100,100,100];
			}
			var nextcolor =  [255,255,255];
			if (tpn.hz.alt < 0) {
				nextcolor = [100,100,100];
			} else if (tpn.hz.alt < 0.172) {
				nextcolor = [255,150,100];
			} else if (tpn.hz.alt < Math.PI/2) {
				nextcolor = [255,255,255];
			} else if (tpn.hz.alt < 2.9) {
				nextcolor = [255,255,255];
			} else if (tpn.hz.alt < 3.0) {
				nextcolor = [255,150,100];
			} else {
				nextcolor = [100,100,100];
			}
			var timecolorsstep = chour/24*10;
			var currentcolor = getColor(prevcolor,nextcolor,3*60,(timecolorsstep%3)*60+cminute);
			var color = new Cesium.Color.multiply(new Cesium.Color(rh/100,rh/100,rh/100,1),new Cesium.Color.fromBytes(currentcolor['r'],currentcolor['v'],currentcolor['b'],155), new Cesium.Color());
			//var color = new Cesium.Color(rh/100,rh/100,rh/100,1);

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
				}
			}
			//console.log(data[i]);
			//console.log(cloud);
			if (typeof cloud != 'undefined') {
				//console.log('models');
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


var handler_all = new Cesium.ScreenSpaceEventHandler(viewer.canvas);
handler_all.setInputAction(function(click) {
	var pickedObject = viewer.scene.pick(click.position);
	if (Cesium.defined(pickedObject) && getCookie('show_Weather') == 'true') {
		delete_clouds();
		var cposition = pickedObject.id.position.getValue(viewer.clock.currentTime);
		create_clouds(viewer.scene.globe.ellipsoid.cartesianToCartographic(cposition));
	}
}, Cesium.ScreenSpaceEventType.LEFT_CLICK);


viewer.clock.onTick.addEventListener(function(clock) {
	if (getCookie('show_Weather') == 'true') {
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

if (getCookie('weather_fire') == 'true') loadFire(getCookie('weather_fire'));
var fireLayer;
function clickFire(cb) {
    loadFire(cb.checked);
}
function loadFire(val) {
    var fire = getCookie('weather_fire');
    if (fire == 'true' && val != 'true') {
	viewer.imageryLayers.remove(fireLayer,true);
	delCookie('weather_fire');
    } else {
	createCookie('weather_fire',val,999);
	var fireProvider = new Cesium.WebMapServiceImageryProvider({
	    url : corsproxy+'https://firms.modaps.eosdis.nasa.gov/wms/map/',
	    layers : 'NASA FIRMS',
	    parameters : {
	    transparent : true,
	    format : 'image/png',
	    time: 24,
	    instr: 'modis',
	    sat: 'T'
	    }
	});
	fireLayer = new Cesium.ImageryLayer(fireProvider);
	viewer.imageryLayers.add(fireLayer);
    }
}