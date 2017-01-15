CREATE TABLE `stats_airport` (
  `stats_airport_id` int(11) NOT NULL,
  `airport_icao` varchar(10) NOT NULL,
  `airport_name` varchar(255) NOT NULL,
  `airport_city` varchar(255) DEFAULT NULL,
  `airport_country` varchar(255) DEFAULT NULL,
  `departure` int(11) NOT NULL DEFAULT '0',
  `arrival` int(11) NOT NULL DEFAULT '0',
  `stats_type` varchar(50) NOT NULL DEFAULT 'yearly',
  `date` date DEFAULT NULL,
  `stats_airline` varchar(255) DEFAULT '',
  `filter_name` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `stats_airport` ADD PRIMARY KEY (`stats_airport_id`), ADD UNIQUE KEY `airport_icao` (`airport_icao`,`stats_type`,`date`,`stats_airline`,`filter_name`);

ALTER TABLE `stats_airport` MODIFY `stats_airport_id` int(11) NOT NULL AUTO_INCREMENT;