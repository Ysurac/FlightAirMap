CREATE TABLE config (
  id serial,
  name varchar(255) NOT NULL,
  value varchar(255) NOT NULL,
  PRIMARY KEY (id)
);

TRUNCATE TABLE config;

INSERT INTO config (id, name, value) VALUES (1, 'schema_version', '55');
