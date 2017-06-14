<?php
/**
* This script is used for push callback
*/

require_once(dirname(__FILE__).'/../require/class.SpotterImport.php');
require_once(dirname(__FILE__).'/../require/class.SpotterServer.php');

require_once(dirname(__FILE__).'/../require/class.Source.php');
require_once(dirname(__FILE__).'/../require/class.Connection.php');
require_once(dirname(__FILE__).'/../require/class.Common.php');
$Common=new Common();
$authorize = false;
$params = array();
$userip = $Common->getUserIP();
foreach ($globalSources as $key => $cb) {
	if (isset($cb['callback']) && $cb['callback']) {
		if (isset($_GET['pass']) && isset($cb['pass']) && $cb['pass'] == $_GET['pass']) {
			$params = $globalSources[$key];
			$authorize = true;
			break;
		}
		if ($userip != '' && isset($cb['host']) && in_array($userip,explode(',',$cb['host']))) {
			$params = $globalSources[$key];
			$authorize = true;
			break;
		}
	}
}
if ($authorize === false) die;

if (isset($globalTracker) && $globalTracker) require_once(dirname(__FILE__).'/../require/class.TrackerImport.php');
if (isset($globalMarine) && $globalMarine) {
	require_once(dirname(__FILE__).'/../require/class.AIS.php');
	require_once(dirname(__FILE__).'/../require/class.MarineImport.php');
}

if (!isset($globalDebug)) $globalDebug = FALSE;

// Check if schema is at latest version
$Connection = new Connection();
if ($Connection->latest() === false) {
	echo "You MUST update to latest schema. Run install/index.php";
	exit();
}

if (isset($globalServer) && $globalServer) {
	if ($globalDebug) echo "Using Server Mode\n";
	$SI=new SpotterServer();
} else $SI=new SpotterImport($Connection->db);
if (isset($globalTracker) && $globalTracker) $TI = new TrackerImport($Connection->db);
if (isset($globalMarine) && $globalMarine) {
	$AIS = new AIS();
	$MI = new MarineImport($Connection->db);
}
$Source=new Source($Connection->db);
date_default_timezone_set('UTC');

$buffer = '';
if (isset($_POST)) $buffer = $_POST;
$data = array();
if (isset($buffer['type_event']) && isset($buffer['lat']) && isset($buffer['lon'])) {
	$data['ident'] = $buffer['device_id'];
	$data['latitude'] = $buffer['lat'];
	$data['longitude'] = $buffer['lon'];
	$data['altitude'] = round($buffer['altitude']*3.28084);
	$data['speed'] = $buffer['speed'];
	//$data['heading'] = $buffer['cap']; // Only N/S/E/W
	$data['datetime'] = date('Y-m-d H:i:s',$buffer['timestamp']);
	$data['comment'] = '';
	if (isset($buffer['battery']) && $buffer['battery'] != '') $data['comment'] .= 'Battery: '.$buffer['battery'].'% ';
	//if (isset($buffer['snr']) && $buffer['snr'] != '') $data['comment'] .= 'SNR: '.$buffer['snr'].' ';
	if (isset($buffer['temp']) && $buffer['temp'] != '') $data['comment'] .= 'Temperature: '.$buffer['temp'].'°C ';
	if (isset($buffer['press']) && $buffer['press'] != '') $data['comment'] .= 'Pressure: '.$buffer['press'].'hPa ';
	$TI->add($data);
	unset($data);
}
if (isset($SI)) $SI->checkAll();
if (isset($MI)) $MI->checkAll();
if (isset($TI)) $TI->checkAll();

?>
