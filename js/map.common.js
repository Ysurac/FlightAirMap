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
    //document.cookie = name+"="+value+expires;
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
    //document.cookie =  'sattypes='+sattypes.join()+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/'
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
    //document.cookie =  'filter_Airlines='+airs.join()+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/'
    createCookie('filter_Airlines',airs.join(),2);
}
function airlinestype(selectObj) {
    var idx = selectObj.selectedIndex;
    var airtype = selectObj.options[idx].value;
    //document.cookie =  'filter_airlinestype='+airtype+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/'
    createCookie('filter_airlinestype',airtype,2);
}
function alliance(selectObj) {
    var idx = selectObj.selectedIndex;
    var alliance = selectObj.options[idx].value;
    //document.cookie =  'filter_alliance='+alliance+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/'
    createCookie('filter_alliance',alliance,2);
}
function identfilter() {
    var ident = $("#identfilter").value;
    //document.cookie =  'filter_ident='+ident+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/'
    createCookie('filter_ident',ident,2);
}
function mmsifilter() {
    var ident = $("#mmsifilter").value;
    //document.cookie =  'filter_ident='+ident+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/'
    createCookie('filter_mmsi',ident,2);
}
function removefilters() {
    // Get an array of all cookie names (the regex matches what we don't want)
    var cookieNames = document.cookie.split(/=[^;]*(?:;\s*|$)/);
    // Remove any that match the pattern
    for (var i = 0; i < cookieNames.length; i++) {
    if (/^filter_/.test(cookieNames[i])) {
        document.cookie = cookieNames[i] + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
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
    //document.cookie =  'filter_Sources='+sources.join()+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/';
    createCookie('filter_Sources',sources.join(),2);
}


function show2D() {
    //document.cookie =  'MapFormat=2d; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/';
    createCookie('MapFormat','2d',10);
    document.cookie =  'MapTrack='+document.getElementById("aircraft_ident").className+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    window.location.reload();
}
function show3D() {
    createCookie('MapFormat','3d',10);
    document.cookie =  'MapTrack='+document.getElementById("aircraft_ident").className+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    window.location.reload();
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
    updateSat();
}
function clickDisplayMinimap(cb) {
    document.cookie =  'displayminimap='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
    window.location.reload();
}
function clickSingleModel(cb) {
    document.cookie =  'singlemodel='+cb.checked+'; expires=Thu, 2 Aug 2100 20:47:11 UTC; path=/'
}
function clickVATSIM(cb) {
    //document.cookie =  'filter_ShowVATSIM='+cb.checked+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/';
    createCookie('filter_ShowVATSIM',cb.checked,2);
}
function clickIVAO(cb) {
     //document.cookie =  'filter_ShowIVAO='+cb.checked+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/';
     createCookie('filter_ShowIVAO',cb.checked,2);
}
function clickphpVMS(cb) {
    //document.cookie =  'filter_ShowVMS='+cb.checked+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/';
    createCookie('filter_ShowVMS',cb.checked,2);
}
function clickSBS1(cb) {
    //document.cookie =  'filter_ShowSBS1='+cb.checked+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/';
    createCookie('filter_ShowSBS1',cb.checked,2);
}
function clickAPRS(cb) {
    //document.cookie =  'filter_ShowAPRS='+cb.checked+'; expires=<?php print date("D, j M Y G:i:s T",mktime(0, 0, 0, date("m")  , date("d")+2, date("Y"))); ?>; path=/';
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
function addarchive(begindate,enddate) {
    console.log('Add archive');
    //console.log('begin: '+begindate+' - end: '+enddate);
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