DROP TABLE IF EXISTS `waypoints`;
CREATE TABLE `waypoints` (
  `waypoints_id` int(11) NOT NULL AUTO_INCREMENT,
  `ident` varchar(255),
  `latitude` float,
  `longitude` float,
  `control` varchar(10),
  `usage` varchar(255),
  PRIMARY KEY (`waypoints_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;
