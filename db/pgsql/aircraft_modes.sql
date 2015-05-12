CREATE TABLE aircraft_modes (
  AircraftID serial,
  FirstCreated timestamp DEFAULT CURRENT_TIMESTAMP,
  LastModified timestamp,
  ModeS varchar(6) NOT NULL,
  ModeSCountry varchar(24),
  Registration varchar(20),
  ICAOTypeCode varchar(4),
  Source varchar(255),
  PRIMARY KEY (AircraftID)
);
