/**
 * This javascript is part of FlightAirmap.
 *
 * Copyright (c) Ycarus (Yannick Chabanois) <support@flightairmap.com>
 * Licensed under AGPL license.
 * For more information see: https://www.flightairmap.com/
*/
"use strict";
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
    document.cookie = cname + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/';
}

function createCookie(name, value, days) {
    var date, expires;
    if (days) {
	date = new Date();
	date.setTime(date.getTime()+(days*24*60*60*1000));
	expires = "; expires="+date.toGMTString();
    } else {
	expires = "";
    }
    document.cookie = name+"="+value+expires+"; path=/";
}

function dynamicSort(property) {
    var sortOrder = 1;
    if(property[0] === "-") {
        sortOrder = -1;
        property = property.substr(1);
    }
    return function (a,b) {
        var result = (a[property] < b[property]) ? -1 : (a[property] > b[property]) ? 1 : 0;
        return result * sortOrder;
    }
}

function dynamicSortMultiple() {
    var props = arguments;
    return function (obj1, obj2) {
	var i = 0, result = 0, numberOfProperties = props.length;
	while(result === 0 && i < numberOfProperties) {
	    result = dynamicSort(props[i])(obj1, obj2);
	    i++;
	}
	return result;
    }
}

function map2dlimit(limit) {
    createCookie('map_2d_limit',limit,9999);
}
function map3dlimit(limit) {
    createCookie('map_3d_limit',limit,9999);
}

function mapType(selectObj) {
    var idx = selectObj.selectedIndex;
    var atype = selectObj.options[idx].value;
    var type = atype.split('-');
    if (type[0] == 'Mapbox') {
	createCookie('MapType',type[0],9999);
	createCookie('MapTypeId',type[1],9999);
	if (getCookie('Map2D3DSync')) {
	    createCookie('MapType3D',type[0],9999);
	    createCookie('MapType3DId',type[1],9999);
	}
    } else {
	createCookie('MapType',atype,9999);
	if (getCookie('Map2D3DSync')) {
	    createCookie('MapType3D',atype,9999);
	}
    }
    window.location.reload();
}
function mapType3D(selectObj) {
    var idx = selectObj.selectedIndex;
    var atype = selectObj.options[idx].value;
    var type = atype.split('-');
    if (type[0] == 'Mapbox') {
	createCookie('MapType3D',type[0],9999);
	createCookie('MapType3DId',type[1],9999);
	if (getCookie('Map2D3DSync')) {
	    createCookie('MapType',type[0],9999);
	    createCookie('MapTypeId',type[1],9999);
	}
    } else {
	createCookie('MapType3D',atype,9999);
	if (getCookie('Map2D3DSync')) {
	    createCookie('MapType',atype,9999);
	}
    }
    window.location.reload();
}
function clickSyncMap2D3D(cb) {
    createCookie('Map2D3DSync',cb.checked,9999);
    if (cb.checked) {
	createCookie('MapType3D',getCookie('MapType'),9999);
	createCookie('MapType3DId',getCookie('MapTypeId'),9999);
    }
}

function terrainType(selectObj) {
    var idx = selectObj.selectedIndex;
    var atype = selectObj.options[idx].value;
    var type = atype.split('-');
    document.cookie =  'MapTerrain='+type+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    createCookie('MapTerrain',type,9999);
    if (type == 'stk') {
	stkterrain();
    } else if (type == 'articdem') {
	articterrain();
    } else if (type == 'ellipsoid') {
	ellipsoidterrain();
    } else if (type == 'vrterrain') {
	vrtheworldterrain();
    }
    //window.location.reload();
}

