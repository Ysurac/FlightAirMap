DROP TABLE IF EXISTS `schedule`;
CREATE TABLE `schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ident` varchar(999) NOT NULL,
  `departure_airport_icao` varchar(999) NOT NULL,
  `departure_airport_time` varchar(999) NOT NULL,
  `arrival_airport_icao` varchar(999) NOT NULL,
  `arrival_airport_time` varchar(999) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;

ALTER TABLE schedule ADD INDEX identidx (ident);
