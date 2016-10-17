-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Gostitelj: localhost
-- Čas nastanka: 13. okt 2016 ob 23.03
-- Različica strežnika: 5.7.13-0ubuntu0.16.04.2
-- Različica PHP: 7.0.8-0ubuntu0.16.04.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Zbirka podatkov: `pckg_database`
--

-- --------------------------------------------------------

--
-- Struktura tabele `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `slug` varchar(127) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Odloži podatke za tabelo `categories`
--

INSERT INTO `categories` (`id`, `slug`, `parent_id`) VALUES
(1, 'first', NULL),
(2, 'second', NULL),
(3, 'third', NULL),
(4, 'first_1', 1),
(5, 'first_2', 1),
(6, 'first_3', 1),
(7, 'first_4', 1),
(8, 'first_4_1', 7),
(9, 'first_4_2', 7),
(10, 'first_3_1', 6);

-- --------------------------------------------------------

--
-- Struktura tabele `languages`
--

CREATE TABLE `languages` (
  `id` int(11) NOT NULL,
  `slug` varchar(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Odloži podatke za tabelo `languages`
--

INSERT INTO `languages` (`id`, `slug`) VALUES
(1, 'en'),
(3, 'hr'),
(2, 'si');

-- --------------------------------------------------------

--
-- Struktura tabele `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `slug` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Odloži podatke za tabelo `settings`
--

INSERT INTO `settings` (`id`, `slug`) VALUES
(2, 'bar'),
(3, 'baz'),
(1, 'foo');

-- --------------------------------------------------------

--
-- Struktura tabele `settings_morphs`
--

CREATE TABLE `settings_morphs` (
  `id` int(11) NOT NULL,
  `setting_id` int(11) NOT NULL,
  `poly_id` int(11) DEFAULT NULL,
  `morph_id` varchar(127) DEFAULT NULL,
  `value` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Odloži podatke za tabelo `settings_morphs`
--

INSERT INTO `settings_morphs` (`id`, `setting_id`, `poly_id`, `morph_id`, `value`) VALUES
(1, 1, 1, 'Test\\Entity\\Users', 'setting foo for admin 1'),
(2, 1, 2, 'Test\\Entity\\Users', 'setting foo for admin 2'),
(3, 2, 1, 'Test\\Entity\\Users', 'setting bar for admin 1');

-- --------------------------------------------------------

--
-- Struktura tabele `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(127) NOT NULL,
  `user_group_id` int(11) NOT NULL,
  `language_id` varchar(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Odloži podatke za tabelo `users`
--

INSERT INTO `users` (`id`, `username`, `user_group_id`, `language_id`) VALUES
(1, 'a1', 1, 'si'),
(2, 'a2', 1, 'en'),
(3, 'm1', 2, 'si'),
(4, 'm2', 2, 'en'),
(5, 'u1', 3, 'en'),
(6, 'u2', 3, 'hr'),
(87, 'testuser', 1, 'si');

-- --------------------------------------------------------

--
-- Struktura tabele `users_categories`
--

CREATE TABLE `users_categories` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Odloži podatke za tabelo `users_categories`
--

INSERT INTO `users_categories` (`id`, `user_id`, `category_id`) VALUES
(22, 1, 2),
(24, 1, 4),
(10, 1, 5),
(18, 1, 8),
(19, 2, 2),
(26, 2, 6),
(5, 2, 8),
(9, 2, 9),
(7, 3, 3),
(20, 3, 7),
(17, 4, 3),
(3, 4, 5),
(8, 5, 2),
(25, 5, 5),
(2, 5, 7),
(11, 5, 8),
(6, 5, 9),
(1, 5, 10),
(21, 6, 9);

-- --------------------------------------------------------

--
-- Struktura tabele `user_groups`
--

CREATE TABLE `user_groups` (
  `id` int(11) NOT NULL,
  `slug` varchar(127) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Odloži podatke za tabelo `user_groups`
--

INSERT INTO `user_groups` (`id`, `slug`) VALUES
(1, 'admin'),
(2, 'mod'),
(3, 'user'),
(4, 'untranslated');

-- --------------------------------------------------------

--
-- Struktura tabele `user_groups_i18n`
--

CREATE TABLE `user_groups_i18n` (
  `id` int(11) NOT NULL,
  `language_id` varchar(2) NOT NULL,
  `title` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Odloži podatke za tabelo `user_groups_i18n`
--

INSERT INTO `user_groups_i18n` (`id`, `language_id`, `title`) VALUES
(1, 'en', 'Administrator'),
(2, 'en', 'Moderator'),
(3, 'en', 'User'),
(1, 'si', 'Admin'),
(2, 'si', 'Moderator'),
(3, 'si', 'Uporabnik');

--
-- Indeksi zavrženih tabel
--

--
-- Indeksi tabele `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug_UNIQUE` (`slug`),
  ADD KEY `fk_categories_categories1_idx` (`parent_id`);

--
-- Indeksi tabele `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug_UNIQUE` (`slug`);

--
-- Indeksi tabele `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug_UNIQUE` (`slug`);

--
-- Indeksi tabele `settings_morphs`
--
ALTER TABLE `settings_morphs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_settings_morphs_settings1_idx` (`setting_id`);

--
-- Indeksi tabele `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username_UNIQUE` (`username`),
  ADD KEY `fk_users_user_groups_idx` (`user_group_id`),
  ADD KEY `fk_users_languages1_idx` (`language_id`);

--
-- Indeksi tabele `users_categories`
--
ALTER TABLE `users_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`category_id`),
  ADD KEY `fk_users_categories_users1_idx` (`user_id`),
  ADD KEY `fk_users_categories_categories1_idx` (`category_id`);

