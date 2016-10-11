CREATE TABLE IF NOT EXISTS `stats_callsign` (
  `stats_callsign_id` int(11) NOT NULL,
  `callsign_icao` varchar(10) NOT NULL,
  `cnt` int(11) NOT NULL,
  `airline_icao` varchar(10) DEFAULT NULL,
  `filter_name` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `stats_callsign` ADD PRIMARY KEY (`stats_callsign_id`), ADD UNIQUE KEY `callsign_icao` (`callsign_icao`,`filter_name`);

ALTER TABLE `stats_callsign` MODIFY `stats_callsign_id` int(11) NOT NULL AUTO_INCREMENT;