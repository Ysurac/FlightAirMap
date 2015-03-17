-- phpMyAdmin SQL Dump
-- version 4.1.8
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 29, 2014 at 07:45 PM
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
-- Table structure for table `spotter_image`
--

CREATE TABLE IF NOT EXISTS `spotter_image` (
  `spotter_image_id` int(11) NOT NULL AUTO_INCREMENT,
  `registration` varchar(999) NOT NULL,
  `image_thumbnail` varchar(999) NOT NULL,
  `image` varchar(999) NOT NULL,
  `image_copyright` varchar(255),
  `image_source` varchar(255),
  `image_source_website` varchar(999),
  PRIMARY KEY (`spotter_image_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
