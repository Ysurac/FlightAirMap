CREATE TABLE `marine_identity` (
  `marid` int(11) NOT NULL AUTO_INCREMENT,
  `mmsi` varchar(255) DEFAULT NULL,
  `imo` varchar(20) DEFAULT NULL,
  `call_sign` varchar(255) DEFAULT NULL,
  `ship_name` varchar(255) DEFAULT NULL,
  `length` float DEFAULT NULL,
  `gross_tonnage` float DEFAULT NULL,
  `dead_weight` float DEFAULT NULL,
  `width` float DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `engine_power` int(11) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`marid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

