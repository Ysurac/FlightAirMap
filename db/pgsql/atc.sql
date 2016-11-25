DROP TABLE IF EXISTS atc;
CREATE TABLE atc (
  atc_id serial,
  ident varchar(255) NOT NULL,
  frequency varchar(255) NOT NULL,
  latitude float NOT NULL,
  longitude float NOT NULL,
  atc_range float NOT NULL,
  ivao_id integer NOT NULL,
  ivao_name varchar(255) NOT NULL,
  atc_lastseen timestamp NOT NULL,
  info text NOT NULL,
  type varchar(255) DEFAULT NULL,
  format_source varchar(255) DEFAULT NULL,
  source_name varchar(255) DEFAULT NULL,
  PRIMARY KEY (atc_id)
);