function sattypes(selectObj) {
    var sattypes = [], sattype;
    for (var i=0, len=selectObj.options.length; i< len;i++) {
	sattype = selectObj.options[i];
	if (sattype.selected) {
	    sattypes.push(sattype.value);
	}
    }
    createCookie('sattypes',sattypes.join(),2);
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
    createCookie('filter_Airlines',airs.join(),2);
}
function airlinestype(selectObj) {
    var idx = selectObj.selectedIndex;
    var airtype = selectObj.options[idx].value;
    createCookie('filter_airlinestype',airtype,2);
}
function racefilter(selectObj) {
    var idx = selectObj.selectedIndex;
    var race = selectObj.options[idx].value;
    if (race == 'all') {
	delCookie('filter_race');
    } else {
	createCookie('filter_race',race,2);
    }
    if (getCookie['MapFormat'] == '3d') {
	updateMarineData();
    } else {
	getLiveMarineData(0);
    }
}
function alliance(selectObj) {
    var idx = selectObj.selectedIndex;
    var alliance = selectObj.options[idx].value;
    createCookie('filter_alliance',alliance,2);
}
function identfilter() {
    var ident = $("#identfilter").value;
    createCookie('filter_ident',ident,2);
}
function mmsifilter() {
    var ident = $("#mmsifilter").value;
    createCookie('filter_mmsi',ident,2);
}
function removefilters() {
    // Get an array of all cookie names (the regex matches what we don't want)
    var cookieNames = document.cookie.split(/=[^;]*(?:;\s*|$)/);
    // Remove any that match the pattern
    for (var i = 0; i < cookieNames.length; i++) {
	if (/^filter_/.test(cookieNames[i])) {
	    delCookie(cookieNames[i]);
	}
    }
    window.location.reload();
}
function sources(selectObj) {
    var sources = [], source;
    for (var i=0, len=selectObj.options.length; i< len;i++) {
	source = selectObj.options[i];
	if (source.selected) {
	    sources.push(source.value);
	}
    }
    createCookie('filter_Sources',sources.join(),2);
}


function show2D() {
    createCookie('MapFormat','2d',10);
    if (document.getElementById("pointtype").className == 'tracker') {
	createCookie('MapTrackTracker',document.getElementById("pointident").className,1);
    } else if (document.getElementById("pointtype").className == 'marine') {
	createCookie('MapTrackMarine',document.getElementById("pointident").className,1);
    } else {
	createCookie('MapTrack',document.getElementById("pointident").className,1);
    }
    window.location.reload();
}
function show3D() {
    createCookie('MapFormat','3d',10);
    if (document.getElementById("pointtype").className == 'tracker') {
	createCookie('MapTrackTracker',document.getElementById("pointident").className,1);
    } else if (document.getElementById("pointtype").className == 'marine') {
	createCookie('MapTrackMarine',document.getElementById("pointident").className,1);
    } else {
	createCookie('MapTrack',document.getElementById("pointident").className,1);
    }
    window.location.reload();
}
function clickPolar(cb) {
    createCookie('polar',cb.checked,9999);
    window.location.reload();
}
function clickDisplayAirports(cb) {
    createCookie('displayairports',cb.checked,9999);
    window.location.reload();
}
function clickDisplayISS(cb) {
    createCookie('displayiss',cb.checked,9999);
    updateSat();
}
function clickDisplayMinimap(cb) {
    createCookie('displayminimap',cb.checked,9999);
    if (cb.checked == true) {
	displayMiniMap();
    } else {
	window.location.reload();
    }
}
function clickShadows(cb) {
    createCookie('map3dnoshadows',cb.checked,9999);
    viewer.shadows = cb.checked;
    //window.location.reload();
}
function clickSingleModel(cb) {
    createCookie('singlemodel',cb.checked,9999);
}
function clickUpdateRealtime(cb) {
    createCookie('updaterealtime',cb.checked,9999);
}
function clickVATSIM(cb) {
    createCookie('filter_ShowVATSIM',cb.checked,2);
}
function clickIVAO(cb) {
     createCookie('filter_ShowIVAO',cb.checked,2);
}
function clickphpVMS(cb) {
    createCookie('filter_ShowVMS',cb.checked,2);
}
function clickSBS1(cb) {
    createCookie('filter_ShowSBS1',cb.checked,2);
}
function clickBlocked(cb) {
    createCookie('filter_blocked',cb.checked,2);
}
function clickAPRS(cb) {
    createCookie('filter_ShowAPRS',cb.checked,2);
}
function clickDisplayGroundStation(cb) {
    createCookie('show_GroundStation',cb.checked,2);
    window.location.reload();
}
function clickDisplayWeatherStation(cb) {
    createCookie('show_WeatherStation',cb.checked,2);
    window.location.reload();
}
/*
function clickDisplayWeather(cb) {
    createCookie('show_Weather',cb.checked,2);
//    window.location.reload();
}
*/
function clickDisplayLightning(cb) {
    createCookie('show_Lightning',cb.checked,2);
    window.location.reload();
}
function clickDisplayFires(cb) {
    createCookie('show_Fires',cb.checked,2);
    window.location.reload();
}
function clickDisplay2DBuildings(cb) {
    createCookie('Map2DBuildings',cb.checked,2);
    window.location.reload();
}

