CREATE TABLE `accidents` (
    `accidents_id` int(11) NOT NULL AUTO_INCREMENT,
    `registration` varchar(20) NOT NULL,
    `date` timestamp NOT NULL,
    `url` varchar(255) DEFAULT NULL,
    `country` varchar(255) DEFAULT NULL,
    `place` varchar(255) DEFAULT NULL,
    `title` text,
    `fatalities` int(11),
    `latitude` float,
    `longitude` float,
    `type` varchar(255) DEFAULT NULL,
    `source` varchar(255) DEFAULT NULL,
    `ident` varchar(255) DEFAULT NULL,
    `aircraft_manufacturer` varchar(255) DEFAULT NULL,
    `aircraft_name` varchar(255) DEFAULT NULL,
    `airline_name` varchar(255) DEFAULT NULL,
    `airline_icao` varchar(10) DEFAULT NULL,
    PRIMARY KEY (`accidents_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;
CREATE INDEX `registration_idx` ON `accidents` (`registration`);
CREATE INDEX `rdts` ON `accidents` (`registration`,`date`,`type`,`source`);
CREATE INDEX `type` ON `accidents` (`type`,`date`);