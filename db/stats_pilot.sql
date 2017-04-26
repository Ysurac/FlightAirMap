CREATE TABLE `stats_pilot` (
  `stats_pilot_id` int(11) NOT NULL,
  `pilot_id` varchar(255) NOT NULL,
  `cnt` int(11) NOT NULL,
  `pilot_name` varchar(255) DEFAULT NULL,
  `stats_airline` varchar(255) DEFAULT '',
  `filter_name` varchar(255) DEFAULT '',
  `format_source` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `stats_pilot`
  ADD PRIMARY KEY (`stats_pilot_id`), ADD UNIQUE KEY `pilot_id` (`pilot_id`,`stats_airline`,`filter_name`,`format_source`);

ALTER TABLE `stats_pilot`
  MODIFY `stats_pilot_id` int(11) NOT NULL AUTO_INCREMENT;