CREATE TABLE IF NOT EXISTS `config` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `config` (`id`, `name`, `value`) VALUES (1, 'schema_version', '2');
ALTER TABLE `config` ADD PRIMARY KEY (`id`);
ALTER TABLE `config` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
