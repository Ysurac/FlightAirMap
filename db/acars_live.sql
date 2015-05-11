CREATE TABLE IF NOT EXISTS `acars_live` (
  `acars_live_id` int(11) NOT NULL AUTO_INCREMENT,
  `ident` varchar(10) NULL,
  `registration` varchar(10) NULL,
  `label` varchar(10) NULL,
  `block_id` int(11) NULL,
  `msg_no` varchar(10) NULL,
  `message` text NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `decode` text null,
  PRIMARY KEY (`acars_live_id`),
  KEY `acars_live_id` (`acars_live_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
