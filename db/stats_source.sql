CREATE TABLE `stats_source` (
  `stats_source_id` int(11) NOT NULL,
  `source_data` text,
  `source_name` varchar(255),
  `stats_type` varchar(255),
  `stats_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `stats_source` ADD PRIMARY KEY (`stats_source_id`), ADD UNIQUE KEY `sourcekey` (`stats_date`,`source_name`,`stats_type`);

ALTER TABLE `stats_source` MODIFY `stats_source_id` int(11) NOT NULL AUTO_INCREMENT;