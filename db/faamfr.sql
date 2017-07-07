CREATE TABLE `faamfr` ( 
    `faamfr_id` INT NOT NULL AUTO_INCREMENT,
    `icao` VARCHAR(10) NOT NULL,
    `mfr` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`faamfr_id`),
    UNIQUE (`mfr`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;
