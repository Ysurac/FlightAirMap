CREATE TABLE stats_pilot (
  stats_pilot_id serial,
  pilot_id integer NOT NULL,
  cnt integer NOT NULL,
  pilot_name varchar(255) DEFAULT NULL,
  stats_airline varchar(255) DEFAULT '',
  filter_name varchar(255) DEFAULT ''
);

ALTER TABLE stats_pilot
  ADD PRIMARY KEY (stats_pilot_id), ADD UNIQUE (pilot_id);
