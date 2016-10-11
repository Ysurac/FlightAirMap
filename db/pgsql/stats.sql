CREATE TABLE stats (
  stats_id serial,
  stats_type varchar(255) NOT NULL,
  cnt integer NOT NULL,
  stats_date timestamp NOT NULL,
  stats_airline varchar(255) DEFAULT '',
  filter_name varchar(255) DEFAULT ''
);

ALTER TABLE stats ADD PRIMARY KEY (stats_id), ADD UNIQUE (stats_type,stats_date,stats_airline,filter_name);
