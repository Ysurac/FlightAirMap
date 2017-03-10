DROP TABLE IF EXISTS tle;
CREATE TABLE tle (
  tle_id int(11) NOT NULL,
  tle_name varchar(255) NOT NULL,
  tle_tle1 varchar(255) NOT NULL,
  tle_tle2 varchar(255) NOT NULL,
  tle_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  tle_source varchar(255),
  tle_type varchar(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE tle ADD PRIMARY KEY (tle_id);


ALTER TABLE tle MODIFY tle_id int(11) NOT NULL AUTO_INCREMENT;
