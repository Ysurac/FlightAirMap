CREATE TABLE stats_pilot (
  stats_pilot_id serial,
  pilot_id integer NOT NULL,
  cnt integer NOT NULL,
  pilot_name varchar(255) DEFAULT NULL
);

ALTER TABLE stats_pilot
  ADD PRIMARY KEY (stats_pilot_id), ADD UNIQUE KEY pilot_id (pilot_id);
