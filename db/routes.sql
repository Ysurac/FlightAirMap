DROP TABLE IF EXISTS `routes`;
CREATE TABLE `routes` (
  `RouteID` int(11) NOT NULL AUTO_INCREMENT,
  `CallSign` varchar(8),
  `Operator_ICAO` varchar(4),
  `FromAirport_ICAO` varchar(4),
  `ToAirport_ICAO` varchar(4),
  `RouteStop` varchar(255),
  PRIMARY KEY (`RouteID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;
