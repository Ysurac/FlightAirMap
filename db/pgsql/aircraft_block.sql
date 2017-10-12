CREATE TABLE aircraft_block (
  block_id serial,
  callsign varchar(20),
  Source varchar(255),
  PRIMARY KEY (block_id)
);
CREATE INDEX callsign_block_idx ON aircraft_block USING btree(callsign);