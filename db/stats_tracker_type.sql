CREATE TABLE IF NOT EXISTS `stats_tracker_type` (
  `stats_tracker_type_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `cnt` int(11) NOT NULL,
  `filter_name` varchar(255) DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8;

ALTER TABLE `stats_tracker_type` ADD PRIMARY KEY (`stats_tracker_type_id`), ADD UNIQUE KEY (`type`);

ALTER TABLE `stats_tracker_type` MODIFY `stats_tracker_type_id` int(11) NOT NULL AUTO_INCREMENT;