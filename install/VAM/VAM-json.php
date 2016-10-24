<?php  
/*
 * Live flights for Virtual Airlines Manager (VAM)
 * Copy this file to your vam directory
*/
header("Content-type: application/json");
include('db_login.php');
$db = new mysqli($db_host, $db_username, $db_password, $db_database);
$db->set_charset("utf8");
if ($db->connect_errno > 0) {
	die();
}
$query = 'select * from vam_live_flights rc, gvausers gu where gu.gvauser_id = rc.gvauser_id';  
$json_data=array();  
$result = $db->query($query);
while ($rec = $result->fetch_assoc())
{  
	$json_array['gvauser_id']=$rec['gvauser_id']; // users "pilot unique ID" "40"
	$json_array['flight_id']=$rec['flight_id']; // flight_id
	$json_array['pilot_id']=$rec['callsign']; // users pilot_id "VAM500"
	$json_array['callsign']=substr($rec['flight_id'],-7);  // substr icao Flight "AFR524"
	$json_array['pilot_name']=$rec['name'] .' '.$rec['surname'] ;  // Users "name + surname"
	$json_array['plane_type']=$rec['plane_type'];  // type Plane "B739"
	$json_array['departure']=$rec['departure'];  // departure ICAO
	$json_array['arrival']=$rec['arrival'];     // arrival Ident
	$json_array['latitude']=$rec['latitude'];  // return 55.7328860921521
	$json_array['longitude']=$rec['longitude'];  // return 8.87433614409404
	$json_array['altitude']=$rec['altitude'];  // return "147"
	$json_array['heading']=$rec['heading'];  // return "307"
	$json_array['ias']=$rec['ias'];  // return speed "IAS"
	$json_array['gs']=$rec['gs'];  // return speed "GS"
	// $json_array['routes']=$rec['routes'];  // (unusable)
	$json_array['flight_status']=$rec['flight_status'];  // "return Status"
	$json_array['last_update']=$rec['last_update'];  // return "DateTime"
	$json_data[] = $json_array;
 }  
echo json_encode($json_data);
?>