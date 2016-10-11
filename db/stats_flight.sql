CREATE TABLE IF NOT EXISTS `stats_flight` (
  `stats_flight_id` int(11) NOT NULL,
  `stats_type` varchar(255) NOT NULL,
  `cnt` int(11) NOT NULL,
  `flight_date` varchar(255) NOT NULL,
  `stats_airline` varchar(255) DEFAULT '',
  `filter_name` varchar(255) DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8;

ALTER TABLE `stats_flight`
  ADD PRIMARY KEY (`stats_flight_id`);

ALTER TABLE `stats_flight`
  MODIFY `stats_flight_id` int(11) NOT NULL AUTO_INCREMENT;