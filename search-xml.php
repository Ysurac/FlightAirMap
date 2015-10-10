<?php
require('require/class.Connection.php');
require('require/class.Spotter.php');

if (isset($_GET['start_date'])) {
        //for the date manipulation into the query
        if($_GET['start_date'] != "" && $_GET['end_date'] != ""){
                $start_date = $_GET['start_date'].":00";
                $end_date = $_GET['end_date'].":00";
                $sql_date = $start_date.",".$end_date;
        } else if($_GET['start_date'] != ""){
                $start_date = $_GET['start_date'].":00";
                $sql_date = $start_date;
        } else if($_GET['start_date'] == "" && $_GET['end_date'] != ""){
                $end_date = date("Y-m-d H:i:s", strtotime("2014-04-12")).",".$_GET['end_date'].":00";
                $sql_date = $end_date;
        } else $sql_date = '';
} else $sql_date = '';

if (isset($_GET['highest_altitude'])) {
        //for altitude manipulation
        if($_GET['highest_altitude'] != "" && $_GET['lowest_altitude'] != ""){
                $end_altitude = $_GET['highest_altitude'];
                $start_altitude = $_GET['lowest_altitude'];
                $sql_altitude = $start_altitude.",".$end_altitude;
        } else if($_GET['highest_altitude'] != ""){
                $end_altitude = $_GET['highest_altitude'];
                $sql_altitude = $end_altitude;
        } else if($_GET['highest_altitude'] == "" && $_GET['lowest_altitude'] != ""){
                $start_altitude = $_GET['lowest_altitude'].",60000";
                $sql_altitude = $start_altitude;
        } else $sql_altitude = '';
} else $sql_altitude = '';

//calculuation for the pagination
if(!isset($_GET['limit']))
{
        if (!isset($_GET['number_results']))
        {
                $limit_start = 0;
                $limit_end = 25;
                $absolute_difference = 25;
        } else {
                if ($_GET['number_results'] > 1000){
                        $_GET['number_results'] = 1000;
                }
                $limit_start = 0;
                $limit_end = $_GET['number_results'];
                $absolute_difference = $_GET['number_results'];
        }
}  else {
        $limit_explode = explode(",", $_GET['limit']);
        $limit_start = $limit_explode[0];
        $limit_end = $limit_explode[1];
}

$absolute_difference = abs($limit_start - $limit_end);
$limit_next = $limit_end + $absolute_difference;
$limit_previous_1 = $limit_start - $absolute_difference;
$limit_previous_2 = $limit_end - $absolute_difference;

if ($_GET['download'] == "true")
{
	header('Content-disposition: attachment; filename="barriespotter.xml"');
}

header('Content-Type: application/xml');

if (isset($_GET['sort'])) $sort = $_GET['sort'];
else $sort = '';
$spotter_array = Spotter::searchSpotterData($_GET['q'],$_GET['registration'],$_GET['aircraft'],strtolower(str_replace("-", " ", $_GET['manufacturer'])),$_GET['highlights'],$_GET['airline'],$_GET['airline_country'],$_GET['airline_type'],$_GET['airport'],$_GET['airport_country'],$_GET['callsign'],$_GET['departure_airport_route'],$_GET['arrival_airport_route'],$sql_altitude,$sql_date,$limit_start.",".$absolute_difference,$sort,'');
      
$output .= '<?xml version="1.0" encoding="UTF-8" ?>';
  $output .= '<barriespotter>';
    $output .= '<title>Barrie Spotter XML Feed</title>';
    $output .= '<link>http://www.barriespotter.com</link>';
    $output .= '<aircrafts>';
    
    if (!empty($spotter_array))
	  {
	    foreach($spotter_array as $spotter_item)
	    {
	    	
            $output .= '<aircraft>';
              $output .= '<id>'.$spotter_item['spotter_id'].'</id>';
		      $output .= '<ident>'.$spotter_item['ident'].'</ident>';
		      $output .= '<registration>'.$spotter_item['registration'].'</registration>';
              $output .= '<aircraft_icao>'.$spotter_item['aircraft_type'].'</aircraft_icao>';
		      $output .= '<aircraft_name>'.$spotter_item['aircraft_name'].'</aircraft_name>';
              $output .= '<aircraft_manufacturer>'.$spotter_item['aircraft_manufacturer'].'</aircraft_manufacturer>';
              $output .= '<airline_name>'.$spotter_item['airline_name'].'</airline_name>';
		      $output .= '<airline_icao>'.$spotter_item['airline_icao'].'</airline_icao>';
              $output .= '<airline_iata>'.$spotter_item['airline_iata'].'</airline_iata>';
              $output .= '<airline_country>'.$spotter_item['airline_country'].'</airline_country>';
              $output .= '<airline_callsign>'.$spotter_item['airline_callsign'].'</airline_callsign>';
              $output .= '<airline_type>'.$spotter_item['airline_type'].'</airline_type>';
              $output .= '<departure_airport_city>'.$spotter_item['departure_airport_city'].'</departure_airport_city>';
		      $output .= '<departure_airport_country>'.$spotter_item['departure_airport_country'].'</departure_airport_country>';  
              $output .= '<departure_airport_iata>'.$spotter_item['departure_airport_iata'].'</departure_airport_iata>';
              $output .= '<departure_airport_icao>'.$spotter_item['departure_airport_icao'].'</departure_airport_icao>';
              $output .= '<departure_airport_latitude>'.$spotter_item['departure_airport_latitude'].'</departure_airport_latitude>';
              $output .= '<departure_airport_longitude>'.$spotter_item['departure_airport_longitude'].'</departure_airport_longitude>';
              $output .= '<departure_airport_altitude>'.$spotter_item['departure_airport_altitude'].'</departure_airport_altitude>';
              $output .= '<arrival_airport_city>'.$spotter_item['arrival_airport_city'].'</arrival_airport_city>';
		      $output .= '<arrival_airport_country>'.$spotter_item['arrival_airport_country'].'</arrival_airport_country>';  
              $output .= '<arrival_airport_iata>'.$spotter_item['arrival_airport_iata'].'</arrival_airport_iata>';
              $output .= '<arrival_airport_icao>'.$spotter_item['arrival_airport_icao'].'</arrival_airport_icao>';
              $output .= '<arrival_airport_latitude>'.$spotter_item['arrival_airport_latitude'].'</arrival_airport_latitude>';
              $output .= '<arrival_airport_longitude>'.$spotter_item['arrival_airport_longitude'].'</arrival_airport_longitude>';
              $output .= '<arrival_airport_altitude>'.$spotter_item['arrival_airport_altitude'].'</arrival_airport_altitude>';
              $output .= '<latitude>'.$spotter_item['latitude'].'</latitude>';
              $output .= '<longitude>'.$spotter_item['longitude'].'</longitude>';
              $output .= '<altitude>'.$spotter_item['altitude'].'</altitude>';
              $output .= '<ground_speed>'.$spotter_item['ground_speed'].'</ground_speed>';
              $output .= '<heading>'.$spotter_item['heading'].'</heading>';
              $output .= '<heading_name>'.$spotter_item['heading_name'].'</heading_name>';
              $output .= '<waypoints>'.$spotter_item['waypoints'].'</waypoints>';
		      $output .= '<date>'.date("c", strtotime($spotter_item['date_iso_8601'])).'</date>';
            $output .= '</aircraft>';
	    }
	   }
		 $output .= '</aircrafts>';
$output .= '</barriespotter>';

print $output;

?>