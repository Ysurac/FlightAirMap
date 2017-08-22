CREATE TABLE IF NOT EXISTS `stats_marine_type` (
  `stats_marine_type_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `type_id` int(11) NOT NULL,
  `cnt` int(11) NOT NULL,
  `filter_name` varchar(255) DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8;

ALTER TABLE `stats_marine_type` ADD PRIMARY KEY (`stats_marine_type_id`), ADD UNIQUE KEY (`type_id`);

ALTER TABLE `stats_marine_type` MODIFY `stats_marine_type_id` int(11) NOT NULL AUTO_INCREMENT;