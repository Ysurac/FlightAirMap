CREATE TABLE IF NOT EXISTS `aircraft_modes` (
  `AircraftID` int(11) NOT NULL AUTO_INCREMENT,
  `FirstCreated` timestamp DEFAULT CURRENT_TIMESTAMP,
  `LastModified` timestamp,
  `ModeS` varchar(6) NOT NULL,
  `ModeSCountry` varchar(24),
  `Registration` varchar(20),
  `ICAOTypeCode` varchar(4),
  `type_flight` varchar(50),
  `Source` varchar(255),
  `source_type` varchar(255) DEFAULT 'modes',
  PRIMARY KEY (`AircraftID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;

ALTER TABLE `aircraft_modes` ADD INDEX(`ModeS`);