CREATE TABLE IF NOT EXISTS `stats_flight` (
  `stats_flight_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `cnt` int(11) NOT NULL,
  `flight_date` varchar(255) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8;

ALTER TABLE `stats_flight`
  ADD PRIMARY KEY (`stats_flight_id`);

ALTER TABLE `stats_flight`
  MODIFY `stats_flight_id` int(11) NOT NULL AUTO_INCREMENT;