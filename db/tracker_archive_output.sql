CREATE TABLE `tracker_archive_output` (
  `tracker_archive_output_id` int(11) NOT NULL,
  `famtrackid` varchar(255) NOT NULL,
  `ident` varchar(255) DEFAULT NULL,
  `latitude` float NOT NULL,
  `longitude` float NOT NULL,
  `heading` int(11) DEFAULT NULL,
  `ground_speed` int(11) DEFAULT NULL,
  `altitude` float DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `departure_code` varchar(10) DEFAULT NULL,
  `departure_name` varchar(255) DEFAULT NULL,
  `departure_country` varchar(255) DEFAULT NULL,
  `arrival_code` varchar(10) DEFAULT NULL,
  `arrival_name` varchar(255) DEFAULT NULL,
  `arrival_country` varchar(255) DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `format_source` varchar(255) DEFAULT NULL,
  `source_name` varchar(255) DEFAULT NULL,
  `last_seen` timestamp NULL DEFAULT NULL,
  `last_latitude` float DEFAULT NULL,
  `last_longitude` float DEFAULT NULL,
  `last_altitude` float DEFAULT NULL,
  `last_ground_speed` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `tracker_archive_output` ADD PRIMARY KEY (`tracker_archive_output_id`);
ALTER TABLE `tracker_archive_output` MODIFY `tracker_archive_output_id` int(11) NOT NULL AUTO_INCREMENT;