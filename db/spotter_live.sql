CREATE TABLE IF NOT EXISTS `spotter_live` (
  `spotter_live_id` int(11) NOT NULL AUTO_INCREMENT,
  `flightaware_id` varchar(999) NOT NULL,
  `ident` varchar(999) NOT NULL,
  `registration` varchar(999),
  `airline_name` varchar(999),
  `airline_icao` varchar(999),
  `airline_country` varchar(999),
  `airline_type` varchar(999),
  `aircraft_icao` varchar(999),
  `aircraft_shadow` varchar(255),
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
  `waypoints` longtext,
  `altitude` int(11) NOT NULL,
  `heading` int(11) NOT NULL,
  `ground_speed` int(11),
  `squawk` int(11),
  `ModeS` varchar(255),
  `pilot_id`varchar(255),
  `pilot_name`varchar(255),
  `verticalrate` int(11),
  `format_source` varchar(255) DEFAULT NULL,
  `ground` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`spotter_live_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;

ALTER TABLE `spotter_live` ADD INDEX(`flightaware_id`);
ALTER TABLE `spotter_live` ADD INDEX(`date`);
