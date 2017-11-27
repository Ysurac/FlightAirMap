DROP TABLE IF EXISTS `marine_race`;
CREATE TABLE `marine_race` (
  `marine_race_id` int(11) NOT NULL AUTO_INCREMENT,
  `race_id` int(11) DEFAULT NULL,
  `race_name` varchar(255) DEFAULT NULL,
  `race_creator` varchar(255) DEFAULT NULL,
  `race_startdate` timestamp NULL DEFAULT 0,
  `race_desc` text DEFAULT NULL,
  `race_markers` text DEFAULT NULL,
  PRIMARY KEY (`marine_race_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;

