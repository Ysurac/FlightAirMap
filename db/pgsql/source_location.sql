CREATE TABLE IF NOT EXISTS source_location (
  id serial,
  source_id integer DEFAULT NULL,
  name varchar(255) DEFAULT NULL,
  latitude float NOT NULL,
  longitude float NOT NULL,
  logo varchar(255) DEFAULT NULL,
  source varchar(255) DEFAULT NULL,
  image varchar(500) DEFAULT NULL,
  image_thumb varchar(500) DEFAULT NULL,
  altitude integer DEFAULT NULL,
  type varchar(255) DEFAULT NULL,
  country varchar(255) DEFAULT NULL,
  city varchar(255) DEFAULT NULL,
  last_seen timestamp NULL DEFAULT NULL,
  location_id integer DEFAULT NULL,
  description text DEFAULT NULL,
  PRIMARY KEY(id)
);
