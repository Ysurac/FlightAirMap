CREATE TABLE aircraft_owner (
  owner_id serial,
  registration varchar(255) NOT NULL,
  base varchar(255) DEFAULT NULL,
  owner varchar(255) NOT NULL,
  date_first_reg timestamp NULL DEFAULT NULL,
  Source varchar(255) NOT NULL
);

ALTER TABLE aircraft_owner ADD PRIMARY KEY (owner_id);
