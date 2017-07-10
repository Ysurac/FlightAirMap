<?php
require_once('require/class.Connection.php');
require_once('require/class.Common.php');
require_once('require/class.Satellite.php');
date_default_timezone_set('UTC');
//$begintime = microtime(true);
$Satellite = new Satellite();
$Common = new Common();

if (isset($_GET['download'])) {
	if ($_GET['download'] == "true")
	{
		header('Content-disposition: attachment; filename="flightairmap-sat.json"');
	}
}
header('Content-Type: text/javascript');

$timeb = time();
//$sqltime = round(microtime(true)-$begintime,2);

$spotter_array = array();
if (isset($_COOKIE['sattypes']) && $_COOKIE['sattypes'] != '') {
	$sattypes = explode(',',$_COOKIE['sattypes']);
	foreach ($sattypes as $sattype) {
		$spotter_array = array_merge($Satellite->position_all_type($sattype,$timeb-$globalLiveInterval,$timeb),$spotter_array);
	}
}
if ((isset($_COOKIE['displayiss']) && $_COOKIE['displayiss'] == 'true') || !isset($_COOKIE['displayiss'])) {
	$spotter_array = array_merge($Satellite->position('ISS (ZARYA)',time()-$globalLiveInterval,time()),$spotter_array);
	$spotter_array = array_merge($Satellite->position('TIANGONG 1',time()-$globalLiveInterval,time()),$spotter_array);
	$spotter_array = array_merge($Satellite->position('TIANGONG-2',time()-$globalLiveInterval,time()),$spotter_array);
}
$spotter_array = array_unique($spotter_array,SORT_REGULAR);
/*
$modelsdb = array();
if (file_exists('models/space/space_modelsdb')) {
	if (($handle = fopen('models/space/space_modelsdb','r')) !== FALSE) {
		while (($row = fgetcsv($handle,1000)) !== FALSE) {
			if (isset($row[1]) ){
				$model = $row[0];
				$modelsdb[$model] = $row[1];
			}
		}
		fclose($handle);
	}
}
*/
//print_r($spotter_array);
$j = 0;
$prev_satname = '';

$output = '[';
$output .= '{"id" : "document", "name" : "famsat","version" : "1.0"';
//	$output .= ',"clock": {"interval" : "'.date("c",time()-$globalLiveInterval).'/'.date("c").'","currentTime" : "'.date("c",time() - $globalLiveInterval).'","multiplier" : 1,"range" : "LOOP_STOP","step": "SYSTEM_CLOCK_MULTIPLIER"}';

//	$output .= ',"clock": {"interval" : "'.date("c",time()-$globalLiveInterval).'/'.date("c").'","currentTime" : "'.date("c",time() - $globalLiveInterval).'","multiplier" : 1,"range" : "UNBOUNDED","step": "SYSTEM_CLOCK_MULTIPLIER"}';
$output .= ',"clock": {"currentTime" : "'.date("c",time() - $globalLiveInterval).'","multiplier" : 1,"range" : "UNBOUNDED","step": "SYSTEM_CLOCK_MULTIPLIER"}';
//$output .= ',"clock": {"currentTime" : "%minitime%","multiplier" : 1,"range" : "UNBOUNDED","step": "SYSTEM_CLOCK_MULTIPLIER"}';

