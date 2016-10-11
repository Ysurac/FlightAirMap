CREATE TABLE `stats_aircraft` (
  `stats_aircraft_id` int(11) NOT NULL,
  `aircraft_icao` varchar(10) NOT NULL,
  `cnt` int(11) NOT NULL,
  `aircraft_name` varchar(255) DEFAULT NULL,
  `aircraft_manufacturer` varchar(255) DEFAULT NULL,
  `stats_airline` varchar(255) DEFAULT '',
  `filter_name` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `stats_aircraft`
  ADD PRIMARY KEY (`stats_aircraft_id`), ADD UNIQUE KEY `aircraft_icao` (`aircraft_icao`,`stats_airline`,`filter_name`);

ALTER TABLE `stats_aircraft`
  MODIFY `stats_aircraft_id` int(11) NOT NULL AUTO_INCREMENT;