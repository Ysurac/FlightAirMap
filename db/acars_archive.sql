CREATE TABLE IF NOT EXISTS `acars_archive` (
  `acars_archive_id` int(11) NOT NULL AUTO_INCREMENT,
  `ident` varchar(10) NOT NULL,
  `registration` varchar(10) NOT NULL,
  `label` varchar(10) NOT NULL,
  `block_id` int(11) NOT NULL,
  `msg_no` varchar(10) NOT NULL,
  `message` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `decode` text,
  PRIMARY KEY (`acars_archive_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;
