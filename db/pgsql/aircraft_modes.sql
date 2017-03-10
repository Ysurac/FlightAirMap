CREATE TABLE aircraft_modes (
  AircraftID serial,
  FirstCreated timestamp DEFAULT CURRENT_TIMESTAMP,
  LastModified timestamp,
  ModeS varchar(6) NOT NULL,
  ModeSCountry varchar(24),
  Registration varchar(20),
  ICAOTypeCode varchar(4),
  type_flight varchar(50),
  Source varchar(255),
  source_type varchar(255) DEFAULT 'modes',
  PRIMARY KEY (AircraftID)
);
