<?php
class SpotterArchive {
    public $global_query = "SELECT spotter_archive.* FROM spotter_archive";
    public $db;

    function __construct() {
                $Connection = new Connection();
                $this->db = $Connection->db;
    }

    function addSpotterArchiveData($flightaware_id = '', $ident = '', $registration = '', $airline_name = '', $airline_icao = '', $airline_country = '', $airline_type = '', $aircraft_icao = '', $aircraft_shadow = '', $aircraft_name = '', $aircraft_manufacturer = '', $departure_airport_icao = '', $departure_airport_name = '', $departure_airport_city = '', $departure_airport_country = '', $departure_airport_time = '',$arrival_airport_icao = '', $arrival_airport_name = '', $arrival_airport_city ='', $arrival_airport_country = '', $arrival_airport_time = '', $route_stop = '', $date = '',$latitude = '', $longitude = '', $waypoints = '', $altitude = '', $heading = '', $ground_speed = '', $squawk = '', $ModeS = '', $pilot_id = '', $pilot_name = '') {
	// Route is not added in spotter_archive
	$query  = "INSERT INTO spotter_archive (flightaware_id, ident, registration, airline_name, airline_icao, airline_country, airline_type, aircraft_icao, aircraft_shadow, aircraft_name, aircraft_manufacturer, departure_airport_icao, departure_airport_name, departure_airport_city, departure_airport_country, departure_airport_time,arrival_airport_icao, arrival_airport_name, arrival_airport_city, arrival_airport_country, arrival_airport_time, route_stop, date,latitude, longitude, waypoints, altitude, heading, ground_speed, squawk, ModeS, pilot_id, pilot_name)
                VALUES (:flightaware_id, :ident, :registration, :airline_name, :airline_icao, :airline_country, :airline_type, :aircraft_icao, :aircraft_shadow, :aircraft_name, :aircraft_manufacturer, :departure_airport_icao, :departure_airport_name, :departure_airport_city, :departure_airport_country, :departure_airport_time,:arrival_airport_icao, :arrival_airport_name, :arrival_airport_city, :arrival_airport_country, :arrival_airport_time, :route_stop, :date,:latitude, :longitude, :waypoints, :altitude, :heading, :ground_speed, :squawk, :ModeS, :pilot_id, :pilot_name)";

        $query_values = array(':flightaware_id' => $flightaware_id, ':ident' => $ident, ':registration' => $registration, ':airline_name' => $airline_name, ':airline_icao' => $airline_icao, ':airline_country' => $airline_country, ':airline_type' => $airline_type, ':aircraft_icao' => $aircraft_icao, ':aircraft_shadow' => $aircraft_shadow, ':aircraft_name' => $aircraft_name, ':aircraft_manufacturer' => $aircraft_manufacturer, ':departure_airport_icao' => $departure_airport_icao, ':departure_airport_name' => $departure_airport_name, ':departure_airport_city' => $departure_airport_city, ':departure_airport_country' => $departure_airport_country, ':departure_airport_time' => $departure_airport_time,':arrival_airport_icao' => $arrival_airport_icao, ':arrival_airport_name' => $arrival_airport_name, ':arrival_airport_city' => $arrival_airport_city, ':arrival_airport_country' => $arrival_airport_country, ':arrival_airport_time' => $arrival_airport_time, ':route_stop' => $route_stop, ':date' => $date,':latitude' => $latitude, ':longitude' => $longitude, ':waypoints' => $waypoints, ':altitude' => $altitude, ':heading' => $heading, ':ground_speed' => $ground_speed, ':squawk' => $squawk, ':ModeS' => $ModeS, ':pilot_id' => $pilot_id, ':pilot_name' => $pilot_name);
        try {
            $sth = $this->db->prepare($query);
            $sth->execute($query_values);
        } catch(PDOException $e) {
            return "error : ".$e->getMessage();
        }
        return "success";
    }


        /**
        * Gets all the spotter information based on a particular callsign
        *
        * @return Array the spotter information
        *
        */
        public function getLastArchiveSpotterDataByIdent($ident)
        {
		$Spotter = new Spotter();
                date_default_timezone_set('UTC');

                $ident = filter_var($ident, FILTER_SANITIZE_STRING);
                $query  = "SELECT spotter_archive.* FROM spotter_archive INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_archive l WHERE l.ident = :ident GROUP BY l.flightaware_id) s on spotter_archive.flightaware_id = s.flightaware_id AND spotter_archive.date = s.maxdate LIMIT 1";

                $spotter_array = $Spotter->getDataFromDB($query,array(':ident' => $ident));

                return $spotter_array;
        }


