CREATE TABLE `stats_owner` (
  `stats_owner_id` int(11) NOT NULL,
  `owner_name` varchar(255) NOT NULL,
  `cnt` int(11) NOT NULL,
  `stats_airline` varchar(255) DEFAULT '',
  `filter_name` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `stats_owner`
  ADD PRIMARY KEY (`stats_owner_id`), ADD UNIQUE KEY `owner_name` (`owner_name`,`stats_airline`,`filter_name`);

ALTER TABLE `stats_owner`
  MODIFY `stats_owner_id` int(11) NOT NULL AUTO_INCREMENT;