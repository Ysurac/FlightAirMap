CREATE TABLE IF NOT EXISTS `stats_country` (
  `stats_country_id` int(11) NOT NULL,
  `iso2` varchar(5) NOT NULL,
  `iso3` varchar(5) NOT NULL,
  `cnt` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `stats_airline` varchar(255) DEFAULT '',
  `filter_name` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `stats_country` ADD PRIMARY KEY (`stats_country_id`), ADD UNIQUE KEY `iso2` (`iso2`,`stats_airline`,`filter_name`);

ALTER TABLE `stats_country` MODIFY `stats_country_id` int(11) NOT NULL AUTO_INCREMENT;