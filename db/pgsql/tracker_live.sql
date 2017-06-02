CREATE TABLE tracker_live (
  tracker_live_id serial,
  famtrackid varchar(255) NOT NULL,
  ident varchar(255) DEFAULT NULL,
  latitude float NOT NULL,
  longitude float NOT NULL,
  heading integer DEFAULT NULL,
  speed integer DEFAULT NULL,
  altitude float DEFAULT NULL,
  comment varchar(255) DEFAULT NULL,
  over_country varchar(255) DEFAULT NULL,
  type varchar(255) DEFAULT NULL,
  departure_code varchar(10) DEFAULT NULL,
  departure_name varchar(255) DEFAULT NULL,
  departure_country varchar(255) DEFAULT NULL,
  arrival_code varchar(10) DEFAULT NULL,
  arrival_name varchar(255) DEFAULT NULL,
  arrival_country varchar(255) DEFAULT NULL,
  date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  format_source varchar(255) DEFAULT NULL,
  source_name varchar(255) DEFAULT NULL
);

ALTER TABLE tracker_live ADD PRIMARY KEY (tracker_live_id);
