DROP TABLE IF EXISTS `routes`;
CREATE TABLE `routes` (
  `RouteID` int(11) NOT NULL AUTO_INCREMENT,
  `CallSign` varchar(8),
  `Operator_ICAO` varchar(4),
  `FromAirport_ICAO` varchar(4),
  `FromAirport_Time` varchar(10) NULL,
  `ToAirport_ICAO` varchar(4),
  `ToAirport_Time` varchar(10) NULL,
  `RouteStop` varchar(255),
  `Source` varchar(255) NULL,
  `date_added` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` DATETIME NULL,
  `date_lastseen` DATETIME NULL,
  PRIMARY KEY (`RouteID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;
