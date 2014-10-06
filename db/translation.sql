DROP TABLE IF EXISTS `translation`;
CREATE TABLE `translation` (
  `TranslationID` int(11) NOT NULL AUTO_INCREMENT,
  `Reg` varchar(20),
  `Reg_correct` varchar(20),
  `Operator` varchar(20),
  `Operator_correct` varchar(20),
  PRIMARY KEY (`TranslationID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;
