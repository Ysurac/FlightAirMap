CREATE TABLE IF NOT EXISTS `aircraft_modes` (
  `AircraftID` int(11) NOT NULL AUTO_INCREMENT,
  `FirstCreated` timestamp DEFAULT CURRENT_TIMESTAMP,
  `LastModified` timestamp,
  `ModeS` varchar(6) NOT NULL,
  `ModeSCountry` varchar(24),
  `Registration` varchar(20),
  `ICAOTypeCode` varchar(4),
  `Source` varchar(255),
  PRIMARY KEY (`AircraftID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;

ALTER TABLE `aircraft_modes` ADD INDEX(`ModeS`);