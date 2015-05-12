CREATE TABLE IF NOT EXISTS `spotter_image` (
  `spotter_image_id` int(11) NOT NULL AUTO_INCREMENT,
  `registration` varchar(999) NOT NULL,
  `image_thumbnail` varchar(999) NOT NULL,
  `image` varchar(999) NOT NULL,
  `image_copyright` varchar(255),
  `image_source` varchar(255),
  `image_source_website` varchar(999),
  PRIMARY KEY (`spotter_image_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;

