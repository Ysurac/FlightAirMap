CREATE TABLE IF NOT EXISTS `spotter_output` (
  `spotter_id` int(11) NOT NULL AUTO_INCREMENT,
  `flightaware_id` varchar(999) NOT NULL,
  `ident` varchar(999) NOT NULL,
  `registration` varchar(999),
  `airline_name` varchar(999),
  `airline_icao` varchar(999),
  `airline_country` varchar(999),
  `airline_type` varchar(999),
  `aircraft_icao` varchar(999),
  `aircraft_name` varchar(999),
  `aircraft_manufacturer` varchar(999),
  `departure_airport_icao` varchar(999),
  `departure_airport_name` varchar(999),
  `departure_airport_city` varchar(999),
  `departure_airport_country` varchar(999),
  `departure_airport_time` varchar(20),
  `arrival_airport_icao` varchar(999),
  `arrival_airport_name` varchar(999),
  `arrival_airport_city` varchar(999),
  `arrival_airport_country` varchar(999),
  `arrival_airport_time` varchar(20),
  `route_stop` varchar(255),
  `date` timestamp NOT NULL,
  `latitude` float NOT NULL,
  `longitude` float NOT NULL,
  `waypoints` longtext NOT NULL,
  `altitude` int(11) NOT NULL,
  `heading` int(11) NOT NULL,
  `ground_speed` int(11),
  `highlight` longtext,
  `squawk` int(11),
  `ModeS` varchar(255) NOT NULL,
  `pilot_id` varchar(255),
  `pilot_name` varchar(255),
  `owner_name` varchar(255),
  `verticalrate` int(11),
  `format_source` varchar(255) DEFAULT NULL,
  `ground` tinyint(1) NOT NULL DEFAULT '0',
  `last_ground` tinyint(1) NOT NULL DEFAULT '0',
  `last_seen` datetime DEFAULT NULL,
  `last_latitude` float DEFAULT NULL,
  `last_longitude` float DEFAULT NULL,
  `last_altitude` int(11) DEFAULT NULL,
  `last_ground_speed` int(11) DEFAULT NULL,
  `real_arrival_airport_icao` varchar(999) DEFAULT NULL,
  `real_arrival_airport_time` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`spotter_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;

ALTER TABLE `spotter_output` ADD INDEX(`flightaware_id`);
ALTER TABLE `spotter_output` ADD INDEX(`date`);
