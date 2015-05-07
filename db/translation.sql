CREATE TABLE IF NOT EXISTS `translation` (
  `TranslationID` int(11) NOT NULL AUTO_INCREMENT,
  `Reg` varchar(20),
  `Reg_correct` varchar(20),
  `Operator` varchar(20),
  `Operator_correct` varchar(20),
  `Source` varchar(255),
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` datetime DEFAULT NULL,
  PRIMARY KEY (`TranslationID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;
