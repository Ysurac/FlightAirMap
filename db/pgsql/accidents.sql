CREATE TABLE accidents (
    accidents_id serial,
    registration character varying(20) NOT NULL,
    date timestamp without time zone NOT NULL,
    url character varying(255) DEFAULT NULL::character varying,
    country character varying(255) DEFAULT NULL::character varying,
    place character varying(255) DEFAULT NULL::character varying,
    title text,
    fatalities integer,
    latitude double precision,
    longitude double precision,
    type character varying(255) DEFAULT NULL::character varying,
    source character varying(255) DEFAULT NULL::character varying,
    ident character varying(255) DEFAULT NULL::character varying,
    aircraft_manufacturer character varying(255) DEFAULT NULL::character varying,
    aircraft_name character varying(255) DEFAULT NULL::character varying,
    airline_name character varying(255) DEFAULT NULL::character varying,
    airline_icao character varying(10) DEFAULT NULL::character varying,
    PRIMARY KEY (accidents_id)
);
CREATE INDEX registration_idx ON accidents USING btree(registration);
CREATE INDEX rdts ON accidents USING btree(registration,date,type,source);
CREATE INDEX type ON accidents USING btree(type,date);