DROP TABLE IF EXISTS marine_race;
CREATE TABLE marine_race (
  marine_race_id serial,
  race_id integer DEFAULT NULL,
  race_name varchar(255) DEFAULT NULL,
  race_creator varchar(255) DEFAULT NULL,
  race_startdate timestamp NULL DEFAULT NULL,
  race_desc text DEFAULT NULL,
  race_markers text DEFAULT NULL,
  PRIMARY KEY (marine_race_id)
);

