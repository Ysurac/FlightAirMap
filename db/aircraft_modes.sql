SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

-- --------------------------------------------------------

--
-- Table structure for table `aircraft_modes`
--

CREATE TABLE IF NOT EXISTS `aircraft_modes` (
  `AircraftID` int(11) NOT NULL AUTO_INCREMENT,
  `FirstCreated` datetime,
  `LastModified` datetime,
  `ModeS` varchar(6) NOT NULL,
  `ModeSCountry` varchar(24),
  `Registration` varchar(20),
  `ICAOTypeCode` varchar(4),
  `Source` varchar(255),
  PRIMARY KEY (`AircraftID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