function unitdistance(selectObj) {
    var idx = selectObj.selectedIndex;
    var unit = selectObj.options[idx].value;
    createCookie('unitdistance',unit,9999);
}
function unitspeed(selectObj) {
    var idx = selectObj.selectedIndex;
    var unit = selectObj.options[idx].value;
    createCookie('unitspeed',unit,9999);
    unitspeedvalue = unit;
}
function unitcoordinate(selectObj) {
    var idx = selectObj.selectedIndex;
    var unit = selectObj.options[idx].value;
    createCookie('unitcoordinate',unit,9999);
    unitcoordinate = unit;
}
function unitaltitude(selectObj) {
    var idx = selectObj.selectedIndex;
    var unit = selectObj.options[idx].value;
    createCookie('unitaltitude',unit,9999);
    unitaltitude = unit;
}

function addarchive(begindate,enddate) {
    console.log('Add archive');
    createCookie('archive',true,2);
    createCookie('archive_begin',begindate,2);
    createCookie('archive_end',enddate,2);
    createCookie('archive_speed',document.getElementById("archivespeed").value,2);
    window.location.reload();
}
function noarchive() {
    console.log('Exit archive!');
    delCookie('archive');
    delCookie('archive_begin');
    delCookie('archive_end');
    delCookie('archive_speed');
    window.location.reload();
}
function msgbox(text,buttontext) {
	buttontext = buttontext || "OK";
	$("<div>" + text + "</div>").dialog({
	    dialogClass: "no-close",
	    buttons: [{
		text: buttontext,
		click: function() {
		    $( this ).dialog( "close" );
		    $(this).remove();
		}
	    }]
	});
}
function generateRandomPoint (latitude,longitude,height,diff,radius) {

	//console.log('height: '+height+' - diff: '+diff);
	radius = Math.random()*radius;
	latitude = latitude*(Math.PI/180.0);
	longitude = longitude*(Math.PI/180.0);
	
	const sinLat = 	Math.sin(latitude)
	const cosLat = 	Math.cos(latitude)

	/* go fixed distance in random direction*/
	const bearing = Math.random() * Math.PI*2
	const theta = radius/6371000
	const sinBearing = Math.sin(bearing)
	const cosBearing = Math.cos(bearing)
	const sinTheta = Math.sin(theta)
	const cosTheta = Math.cos(theta)
    
	latitude = Math.asin(sinLat*cosTheta+cosLat*sinTheta*cosBearing);
	longitude = longitude + Math.atan2( sinBearing*sinTheta*cosLat, cosTheta-sinLat*Math.sin(latitude ));
	/* normalize -PI -> +PI radians */
	longitude = ((longitude+(Math.PI*3))%(Math.PI*2))-Math.PI
	var h = height+(Math.random()*diff)
	//console.log('h: '+h);
	return {
	    latitude: latitude/(Math.PI/180.0),
	    longitude: longitude/(Math.PI/180.0),
	    height: h
	};
}
function getColor(colorStart,colorEnd,colorCount,step) {
	var alpha = (1.0/colorCount)*step;
	return {
	    r: colorStart[0]*alpha+(1-alpha)*colorEnd[0],
	    v: colorStart[1]*alpha+(1-alpha)*colorEnd[1],
	    b: colorStart[2]*alpha+(1-alpha)*colorEnd[2]
	};
}
function convertDMS(coord,latlong) {
	if (latlong == 'latitude') {
		var nsew = (coord >= 0) ? 'N' : 'S';
	} else if (latlong == 'longitude') {
		var nsew = (coord >= 0) ? 'E' : 'W';
	}
	var coord = Math.abs(coord);
	var deg = Math.floor(coord);
	var min = Math.floor((coord - deg) * 60);
	var sec = Math.round((coord - deg - min / 60) * 3600);
	var result = deg+"° "+min+"' "+sec+'" '+nsew;
	return result;
}
function convertDM(coord,latlong) {
	if (latlong == 'latitude') {
		var nsew = (coord >= 0) ? 'N' : 'S';
	} else if (latlong == 'longitude') {
		var nsew = (coord >= 0) ? 'E' : 'W';
	}
	var coord = Math.abs(coord);
	var deg = Math.floor(coord);
	var min = Math.round((coord - deg) * 60 *1000)/1000;
	var result = deg+"° "+min+"' "+nsew;
	return result;
}
