CREATE TABLE marine_identity (
  marid serial,
  mmsi varchar(255) DEFAULT NULL,
  imo varchar(20) DEFAULT NULL,
  call_sign varchar(255) DEFAULT NULL,
  ship_name varchar(255) DEFAULT NULL,
  length float DEFAULT NULL,
  gross_tonnage float DEFAULT NULL,
  dead_weight float DEFAULT NULL,
  width float DEFAULT NULL,
  country varchar(255) DEFAULT NULL,
  engine_power integer DEFAULT NULL,
  type varchar(255) DEFAULT NULL,
  PRIMARY KEY (marid)
);

