CREATE TABLE IF NOT EXISTS `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

TRUNCATE TABLE `config`;

INSERT INTO `config` (`id`, `name`, `value`) VALUES (1, 'schema_version', '55');
