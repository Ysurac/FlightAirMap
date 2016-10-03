DROP TABLE IF EXISTS tle;
CREATE TABLE tle (
  tle_id serial,
  tle_name varchar(255) NOT NULL,
  tle_tle1 varchar(255),
  tle_tle2 varchar(255),
  tle_date timestamp DEFAULT CURRENT_TIMESTAMP,
  tle_source varchar(255),
  tle_type varchar(255)
);

ALTER TABLE tle ADD PRIMARY KEY (tle_id);