--
-- Indeksi tabele `user_groups`
--
ALTER TABLE `user_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug_UNIQUE` (`slug`);

--
-- Indeksi tabele `user_groups_i18n`
--
ALTER TABLE `user_groups_i18n`
  ADD PRIMARY KEY (`language_id`,`id`),
  ADD KEY `fk_user_groups_i18n_user_groups1_idx` (`id`),
  ADD KEY `fk_user_groups_i18n_languages1_idx` (`language_id`);

--
-- AUTO_INCREMENT zavrženih tabel
--

--
-- AUTO_INCREMENT tabele `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT tabele `languages`
--
ALTER TABLE `languages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT tabele `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT tabele `settings_morphs`
--
ALTER TABLE `settings_morphs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT tabele `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;
--
-- AUTO_INCREMENT tabele `users_categories`
--
ALTER TABLE `users_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;
--
-- AUTO_INCREMENT tabele `user_groups`
--
ALTER TABLE `user_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- Omejitve tabel za povzetek stanja
--

--
-- Omejitve za tabelo `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_categories1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omejitve za tabelo `settings_morphs`
--
ALTER TABLE `settings_morphs`
  ADD CONSTRAINT `fk_settings_morphs_settings1` FOREIGN KEY (`setting_id`) REFERENCES `settings` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omejitve za tabelo `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_languages1` FOREIGN KEY (`language_id`) REFERENCES `languages` (`slug`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_users_user_groups` FOREIGN KEY (`user_group_id`) REFERENCES `user_groups` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omejitve za tabelo `users_categories`
--
ALTER TABLE `users_categories`
  ADD CONSTRAINT `fk_users_categories_categories1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_users_categories_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Omejitve za tabelo `user_groups_i18n`
--
ALTER TABLE `user_groups_i18n`
  ADD CONSTRAINT `fk_user_groups_i18n_languages1` FOREIGN KEY (`language_id`) REFERENCES `languages` (`slug`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_user_groups_i18n_user_groups1` FOREIGN KEY (`id`) REFERENCES `user_groups` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