        /**
        * Gets last the spotter information based on a particular id
        *
        * @return Array the spotter information
        *
        */
        public function getLastArchiveSpotterDataById($id)
        {
    		$Spotter = new Spotter();
                date_default_timezone_set('UTC');
                $id = filter_var($id, FILTER_SANITIZE_STRING);
                //$query  = SpotterArchive->$global_query." WHERE spotter_archive.flightaware_id = :id";
                $query  = "SELECT spotter_archive.* FROM spotter_archive INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_archive l WHERE l.flightaware_id = :id GROUP BY l.flightaware_id) s on spotter_archive.flightaware_id = s.flightaware_id AND spotter_archive.date = s.maxdate LIMIT 1";

//              $spotter_array = Spotter->getDataFromDB($query,array(':id' => $id));
                  /*
                try {
                        $Connection = new Connection();
                        $sth = Connection->$db->prepare($query);
                        $sth->execute(array(':id' => $id));
                } catch(PDOException $e) {
                        return "error";
                }
                $spotter_array = $sth->fetchAll(PDO->FETCH_ASSOC);
                */
                $spotter_array = $Spotter->getDataFromDB($query,array(':id' => $id));

                return $spotter_array;
        }

        /**
        * Gets all the spotter information based on a particular id
        *
        * @return Array the spotter information
        *
        */
        public function getAllArchiveSpotterDataById($id)
        {
                date_default_timezone_set('UTC');
                $id = filter_var($id, FILTER_SANITIZE_STRING);
                $query  = $this->global_query." WHERE spotter_archive.flightaware_id = :id";

//              $spotter_array = Spotter->getDataFromDB($query,array(':id' => $id));

                try {
                        $sth = $this->db->prepare($query);
                        $sth->execute(array(':id' => $id));
                } catch(PDOException $e) {
                        return "error";
                }
                $spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);

                return $spotter_array;
        }


        /**
        * Gets altitude information based on a particular callsign
        *
        * @return Array the spotter information
        *
        */
        public function getAltitudeArchiveSpotterDataByIdent($ident)
        {

                date_default_timezone_set('UTC');

                $ident = filter_var($ident, FILTER_SANITIZE_STRING);
                $query  = "SELECT spotter_archive.altitude, spotter_archive.date FROM spotter_archive WHERE spotter_archive.ident = :ident";

                try {
                        $sth = $this->db->prepare($query);
                        $sth->execute(array(':ident' => $ident));
                } catch(PDOException $e) {
                        return "error";
                }
                $spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);

                return $spotter_array;
        }

        /**
        * Gets altitude information based on a particular id
        *
        * @return Array the spotter information
        *
        */
        public function getAltitudeArchiveSpotterDataById($id)
        {

                date_default_timezone_set('UTC');

                $id = filter_var($id, FILTER_SANITIZE_STRING);
                $query  = "SELECT spotter_archive.altitude, spotter_archive.date FROM spotter_archive WHERE spotter_archive.flightaware_id = :id";

                try {
                        $sth = $this->db->prepare($query);
                        $sth->execute(array(':id' => $id));
                } catch(PDOException $e) {
                        return "error";
                }
                $spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);

                return $spotter_array;
        }

        /**
        * Gets altitude information based on a particular callsign
        *
        * @return Array the spotter information
        *
        */
        public function getLastAltitudeArchiveSpotterDataByIdent($ident)
        {

                date_default_timezone_set('UTC');

                $ident = filter_var($ident, FILTER_SANITIZE_STRING);
                $query  = "SELECT spotter_archive.altitude, spotter_archive.date FROM spotter_archive INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_archive l WHERE l.ident = :ident GROUP BY l.flightaware_id) s on spotter_archive.flightaware_id = s.flightaware_id AND spotter_archive.date = s.maxdate LIMIT 1";
//                $query  = "SELECT spotter_archive.altitude, spotter_archive.date FROM spotter_archive WHERE spotter_archive.ident = :ident";

                try {
                        $sth = $this->db->prepare($query);
                        $sth->execute(array(':ident' => $ident));
                } catch(PDOException $e) {
                        return "error";
                }
                $spotter_array = $sth->fetchAll(PDO::FETCH_ASSOC);

                return $spotter_array;
        }



       /**
        * Gets all the archive spotter information
        *
        * @return Array the spotter information
        *
        */
        public function getSpotteArchiveData($ident,$flightaware_id,$date)
        {
    		$Spotter = new Spotter();
                $ident = filter_var($ident, FILTER_SANITIZE_STRING);
                $query  = "SELECT spotter_live.* FROM spotter_live INNER JOIN (SELECT l.flightaware_id, max(l.date) as maxdate FROM spotter_live l WHERE l.ident = :ident AND l.flightaware_id = :flightaware_id AND l.date LIKE :date GROUP BY l.flightaware_id) s on spotter_live.flightaware_id = s.flightaware_id AND spotter_live.date = s.maxdate";

                $spotter_array = $Spotter->getDataFromDB($query,array(':ident' => $ident,':flightaware_id' => $flightaware_id,':date' => $date.'%'));

                return $spotter_array;
        }



}

?>