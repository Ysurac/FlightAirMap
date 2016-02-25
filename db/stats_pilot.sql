CREATE TABLE `stats_pilot` (
  `stats_pilot_id` int(11) NOT NULL,
  `pilot_id` int(11) NOT NULL,
  `cnt` int(11) NOT NULL,
  `pilot_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `stats_pilot`
  ADD PRIMARY KEY (`stats_pilot_id`), ADD UNIQUE KEY `pilot_id` (`pilot_id`);

ALTER TABLE `stats_pilot`
  MODIFY `stats_pilot_id` int(11) NOT NULL AUTO_INCREMENT;