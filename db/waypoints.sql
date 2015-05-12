CREATE TABLE IF NOT EXISTS `waypoints` (
  `waypoints_id` int(11) NOT NULL AUTO_INCREMENT,
  `name_begin` varchar(255),
  `latitude_begin` float,
  `longitude_begin` float,
  `name_end` varchar(255),
  `latitude_end` float,
  `longitude_end` float,
  `high` int(2),
  `base` float,
  `top` float,
  `segment_name` varchar(255),
  PRIMARY KEY (`waypoints_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;
