CREATE TABLE `stats` (
  `stats_id` int(11) NOT NULL,
  `stats_type` varchar(255) NOT NULL,
  `cnt` int(11) NOT NULL,
  `stats_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `stats`
  ADD PRIMARY KEY (`stats_id`), ADD UNIQUE KEY `stats_type` (`stats_type`,`stats_date`);

ALTER TABLE `stats`
  MODIFY `stats_id` int(11) NOT NULL AUTO_INCREMENT;