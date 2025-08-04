-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Εξυπηρετητής: 127.0.0.1
-- Χρόνος δημιουργίας: 26 Απρ 2025 στις 09:12:53
-- Έκδοση διακομιστή: 10.4.32-MariaDB
-- Έκδοση PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Βάση δεδομένων: `air_ds`
--
CREATE DATABASE IF NOT EXISTS air_ds
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

-- Select the database
USE air_ds;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `airports`
--
USE air_ds;
CREATE TABLE `airports` (
  `airport_id` int(11) NOT NULL,
  `airport_name` varchar(255) NOT NULL,
  `airport_code` varchar(10) NOT NULL,
  `latitude` decimal(8,6) NOT NULL,
  `longitude` decimal(8,6) NOT NULL,
  `airport_tax` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `airports`
--

INSERT INTO `airports` (`airport_id`, `airport_name`, `airport_code`, `latitude`, `longitude`, `airport_tax`) VALUES
(1, 'Athens International Airport \"Eleftherios Venizelos\"', 'ATH', 37.937225, 23.945238, 150),
(2, 'Paris Charles de Gaulle Airport', 'CDG', 49.009724, 2.547778, 200),
(3, 'Leonardo da Vinci Rome Fiumicino Airport', 'FCO', 41.810800, 12.250900, 150),
(4, 'Adolfo Suárez Madrid–Barajas Airport', 'MAD', 40.489500, 3.564300, 250),
(5, 'Larnaka International Airport', 'LCA', 34.871500, 33.607700, 150),
(6, 'Brussels Airport', 'BRU', 50.900200, 4.485900, 200);

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `departure_airport_id` int(11) NOT NULL,
  `arrival_airport_id` int(11) NOT NULL,
  `reservation_date` date NOT NULL,
  `reservation_status` varchar(50) NOT NULL,
  `reserved_seats_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `departure_tax` int(10) DEFAULT NULL,
  `arrival_tax` int(10) DEFAULT NULL,
  `seat_cost` int(10) DEFAULT NULL,
  `passenger_names` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`passenger_names`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `departure_airport_id`, `arrival_airport_id`, `reservation_date`, `reservation_status`, `reserved_seats_json`, `total_amount`, `departure_tax`, `arrival_tax`, `seat_cost`, `passenger_names`) VALUES
(3, 1, 2, '2025-12-12', 'cancelled', '[]', 580.86, 150, 200, 20, '[\"gerasimos gerasimos\"]'),
(4, 1, 2, '2025-05-30', 'confirmed', '[\"2B\",\"13B\"]', 1131.72, 150, 200, 10, '[\"gerasimos gerasimos\",\"dionysios megaris\"]'),
(5, 1, 2, '2025-06-20', 'confirmed', '[\"1A\",\"1B\",\"1C\"]', 1742.59, 150, 200, 60, '[\"gerasimos gerasimos\",\"sakis zaxos\",\"kostas kakoliris\"]'),
(6, 1, 2, '2025-12-01', 'cancelled', '[]', 570.86, 150, 200, 10, '[\"gerasimos gerasimos\"]'),
(7, 1, 2, '2025-12-01', 'confirmed', '[\"1A\",\"1C\"]', 1161.72, 150, 200, 40, '[\"gerasimos gerasimos\",\"giannis matlis\"]'),
(8, 1, 2, '2025-12-01', 'cancelled', '[]', 570.86, 150, 200, 10, '[\"eleni megari\"]'),
(9, 1, 2, '2025-12-01', 'confirmed', '[\"3A\",\"3B\",\"4A\",\"4B\"]', 2283.45, 150, 200, 40, '[\"eleni megari\",\"kostas karnas\",\"ioannis matlis\",\"bill kako\"]'),
(10, 1, 2, '2025-12-01', 'confirmed', '[\"3D\",\"3E\"]', 1141.72, 150, 200, 20, '[\"eleni megari\",\"eleni mylona\"]'),
(11, 1, 2, '2025-12-01', 'confirmed', '[\"11D\",\"11E\"]', 1161.72, 150, 200, 40, '[\"eleni megari\",\"kostas kakoliris\"]'),
(12, 1, 2, '2025-12-01', 'cancelled', '[]', 580.86, 150, 200, 20, '[\"gerasimos gerasimos\"]'),
(13, 1, 2, '2025-04-23', 'confirmed', '[\"1A\"]', 580.86, 150, 200, 20, '[\"gerasimos gerasimos\"]');

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `reservation_user`
--

CREATE TABLE `reservation_user` (
  `reservation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `reservation_user`
--

INSERT INTO `reservation_user` (`reservation_id`, `user_id`) VALUES
(3, 1),
(4, 1),
(5, 1),
(6, 2),
(7, 2),
(8, 3),
(9, 3),
(10, 3),
(11, 3),
(12, 1),
(13, 1);

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(30) NOT NULL,
  `last_name` varchar(30) NOT NULL,
  `username` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `username`, `password`, `email`) VALUES
(1, 'gerasimos', 'gerasimos', 'gerasimos1', '$2y$10$p12a1SsPLyy4eOJ0QnutX.dTJhodi4GeXbXo3kYr5G1hvtU5If5Xe', 'gerassmeg@hotmail.com'),
(2, 'gerasimos', 'gerasimos', 'gerasimos2', '$2y$10$6cdNNCfh9KJwnljCD740I.U6qyFQruqDzXBeOh2GPrjZCZ4Zuhwe.', 'gerassmeg2@gmail.com'),
(3, 'eleni', 'megari', 'eleni', '$2y$10$GvFqO.IY942MaXeU4xby2uDUBQa/EcZOvdmXD6lCo0AeyJoBigo/i', 'eleni29@gmail.com');

--
-- Ευρετήρια για άχρηστους πίνακες
--

--
-- Ευρετήρια για πίνακα `airports`
--
ALTER TABLE `airports`
  ADD PRIMARY KEY (`airport_id`),
  ADD UNIQUE KEY `airport_code` (`airport_code`);

--
-- Ευρετήρια για πίνακα `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `departure_airport_id` (`departure_airport_id`),
  ADD KEY `arrival_airport_id` (`arrival_airport_id`);

--
-- Ευρετήρια για πίνακα `reservation_user`
--
ALTER TABLE `reservation_user`
  ADD PRIMARY KEY (`reservation_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Ευρετήρια για πίνακα `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT για άχρηστους πίνακες
--

--
-- AUTO_INCREMENT για πίνακα `airports`
--
ALTER TABLE `airports`
  MODIFY `airport_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT για πίνακα `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT για πίνακα `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Περιορισμοί για άχρηστους πίνακες
--

--
-- Περιορισμοί για πίνακα `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`departure_airport_id`) REFERENCES `airports` (`airport_id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`arrival_airport_id`) REFERENCES `airports` (`airport_id`);

--
-- Περιορισμοί για πίνακα `reservation_user`
--
ALTER TABLE `reservation_user`
  ADD CONSTRAINT `reservation_user_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`),
  ADD CONSTRAINT `reservation_user_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
