CREATE TABLE IF NOT EXISTS `stats_marine_country` (
  `stats_marine_country_id` int(11) NOT NULL,
  `iso2` varchar(5) NOT NULL,
  `iso3` varchar(5) NOT NULL,
  `cnt` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `filter_name` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `stats_marine_country` ADD PRIMARY KEY (`stats_marine_country_id`), ADD UNIQUE KEY `iso2` (`iso2`,`filter_name`);

ALTER TABLE `stats_marine_country` MODIFY `stats_marine_country_id` int(11) NOT NULL AUTO_INCREMENT;