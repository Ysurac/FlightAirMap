CREATE TABLE stats_pilot (
  stats_pilot_id serial,
  pilot_id varchar(255) NOT NULL,
  cnt integer NOT NULL,
  pilot_name varchar(255) DEFAULT NULL,
  stats_airline varchar(255) DEFAULT '',
  filter_name varchar(255) DEFAULT '',
  format_source varchar(255) DEFAULT ''
);

ALTER TABLE stats_pilot
  ADD PRIMARY KEY (stats_pilot_id), ADD UNIQUE (pilot_id,stats_airline,filter_name,format_source);
