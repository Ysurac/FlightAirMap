CREATE TABLE `stats_aircraft` (
  `stats_aircraft_id` int(11) NOT NULL,
  `aircraft_icao` varchar(10) NOT NULL,
  `cnt` int(11) NOT NULL,
  `aircraft_name` varchar(255) DEFAULT NULL,
  `aircraft_manufacturer` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `stats_aircraft`
  ADD PRIMARY KEY (`stats_aircraft_id`), ADD UNIQUE KEY `aircraft_icao` (`aircraft_icao`);

ALTER TABLE `stats_aircraft`
  MODIFY `stats_aircraft_id` int(11) NOT NULL AUTO_INCREMENT;