CREATE TABLE IF NOT EXISTS `source_location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `latitude` float NOT NULL,
  `longitude` float NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL,
  `image_thumb` varchar(500) DEFAULT NULL,
  `altitude` int(11) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `last_seen` timestamp NULL DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY(id)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
