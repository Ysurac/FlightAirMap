CREATE TABLE aircraft_block (
  block_id int(11) NOT NULL AUTO_INCREMENT,
  callsign varchar(20),
  Source varchar(255),
  PRIMARY KEY (block_id)
);
ALTER TABLE aircraft_block ADD INDEX(callsign);