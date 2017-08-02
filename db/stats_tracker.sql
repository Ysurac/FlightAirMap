CREATE TABLE IF NOT EXISTS `stats_tracker` (
  `stats_tracker_id` int(11) NOT NULL,
  `stats_type` varchar(255) NOT NULL,
  `cnt` int(11) NOT NULL,
  `tracker_date` varchar(255) NOT NULL,
  `filter_name` varchar(255) DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8;

ALTER TABLE `stats_tracker`
  ADD PRIMARY KEY (`stats_tracker_id`);

ALTER TABLE `stats_tracker`
  MODIFY `stats_tracker_id` int(11) NOT NULL AUTO_INCREMENT;