//	$output .= ',"clock": {"interval" : "'.date("c",time()-$globalLiveInterval).'/'.date("c").'","currentTime" : "'.date("c",time() - $globalLiveInterval).'","multiplier" : 1,"step": "SYSTEM_CLOCK_MULTIPLIER"}';
$output .= '},';
if (!empty($spotter_array) && is_array($spotter_array))
{
	foreach($spotter_array as $spotter_item)
	{
		$j++;
		date_default_timezone_set('UTC');

		if ($prev_satname != $spotter_item['name']) {
			if ($prev_satname != '') {
				$output .= ']';
				$output .= '}';
				//$output .= ', '.$orientation.']}';
				$output .= '},';
			}
			$orientation = '';
			$prev_satname = $spotter_item['name'];
			$output .= '{';
			//$output .= '"id": "'.urlencode(trim(str_replace(array('[+]','[-]'),'',$spotter_item['name']))).'",';
			$output .= '"id": "'.urlencode($spotter_item['name']).'",';
			$output .= '"properties": {';
			// Not yet supported in CZML with Cesium
			$output .= '},';

			
			$output .= '"path" : { ';
			$output .= '"show" : false, ';
			$output .= '"material" : { ';
			$output .= '"polylineOutline" : { ';
			$output .= '"color" : { "rgba" : [238, 250, 255, 255] }, ';
			$output .= '"outlineColor" : { "rgba" : [200, 209, 214, 255] }, ';
			$output .= '"outlineWidth" : 5, ';
			$output .= '"polylineGlow" : { "color" : { "rgba" : [214, 208, 214, 255] }, "glowPower" : 3 } ';
			$output .= '}';
			$output .= '}, ';
			$output .= '"width" : 6, "leadTime" : 0, "trailTime" : 1000000, "resolution" : 10 },';
			
			//$output .= ' "billboard" : {"image" : "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACgAAAAfCAYAAACVgY94AAAACXBIWXMAAC4jAAAuIwF4pT92AAAKT2lDQ1BQaG90b3Nob3AgSUNDIHByb2ZpbGUAAHjanVNnVFPpFj333vRCS4iAlEtvUhUIIFJCi4AUkSYqIQkQSoghodkVUcERRUUEG8igiAOOjoCMFVEsDIoK2AfkIaKOg6OIisr74Xuja9a89+bN/rXXPues852zzwfACAyWSDNRNYAMqUIeEeCDx8TG4eQuQIEKJHAAEAizZCFz/SMBAPh+PDwrIsAHvgABeNMLCADATZvAMByH/w/qQplcAYCEAcB0kThLCIAUAEB6jkKmAEBGAYCdmCZTAKAEAGDLY2LjAFAtAGAnf+bTAICd+Jl7AQBblCEVAaCRACATZYhEAGg7AKzPVopFAFgwABRmS8Q5ANgtADBJV2ZIALC3AMDOEAuyAAgMADBRiIUpAAR7AGDIIyN4AISZABRG8lc88SuuEOcqAAB4mbI8uSQ5RYFbCC1xB1dXLh4ozkkXKxQ2YQJhmkAuwnmZGTKBNA/g88wAAKCRFRHgg/P9eM4Ors7ONo62Dl8t6r8G/yJiYuP+5c+rcEAAAOF0ftH+LC+zGoA7BoBt/qIl7gRoXgugdfeLZrIPQLUAoOnaV/Nw+H48PEWhkLnZ2eXk5NhKxEJbYcpXff5nwl/AV/1s+X48/Pf14L7iJIEyXYFHBPjgwsz0TKUcz5IJhGLc5o9H/LcL//wd0yLESWK5WCoU41EScY5EmozzMqUiiUKSKcUl0v9k4t8s+wM+3zUAsGo+AXuRLahdYwP2SycQWHTA4vcAAPK7b8HUKAgDgGiD4c93/+8//UegJQCAZkmScQAAXkQkLlTKsz/HCAAARKCBKrBBG/TBGCzABhzBBdzBC/xgNoRCJMTCQhBCCmSAHHJgKayCQiiGzbAdKmAv1EAdNMBRaIaTcA4uwlW4Dj1wD/phCJ7BKLyBCQRByAgTYSHaiAFiilgjjggXmYX4IcFIBBKLJCDJiBRRIkuRNUgxUopUIFVIHfI9cgI5h1xGupE7yAAygvyGvEcxlIGyUT3UDLVDuag3GoRGogvQZHQxmo8WoJvQcrQaPYw2oefQq2gP2o8+Q8cwwOgYBzPEbDAuxsNCsTgsCZNjy7EirAyrxhqwVqwDu4n1Y8+xdwQSgUXACTYEd0IgYR5BSFhMWE7YSKggHCQ0EdoJNwkDhFHCJyKTqEu0JroR+cQYYjIxh1hILCPWEo8TLxB7iEPENyQSiUMyJ7mQAkmxpFTSEtJG0m5SI+ksqZs0SBojk8naZGuyBzmULCAryIXkneTD5DPkG+Qh8lsKnWJAcaT4U+IoUspqShnlEOU05QZlmDJBVaOaUt2ooVQRNY9aQq2htlKvUYeoEzR1mjnNgxZJS6WtopXTGmgXaPdpr+h0uhHdlR5Ol9BX0svpR+iX6AP0dwwNhhWDx4hnKBmbGAcYZxl3GK+YTKYZ04sZx1QwNzHrmOeZD5lvVVgqtip8FZHKCpVKlSaVGyovVKmqpqreqgtV81XLVI+pXlN9rkZVM1PjqQnUlqtVqp1Q61MbU2epO6iHqmeob1Q/pH5Z/YkGWcNMw09DpFGgsV/jvMYgC2MZs3gsIWsNq4Z1gTXEJrHN2Xx2KruY/R27iz2qqaE5QzNKM1ezUvOUZj8H45hx+Jx0TgnnKKeX836K3hTvKeIpG6Y0TLkxZVxrqpaXllirSKtRq0frvTau7aedpr1Fu1n7gQ5Bx0onXCdHZ4/OBZ3nU9lT3acKpxZNPTr1ri6qa6UbobtEd79up+6Ynr5egJ5Mb6feeb3n+hx9L/1U/W36p/VHDFgGswwkBtsMzhg8xTVxbzwdL8fb8VFDXcNAQ6VhlWGX4YSRudE8o9VGjUYPjGnGXOMk423GbcajJgYmISZLTepN7ppSTbmmKaY7TDtMx83MzaLN1pk1mz0x1zLnm+eb15vft2BaeFostqi2uGVJsuRaplnutrxuhVo5WaVYVVpds0atna0l1rutu6cRp7lOk06rntZnw7Dxtsm2qbcZsOXYBtuutm22fWFnYhdnt8Wuw+6TvZN9un2N/T0HDYfZDqsdWh1+c7RyFDpWOt6azpzuP33F9JbpL2dYzxDP2DPjthPLKcRpnVOb00dnF2e5c4PziIuJS4LLLpc+Lpsbxt3IveRKdPVxXeF60vWdm7Obwu2o26/uNu5p7ofcn8w0nymeWTNz0MPIQ+BR5dE/C5+VMGvfrH5PQ0+BZ7XnIy9jL5FXrdewt6V3qvdh7xc+9j5yn+M+4zw33jLeWV/MN8C3yLfLT8Nvnl+F30N/I/9k/3r/0QCngCUBZwOJgUGBWwL7+Hp8Ib+OPzrbZfay2e1BjKC5QRVBj4KtguXBrSFoyOyQrSH355jOkc5pDoVQfujW0Adh5mGLw34MJ4WHhVeGP45wiFga0TGXNXfR3ENz30T6RJZE3ptnMU85ry1KNSo+qi5qPNo3ujS6P8YuZlnM1VidWElsSxw5LiquNm5svt/87fOH4p3iC+N7F5gvyF1weaHOwvSFpxapLhIsOpZATIhOOJTwQRAqqBaMJfITdyWOCnnCHcJnIi/RNtGI2ENcKh5O8kgqTXqS7JG8NXkkxTOlLOW5hCepkLxMDUzdmzqeFpp2IG0yPTq9MYOSkZBxQqohTZO2Z+pn5mZ2y6xlhbL+xW6Lty8elQfJa7OQrAVZLQq2QqboVFoo1yoHsmdlV2a/zYnKOZarnivN7cyzytuQN5zvn//tEsIS4ZK2pYZLVy0dWOa9rGo5sjxxedsK4xUFK4ZWBqw8uIq2Km3VT6vtV5eufr0mek1rgV7ByoLBtQFr6wtVCuWFfevc1+1dT1gvWd+1YfqGnRs+FYmKrhTbF5cVf9go3HjlG4dvyr+Z3JS0qavEuWTPZtJm6ebeLZ5bDpaql+aXDm4N2dq0Dd9WtO319kXbL5fNKNu7g7ZDuaO/PLi8ZafJzs07P1SkVPRU+lQ27tLdtWHX+G7R7ht7vPY07NXbW7z3/T7JvttVAVVN1WbVZftJ+7P3P66Jqun4lvttXa1ObXHtxwPSA/0HIw6217nU1R3SPVRSj9Yr60cOxx++/p3vdy0NNg1VjZzG4iNwRHnk6fcJ3/ceDTradox7rOEH0x92HWcdL2pCmvKaRptTmvtbYlu6T8w+0dbq3nr8R9sfD5w0PFl5SvNUyWna6YLTk2fyz4ydlZ19fi753GDborZ752PO32oPb++6EHTh0kX/i+c7vDvOXPK4dPKy2+UTV7hXmq86X23qdOo8/pPTT8e7nLuarrlca7nuer21e2b36RueN87d9L158Rb/1tWeOT3dvfN6b/fF9/XfFt1+cif9zsu72Xcn7q28T7xf9EDtQdlD3YfVP1v+3Njv3H9qwHeg89HcR/cGhYPP/pH1jw9DBY+Zj8uGDYbrnjg+OTniP3L96fynQ89kzyaeF/6i/suuFxYvfvjV69fO0ZjRoZfyl5O/bXyl/erA6xmv28bCxh6+yXgzMV70VvvtwXfcdx3vo98PT+R8IH8o/2j5sfVT0Kf7kxmTk/8EA5jz/GMzLdsAAAAgY0hSTQAAeiUAAICDAAD5/wAAgOkAAHUwAADqYAAAOpgAABdvkl/FRgAAA7VJREFUeNrEl2uIlWUQx39nXUu0m2uQbZYrbabdLKMs/VBkmHQjioqFIhBS+hKEQpQRgVAf2u5RQkGBRUllRH4I2e5ZUBJlEZVt5i0tTfHStrZ6fn35L70d9n7Obg88vOedmWfmf2bmmZkXlRrtq9V16mZ1iVqqhd5agXvQf1c5zw/V8dXqrqO6dQKwBrgdWApsCb0VqAc2AnOrMVANwIsD4BLgTOBPYB2wHJgEzAG+ANqAu4ZsZYiuX5QwfqI2hvaNulA9J7zLQn8o76vUuuHOwXHqSzH4aIF+TWjnBkSH+nCBf716SP1KPWO4AJ6ltgfIjRW8p9U/1KPz/ry6RT2mIDNF3Zjz19Ya4G1R/J16dgWvQd2pPlXhMdVZPUTgxfCW1wJgXUJpQlvfg8zs8K8r0Caom9QHetG7NGfa1ElDBThRXRtFd/Qh16puKIS3e7+clBjdy7kL1b3q4fzJQQGck5z6Nb97kxujblWf64HXov7Vl/E4YXWccP9AAd6dAx+ox/WTArNzY1t64B0f8K0DyLXuUvRGZfcpCo1VX4tg6wB76WMB0dALf526foAX8cqUot2pGP8B2Kz+krBeNYjS8636dh/8Beo2deoA9TWp76pd6g0q9cDNwKvAD8A84EfglLRBe2g+JWAfcEF68bPABOCoAl/gIPA5MA64FVgGnNhP292W3r0SeB1YVlJXAjcBP8XwyQUj9AKwAzg2+/fQSsBhoJxBAaALaIzenZGnD911wA7gEDAD2FFSpwOzgDHZ5T7+ZSlGd2d6AXgi5+qAn+O5U0PbBVwKtAD3AHuB8f3YGBUdncCGoQ4LE9XtGRqK9LnduVPRIu2BPqwD65IYbS7Qpql7Ql9YoJcy9bwzkgPrfOCj5G33+h54E/g0PAr5thq4ApgyEgNrc27aWwVaPTA1QJ4BjgTGFvhteV40EgPrgvTP7qlmZqFnl9WD+b2posN83E/NrEkOjlI/U1fkfUYa/pe5IE3qZPW8jFOqiyN7p3pAPX04c7AxYSoDDcAjKT2LgLXA6IR2M3Bviv59wDTgQGTPH84Qd8+HXfHcoUws2zM0HMjuUPep+xP2PWpnwtw0GJsldbBpewQwE/gbeDyt7H1gcW53O7AC+A3Yn6+/W+Ld9SnWA15DAVhc8xK2TuA9YHrCuhV4EngFuBx4YagG6qv8cF+T52kB2Zy+e1I8taUacNV+uBdXO7ABmJwJpwx8XQvF9TUCWM64tiQhbq/oMv+7BwFWpQzNT8vbVQul/wwAGzzdmXU1xuUAAAAASUVORK5CYII=","scale" : 1.5},';
			if (file_exists('models/space/iss.glb')) {
				if ($spotter_item['name'] == 'ISS (ZARYA)') {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/iss.glb'.'","scale" : 1.0,"minimumPixelSize": 50,"maximunPixelSize": 300 },';
				} elseif ($spotter_item['name'] == 'TIANGONG 1') {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/tiangong1.glb'.'","scale" : 1.0,"minimumPixelSize": 50,"maximunPixelSize": 300 },';
				} elseif ($spotter_item['name'] == 'TIANGONG-2') {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/tiangong1.glb'.'","scale" : 1.0,"minimumPixelSize": 50,"maximunPixelSize": 300 },';
				} elseif ($spotter_item['name'] == 'IBEX') {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/ibex.glb'.'","scale" : 1.0,"minimumPixelSize": 25,"maximunPixelSize": 300 },';
				} elseif ($spotter_item['name'] == 'SDO') {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/sdo.glb'.'","scale" : 1.0,"minimumPixelSize": 25,"maximunPixelSize": 300 },';
				} elseif ($spotter_item['name'] == 'INTEGRAL') {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/integral.glb'.'","scale" : 1.0,"minimumPixelSize": 25,"maximunPixelSize": 300 },';
				} elseif ($spotter_item['name'] == 'AQUA') {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/aqua.glb'.'","scale" : 1.0,"minimumPixelSize": 25,"maximunPixelSize": 300 },';
				} elseif ($spotter_item['name'] == 'MINXSS') {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/cubiesat.glb'.'","scale" : 1.0,"minimumPixelSize": 25,"maximunPixelSize": 300 },';
				} elseif ($spotter_item['name'] == 'TERRA') {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/terra.glb'.'","scale" : 1.0,"minimumPixelSize": 25,"maximunPixelSize": 300 },';
				} elseif (strpos($spotter_item['name'],'O3B') !== false) {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/o3b.glb'.'","scale" : 1.0,"minimumPixelSize": 25,"maximunPixelSize": 300 },';
				} elseif (strpos($spotter_item['name'],'GLOBALSTAR') !== false) {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/globalstar.glb'.'","scale" : 1.0,"minimumPixelSize": 25,"maximunPixelSize": 300 },';
				} elseif (strpos($spotter_item['name'],'GPS') !== false) {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/gps.glb'.'","scale" : 1.0,"minimumPixelSize": 25,"maximunPixelSize": 300 },';
				} elseif (strpos($spotter_item['name'],'GENESIS') !== false) {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/genesis.glb'.'","scale" : 1.0,"minimumPixelSize": 25,"maximunPixelSize": 300 },';
				} elseif (strpos($spotter_item['name'],'OSCAR 7') !== false) {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/oscar7.glb'.'","scale" : 1.0,"minimumPixelSize": 25,"maximunPixelSize": 300 },';
				} elseif (strpos($spotter_item['name'],'FLOCK') !== false) {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/cubesat.glb'.'","scale" : 1.0,"minimumPixelSize": 25,"maximunPixelSize": 300 },';
				} elseif (strpos($spotter_item['name'],'PLEIADES') !== false) {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/pleiades.glb'.'","scale" : 1.0,"minimumPixelSize": 25,"maximunPixelSize": 300 },';
				} elseif (strpos($spotter_item['name'],'DUCHIFAT') !== false) {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/duchifat.glb'.'","scale" : 1.0,"minimumPixelSize": 25,"maximunPixelSize": 300 },';
				} elseif (strpos($spotter_item['name'],'FORMOSAT-2') !== false) {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/formosat2.glb'.'","scale" : 1.0,"minimumPixelSize": 25,"maximunPixelSize": 300 },';
				} elseif ($spotter_item['type'] == 'iridium') {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/iridium.glb'.'","scale" : 1.0,"minimumPixelSize": 25,"maximunPixelSize": 300 },';
				} elseif ($spotter_item['type'] == 'geo') {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/geo.glb'.'","scale" : 1.0,"minimumPixelSize": 25,"maximunPixelSize": 300 },';
				} elseif ($spotter_item['type'] == 'cubesat') {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/cubesat.glb'.'","scale" : 1.0,"minimumPixelSize": 25,"maximunPixelSize": 300 },';
				} else {
					$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/sat.glb'.'","scale" : 1.0,"minimumPixelSize": 25,"maximunPixelSize": 300 },';
				}
			} else {
				$output .= '"model": {"gltf" : "'.$globalURL.'/models/space/sat.glb'.'","scale" : 1.0,"minimumPixelSize": 25,"maximunPixelSize": 300 },';
			}
			$output .= '"heightReference": "CLAMP_TO_GROUND",';
			$output .= '"position": {';
			$output .= '"type": "Point",';
	//		$output .= '"interpolationAlgorithm" : "LAGRANGE",';
	//		$output .= '"interpolationDegree" : 5,';
	//		$output .= '"epoch" : "'.date("c",strtotime($spotter_item['date'])).'", ';
			$output .= '"interpolationAlgorithm":"HERMITE","interpolationDegree":3,';
			$output .= '"cartographicDegrees": [';
			$output .= '"'.date("c",$spotter_item['timestamp']).'", ';
			$output .= $spotter_item['longitude'].', ';
			$output .= $spotter_item['latitude'].', ';
			$output .= $spotter_item['altitude']*1000;
			$orientation = '"orientation" : { ';
			$orientation .= '"unitQuaternion": [';
		} else {
			$output .= ',"'.date("c",$spotter_item['timestamp']).'", ';
			$output .= $spotter_item['longitude'].', ';
			$output .= $spotter_item['latitude'].', ';
			$output .= $spotter_item['altitude']*1000;
		}
	}
	$output  = substr($output, 0, -1);
	$output .= ']}}';
} else {
	$output  = substr($output, 0, -1);
}
$output .= ']';
print $output;
?>
