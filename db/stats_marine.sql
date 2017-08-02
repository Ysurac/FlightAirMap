CREATE TABLE IF NOT EXISTS `stats_marine` (
  `stats_marine_id` int(11) NOT NULL,
  `stats_type` varchar(255) NOT NULL,
  `cnt` int(11) NOT NULL,
  `marine_date` varchar(255) NOT NULL,
  `filter_name` varchar(255) DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8;

ALTER TABLE `stats_marine`
  ADD PRIMARY KEY (`stats_marine_id`);

ALTER TABLE `stats_marine`
  MODIFY `stats_marine_id` int(11) NOT NULL AUTO_INCREMENT;