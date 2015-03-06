-- phpMyAdmin SQL Dump
-- version 4.1.8
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 29, 2014 at 08:21 PM
-- Server version: 5.5.36-cll
-- PHP Version: 5.4.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `barriesp_spotter`
--

-- --------------------------------------------------------

--
-- Table structure for table `spotter_output`
--

CREATE TABLE IF NOT EXISTS `spotter_output` (
  `spotter_id` int(11) NOT NULL AUTO_INCREMENT,
  `flightaware_id` varchar(999) NOT NULL,
  `ident` varchar(999) NOT NULL,
  `registration` varchar(999),
  `airline_name` varchar(999) NOT NULL,
  `airline_icao` varchar(999) NOT NULL,
  `airline_country` varchar(999) NOT NULL,
  `airline_type` varchar(999) NOT NULL,
  `aircraft_icao` varchar(999) NOT NULL,
  `aircraft_name` varchar(999) NOT NULL,
  `aircraft_manufacturer` varchar(999) NOT NULL,
  `departure_airport_icao` varchar(999) NOT NULL,
  `departure_airport_name` varchar(999) NOT NULL,
  `departure_airport_city` varchar(999) NOT NULL,
  `departure_airport_country` varchar(999) NOT NULL,
  `departure_airport_time` varchar(20),
  `arrival_airport_icao` varchar(999) NOT NULL,
  `arrival_airport_name` varchar(999) NOT NULL,
  `arrival_airport_city` varchar(999) NOT NULL,
  `arrival_airport_country` varchar(999) NOT NULL,
  `arrival_airport_time` varchar(20),
  `route_stop` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `latitude` float NOT NULL,
  `longitude` float NOT NULL,
  `waypoints` longtext NOT NULL,
  `altitude` int(11) NOT NULL,
  `heading` int(11) NOT NULL,
  `ground_speed` int(11) NOT NULL,
  `highlight` longtext NOT NULL,
  `squawk` int(11),
  `ModeS` varchar(255) NOT NULL
  PRIMARY KEY (`spotter_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
