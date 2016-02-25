CREATE TABLE stats_owner (
  stats_owner_id serial,
  owner_name varchar(255) NOT NULL,
  cnt integer NOT NULL
);

ALTER TABLE stats_owner
  ADD PRIMARY KEY (stats_owner_id), ADD UNIQUE KEY owner_name (owner_name);
