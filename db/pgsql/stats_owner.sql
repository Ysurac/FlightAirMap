CREATE TABLE stats_owner (
  stats_owner_id serial,
  owner_name varchar(255) NOT NULL,
  cnt integer NOT NULL,
  stats_airline varchar(255) DEFAULT '',
  filter_name varchar(255) DEFAULT ''
);

ALTER TABLE stats_owner
  ADD PRIMARY KEY (stats_owner_id), ADD UNIQUE (owner_name,stats_airline,filter_name);
