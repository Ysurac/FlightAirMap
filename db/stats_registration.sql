CREATE TABLE IF NOT EXISTS `stats_registration` (
  `stats_registration_id` int(11) NOT NULL,
  `registration` varchar(10) NOT NULL,
  `cnt` int(11) NOT NULL,
  `aircraft_icao` varchar(10) DEFAULT NULL,
  `stats_airline` varchar(255) DEFAULT '',
  `filter_name` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `stats_registration` ADD PRIMARY KEY (`stats_registration_id`), ADD UNIQUE KEY `registration` (`registration`,`stats_airline`,`filter_name`);

ALTER TABLE `stats_registration` MODIFY `stats_registration_id` int(11) NOT NULL AUTO_INCREMENT;