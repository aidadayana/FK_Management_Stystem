-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2026 at 10:36 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fk_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `club`
--

CREATE TABLE `club` (
  `ClubID` varchar(50) NOT NULL,
  `ClubName` varchar(120) NOT NULL,
  `ClubDesc` text NOT NULL,
  `ClubAdvisor` varchar(100) NOT NULL,
  `ClubStatus` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `club`
--

INSERT INTO `club` (`ClubID`, `ClubName`, `ClubDesc`, `ClubAdvisor`, `ClubStatus`) VALUES
('C003', '3D modelling', 'Design 3D Modelling', 'Aisy', 'Active'),
('E002', 'HCI', 'HumanComputer', 'Audi', 'Inactive');

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

CREATE TABLE `event` (
  `EventID` varchar(50) NOT NULL,
  `Title` varchar(200) NOT NULL,
  `Description` varchar(250) NOT NULL,
  `EventDate` date NOT NULL,
  `EventTime` time NOT NULL,
  `Venue` varchar(250) NOT NULL,
  `MaxParicipants` int(11) NOT NULL,
  `ClubID` varchar(50) NOT NULL,
  `EventStatus` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_registration`
--

CREATE TABLE `event_registration` (
  `RegistrationID` varchar(50) NOT NULL,
  `UserID` varchar(50) NOT NULL,
  `ClubID` varchar(50) NOT NULL,
  `RegistrationDate` date NOT NULL,
  `RegistrationStatus` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `membership`
--

CREATE TABLE `membership` (
  `MemberID` varchar(50) NOT NULL,
  `UserID` varchar(50) NOT NULL,
  `ClubID` varchar(50) NOT NULL,
  `MemberRoleID` varchar(50) NOT NULL,
  `JoinDate` date NOT NULL,
  `MemberStatus` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `membership_role`
--

CREATE TABLE `membership_role` (
  `MemberRoleID` varchar(50) NOT NULL,
  `MemberRoleName` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership_role`
--

INSERT INTO `membership_role` (`MemberRoleID`, `MemberRoleName`) VALUES
('R001', 'President'),
('R002', 'Secretary'),
('R003', 'Treasurer'),
('R004', 'Committee Member');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `UserID` varchar(50) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(50) NOT NULL,
  `RoleID` varchar(50) NOT NULL,
  `UserStatus` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`UserID`, `Name`, `Email`, `Password`, `RoleID`, `UserStatus`) VALUES
('U001', 'Admin User', 'admin@gmail.com', '123456', 'R01', 'Active'),
('U002', 'Ali Ahmad', 'ali@gmail.com', '123456', 'R02', 'Active'),
('U003', 'Siti Aminah', 'siti@gmail.com', '123456', 'R03', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `user_role`
--

CREATE TABLE `user_role` (
  `RoleID` varchar(50) NOT NULL,
  `RoleName` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_role`
--

INSERT INTO `user_role` (`RoleID`, `RoleName`) VALUES
('R01', 'Admin'),
('R02', 'Student'),
('R03', 'Committee');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `club`
--
ALTER TABLE `club`
  ADD PRIMARY KEY (`ClubID`);

--
-- Indexes for table `event`
--
ALTER TABLE `event`
  ADD PRIMARY KEY (`EventID`);

--
-- Indexes for table `event_registration`
--
ALTER TABLE `event_registration`
  ADD PRIMARY KEY (`RegistrationID`);

--
-- Indexes for table `membership_role`
--
ALTER TABLE `membership_role`
  ADD PRIMARY KEY (`MemberRoleID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`UserID`);

--
-- Indexes for table `user_role`
--
ALTER TABLE `user_role`
  ADD PRIMARY KEY (`RoleID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
