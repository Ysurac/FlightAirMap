DROP TABLE IF EXISTS metar;
CREATE TABLE metar (
  metar_id int(11) NOT NULL,
  metar_location varchar(10) NOT NULL,
  metar_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  metar text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE metar
  ADD PRIMARY KEY (metar_id), ADD UNIQUE KEY location (metar_location);


ALTER TABLE metar
  MODIFY metar_id int(11) NOT NULL AUTO_INCREMENT;
