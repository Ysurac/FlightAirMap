CREATE TABLE IF NOT EXISTS notam (
  notam_id serial,
  ref varchar(15) NOT NULL,
  title varchar(255) DEFAULT NULL,
  notam_type varchar(1) NOT NULL,
  fir varchar(4) NOT NULL,
  code varchar(5) NOT NULL,
  rules varchar(255) NOT NULL,
  scope varchar(255) NOT NULL,
  lower_limit integer NOT NULL,
  upper_limit integer NOT NULL,
  center_latitude float NOT NULL,
  center_longitude float NOT NULL,
  radius integer NOT NULL,
  date_begin timestamp NOT NULL,
  date_end timestamp DEFAULT NULL,
  permanent integer NOT NULL,
  notam_text text NOT NULL,
  full_notam text NOT NULL,
  PRIMARY KEY (notam_id)
);

CREATE INDEX ref_idx ON notam (ref);