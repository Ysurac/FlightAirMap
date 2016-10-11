CREATE TABLE `stats_airline` (
  `stats_airline_id` int(11) NOT NULL,
  `airline_icao` varchar(10) NOT NULL,
  `cnt` int(11) NOT NULL,
  `airline_name` varchar(255) DEFAULT NULL,
  `filter_name` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `stats_airline`
  ADD PRIMARY KEY (`stats_airline_id`), ADD UNIQUE KEY `airline_icao` (`airline_icao`,`filter_name`);

ALTER TABLE `stats_airline`
  MODIFY `stats_airline_id` int(11) NOT NULL AUTO_INCREMENT;