CREATE TABLE IF NOT EXISTS `atc` (
  `atc_id` int(11) NOT NULL AUTO_INCREMENT,
  `ident` varchar(255) NOT NULL,
  `frequency` varchar(255) NOT NULL,
  `latitude` float NOT NULL,
  `longitude` float NOT NULL,
  `atc_range` float NOT NULL,
  `ivao_id` int(11) NOT NULL,
  `ivao_name` varchar(255) NOT NULL,
  `atc_lastseen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `info` text NOT NULL,
  `type` enum('Observer','Flight Information','Delivery','Tower','Approach','ACC','Departure') DEFAULT NULL,
  `format_source` varchar(255) DEFAULT NULL,
  `source_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`atc_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
