CREATE TABLE faamfr (
    faamfr_id serial,
    icao VARCHAR(10) NOT NULL,
    mfr VARCHAR(255) NOT NULL,
    PRIMARY KEY (faamfr_id),
    UNIQUE (mfr)
);