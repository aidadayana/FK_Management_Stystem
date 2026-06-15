-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 15, 2026 at 07:14 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `AttendanceID` int(11) NOT NULL,
  `UserID` varchar(50) NOT NULL,
  `EventID` int(11) NOT NULL,
  `AttendanceStatus` varchar(50) NOT NULL,
  `CheckInTime` datetime NOT NULL DEFAULT current_timestamp(),
  `PointEarned` int(11) NOT NULL,
  `IsVolunteer` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
('C003', '3D modelling', 'Design 3D Modelling', 'Aisy', 'Inactive'),
('E002', 'HCI', 'HumanComputer', 'Audi', 'Active'),
('E010', 'DNS', 'Data Network and Security', 'Ahmad', 'Active');

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
  `MaxParticipants` int(11) NOT NULL,
  `ClubID` varchar(50) NOT NULL,
  `EventStatus` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event`
--

INSERT INTO `event` (`EventID`, `Title`, `Description`, `EventDate`, `EventTime`, `Venue`, `MaxParticipants`, `ClubID`, `EventStatus`) VALUES
('EV001', 'HCI Workshop 2026', 'Hands-on workshop exploring human-computer interaction principles and usability testing.', '2026-07-15', '09:00:00', 'Block A, Room 301', 50, 'E002', 'Upcoming'),
('EV002', 'DNS Security Talk', 'A technical seminar on network security, DNS protocols, and ethical hacking basics.', '2026-07-22', '14:00:00', 'Auditorium 1', 100, 'E010', 'Upcoming'),
('EV003', 'HCI Design Sprint', 'Collaborative design sprint — bring your ideas and build a prototype in 4 hours.', '2026-05-10', '10:00:00', 'Lab 2, Block B', 30, 'E002', 'Completed'),
('EV20260607133031', 'Bubble Run 2026', 'fun run', '2026-06-08', '07:30:00', 'UMPSA PEKAN', 100, 'E002', 'Upcoming'),
('EV20260615065553', 'Test', 'Test', '2026-06-16', '14:00:00', 'Library', 10, 'E002', 'Upcoming'),
('EV20260615070601', 'Test', 'Test', '2026-06-16', '13:05:00', 'Library', 0, 'E002', 'Upcoming');

-- --------------------------------------------------------

--
-- Table structure for table `event_registration`
--

CREATE TABLE `event_registration` (
  `RegistrationID` varchar(50) NOT NULL,
  `EventID` varchar(50) NOT NULL,
  `UserID` varchar(50) NOT NULL,
  `StudentName` varchar(100) NOT NULL DEFAULT '',
  `ClubID` varchar(50) NOT NULL,
  `RegistrationDate` date NOT NULL,
  `RegStatus` varchar(50) NOT NULL,
  `RegisteredAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_registration`
--

INSERT INTO `event_registration` (`RegistrationID`, `EventID`, `UserID`, `StudentName`, `ClubID`, `RegistrationDate`, `RegStatus`, `RegisteredAt`) VALUES
('REG6A2F85FE7F8F4', 'EV001', 'U002', 'Ali Ahmad', 'E002', '2026-06-15', 'Cancelled', '2026-06-15 12:56:30'),
('REG6A2F87D2357EE', 'EV002', 'U002', 'Ali Ahmad', 'E010', '2026-06-15', 'Cancelled', '2026-06-15 13:04:18');

-- --------------------------------------------------------

--
-- Table structure for table `membership`
--

CREATE TABLE `membership` (
  `MemberID` int(11) NOT NULL,
  `UserID` varchar(50) NOT NULL,
  `ClubID` varchar(50) NOT NULL,
  `MemberRoleID` varchar(50) NOT NULL,
  `JoinDate` date NOT NULL,
  `MemberStatus` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership`
--

INSERT INTO `membership` (`MemberID`, `UserID`, `ClubID`, `MemberRoleID`, `JoinDate`, `MemberStatus`) VALUES
(1, 'U003', 'E002', 'R001', '2026-05-17', 'Active'),
(3, 'U004', 'E010', 'R002', '2026-05-17', 'Active'),
(4, 'U002', 'E010', 'R006', '2026-06-15', 'Active');

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
('R002', 'Vice President'),
('R003', 'Secretary'),
('R004', 'Treasurer'),
('R005', 'Committee Member'),
('R006', 'General Member');

-- --------------------------------------------------------

--
-- Table structure for table `participation_summary`
--

CREATE TABLE `participation_summary` (
  `SummaryID` int(11) NOT NULL,
  `UserID` varchar(50) NOT NULL,
  `RecognitionID` int(11) NOT NULL,
  `Semester` varchar(50) NOT NULL,
  `TotalPoints` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recognition_level`
--

CREATE TABLE `recognition_level` (
  `RecognitionID` int(11) NOT NULL,
  `MinPoints` int(11) NOT NULL,
  `MaxPoints` int(11) NOT NULL,
  `LevelName` varchar(100) NOT NULL,
  `Enforcement` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
('U003', 'Siti Aminah', 'siti@gmail.com', '123456', 'R03', 'Active'),
('U004', 'Nur Alia', 'alia@gmail.com', 'abc123', 'R02', 'Active');

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

-- --------------------------------------------------------

--
-- Table structure for table `waitlist`
--

CREATE TABLE `waitlist` (
  `WaitlistID` varchar(50) NOT NULL,
  `EventID` varchar(50) NOT NULL,
  `UserID` varchar(50) NOT NULL,
  `Queue` int(11) NOT NULL,
  `WaitJoinDate` date NOT NULL,
  `WaitlistStatus` varchar(50) NOT NULL DEFAULT 'Waiting'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`AttendanceID`);

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
-- Indexes for table `membership`
--
ALTER TABLE `membership`
  ADD PRIMARY KEY (`MemberID`);

--
-- Indexes for table `membership_role`
--
ALTER TABLE `membership_role`
  ADD PRIMARY KEY (`MemberRoleID`);

--
-- Indexes for table `participation_summary`
--
ALTER TABLE `participation_summary`
  ADD PRIMARY KEY (`SummaryID`);

--
-- Indexes for table `recognition_level`
--
ALTER TABLE `recognition_level`
  ADD PRIMARY KEY (`RecognitionID`);

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

--
-- Indexes for table `waitlist`
--
ALTER TABLE `waitlist`
  ADD PRIMARY KEY (`WaitlistID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `AttendanceID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `membership`
--
ALTER TABLE `membership`
  MODIFY `MemberID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `participation_summary`
--
ALTER TABLE `participation_summary`
  MODIFY `SummaryID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recognition_level`
--
ALTER TABLE `recognition_level`
  MODIFY `RecognitionID` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
