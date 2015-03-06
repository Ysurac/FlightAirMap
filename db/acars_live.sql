CREATE TABLE IF NOT EXISTS `acars_live` (
  `acars_live_id` int(11) NOT NULL AUTO_INCREMENT,
  `ident` varchar(10) NOT NULL,
  `registration` varchar(10) NOT NULL,
  `label` varchar(10) NOT NULL,
  `block_id` int(11) NOT NULL,
  `msg_no` varchar(10) NOT NULL,
  `message` text NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`acars_live_id`),
  KEY `acars_live_id` (`acars_live_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
