CREATE TABLE waypoints (
  waypoints_id serial,
  name_begin varchar(255),
  latitude_begin float,
  longitude_begin float,
  name_end varchar(255),
  latitude_end float,
  longitude_end float,
  high integer,
  base float,
  top float,
  segment_name varchar(255),
  PRIMARY KEY (waypoints_id)
);
