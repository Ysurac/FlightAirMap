CREATE TABLE IF NOT EXISTS `marine_image` (
  `marine_image_id` int(11) NOT NULL AUTO_INCREMENT,
  `mmsi` varchar(20) NOT NULL,
  `imo` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `image_thumbnail` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `image_copyright` varchar(255),
  `image_source` varchar(255),
  `image_source_website` varchar(255),
  PRIMARY KEY (`marine_image_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;

