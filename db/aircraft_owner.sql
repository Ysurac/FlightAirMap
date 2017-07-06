CREATE TABLE IF NOT EXISTS `aircraft_owner` (
  `owner_id` int(11) NOT NULL AUTO_INCREMENT,
  `registration` varchar(20) NOT NULL,
  `base` varchar(255) DEFAULT NULL,
  `owner` varchar(255) NOT NULL,
  `date_first_reg` timestamp NULL DEFAULT NULL,
  `Source` varchar(255) NOT NULL,
  PRIMARY KEY (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE aircraft_owner ADD INDEX(registration);
ALTER TABLE aircraft_owner ADD INDEX(owner);
