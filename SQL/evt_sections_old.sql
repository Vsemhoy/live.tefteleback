-- phpMyAdmin SQL Dump
-- version 
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Апр 24 2026 г., 10:16
-- Версия сервера: 5.7.44-54-log
-- Версия PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `host1334262_okkiobase25`
--

-- --------------------------------------------------------

--
-- Структура таблицы `evt_sections`
--

CREATE TABLE `evt_sections` (
  `id` char(26) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(26) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'New section',
  `literals` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(256) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `access` tinyint(4) NOT NULL DEFAULT '1',
  `color` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bgcolor` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `decor` json DEFAULT NULL,
  `seo` json DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `abc` text COLLATE utf8mb4_unicode_ci,
  `cde` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `evt_sections`
--

INSERT INTO `evt_sections` (`id`, `user_id`, `name`, `literals`, `description`, `sort_order`, `access`, `color`, `bgcolor`, `icon`, `decor`, `seo`, `is_archived`, `is_default`, `created_at`, `updated_at`, `abc`, `cde`) VALUES
('0KEpPRv3IWiREmT', '01K0YW4Z6F1KM8Q2KQ7SWNJBYQ', 'Fun and joy', NULL, '', 9, 1, NULL, 'cfbeb4', NULL, NULL, NULL, 1, 0, '2024-03-19 18:15:56', '2023-08-15 09:11:41', '', ''),
('b92J8BsuysSECR2', '01K0YW4Z6F1KM8Q2KQ7SWNJBYQ', 'Health', NULL, '', 7, 2, NULL, '6fd3c2', NULL, NULL, NULL, 1, 0, '2024-04-07 12:39:41', '2023-08-15 09:11:41', '', ''),
('bKEalTgCHd02979', '01K0YW4Z6F1KM8Q2KQ7SWNJBYQ', 'Finance', NULL, '', 18, 1, NULL, 'a4e35d', NULL, NULL, NULL, 1, 0, '2024-02-02 19:35:29', '2023-08-15 09:11:41', '', ''),
('dfghrredfgvfg54', '01K0YW4Z6F1KM8Q2KQ7SWNJBYQ', 'Home Events', NULL, 'Home observation', 17, 1, NULL, 'ff896b', NULL, NULL, NULL, 1, 0, '2024-02-02 19:35:44', '2023-08-20 19:22:40', '', ''),
('fjsltinsghtlen4', '01K0YW4Z6F1KM8Q2KQ7SWNJBYQ', 'Code Informer', NULL, 'Lol', 4, 1, NULL, 'fd216e', NULL, NULL, NULL, 1, 0, '2024-09-04 20:32:10', '2023-08-15 08:04:45', 's_652922712cbd9,s_6587033ec6ca7,s_6587035132184,s_6587037401c49,s_659747c8bc36b,s_65983790b618d,s_659837bac3e8c,s_659837bc9d3a5,s_6598385a9988d,s_66052de4ac3e9,s_65da2f1a9b604,s_66d8c3901a3cc', ''),
('jgongisngienth3', '01K0YW4Z6F1KM8Q2KQ7SWNJBYQ', 'Sport', NULL, 'Haha', 11, 1, NULL, '45b536', NULL, NULL, NULL, 1, 0, '2024-02-02 19:35:44', '2023-08-15 08:04:45', '', ''),
('jklYemcreiy3eXu', '01K0YW4Z6F1KM8Q2KQ7SWNJBYQ', 'Okkio Project', NULL, '', 5, 3, NULL, '16aca9', NULL, NULL, NULL, 1, 0, '2024-02-02 19:35:35', '2023-08-15 09:11:41', 's_64f065f72be43,s_64f065f0ab3dc,s_64efb0684ea17,s_64f046a0d42d2,s_64f065289abe2,s_64f06533df31c,s_64f06650a7770,s_64f067133b3d4,s_64f195ea85e66,s_650b71af1eadc,s_650b71b023aff,s_652922712cbd9,s_65604ce81f273', ''),
('lqYC8dD8SqJh9yD', '01K0YW4Z6F1KM8Q2KQ7SWNJBYQ', 'Shopping', NULL, '', 12, 1, NULL, '7872d5', NULL, NULL, NULL, 1, 0, '2024-02-02 19:35:44', '2023-08-15 09:11:41', '', ''),
('R0wRF5jInXuDNiB', '01K0YW4Z6F1KM8Q2KQ7SWNJBYQ', 'Accidents', NULL, '', 8, 1, NULL, '535670', NULL, NULL, NULL, 1, 0, '2024-02-02 19:35:44', '2023-08-15 09:11:41', '', ''),
('s_64e6a7ec6d931', '01K0YW4Z6F1KM8Q2KQ7SWNJBYQ', 'Diary notes', NULL, 'Description...', 13, 1, NULL, 'ffff00', NULL, NULL, NULL, 1, 0, '2024-02-02 19:35:44', '2023-08-24 00:44:28', '', ''),
('s_64ee35c374dcf', '01K0YW4Z6F1KM8Q2KQ7SWNJBYQ', 'Payments', NULL, 'What to pay', 10, 1, NULL, '00ffb3', NULL, NULL, NULL, 1, 0, '2024-02-02 19:35:44', '2023-08-29 18:15:31', '', ''),
('s_64f62504f3ab7', '01K0YW4Z6F1KM8Q2KQ7SWNJBYQ', 'Teftele Media', NULL, 'Description...', 19, 1, NULL, 'ea3483', NULL, NULL, NULL, 1, 0, '2024-02-02 19:35:29', '2023-09-04 18:42:12', 's_64f1829987c84,s_64f608120e3b1', ''),
('s_64fc23655335c', '01K0YW4Z6F1KM8Q2KQ7SWNJBYQ', 'My Day', NULL, 'Description...', 15, 1, NULL, 'ff0000', NULL, NULL, NULL, 1, 0, '2024-02-02 19:35:44', '2023-09-09 07:48:53', '', ''),
('s_651756e333cb6', '01K0YW4Z6F1KM8Q2KQ7SWNJBYQ', 'InfoTrash', NULL, 'Description...', 16, 1, NULL, '74b6db', NULL, NULL, NULL, 1, 0, '2024-04-11 20:07:44', '2023-09-29 22:59:47', '', ''),
('s_656a02fd61289', '01K0YW4Z6F1KM8Q2KQ7SWNJBYQ', 'Side jobs and tests', NULL, 'Description...', 2, 1, NULL, 'a32cce', NULL, NULL, NULL, 1, 0, '2024-03-28 15:54:25', '2023-12-01 15:59:57', '', ''),
('s_656a176b78e92', '01K0YW4Z6F1KM8Q2KQ7SWNJBYQ', 'Teftele Service project', NULL, 'Description...', 6, 1, NULL, 'e1506e', NULL, NULL, NULL, 1, 0, '2024-05-27 06:31:14', '2023-12-01 17:27:07', 's_64f065f72be43,s_64f065f0ab3dc,s_64f06650a7770,s_64f067133b3d4,s_64f1829987c84,s_64f195ea85e66,s_652922712cbd9,s_65604ce81f273,s_6587033ec6ca7,s_6587035132184,s_6587037401c49,s_65870381ed9e7,s_658703f5adca5', ''),
('S0n6SPv6bzuuGTp', '', 'Sport', NULL, NULL, 9, 1, NULL, 'cde8ea', NULL, NULL, NULL, 0, 0, '2023-08-15 09:11:41', '2023-08-15 09:11:41', '', ''),
('T4OHUmAZY2NZBL3', '01K0YW4Z6F1KM8Q2KQ7SWNJBYQ', 'ARS JOB', NULL, '', 1, 1, NULL, 'fe8420', NULL, NULL, NULL, 1, 0, '2024-03-21 09:17:02', '2023-08-15 09:11:41', 's_651ad0e6c8f4b,s_651ad11063daa,s_651ad111d62ab,s_651ad12f74124,s_64f065f72be43,s_64f065f0ab3dc,s_64f065557ce26,s_64f0656c77fec,s_64f0656d82b14,s_64f06591df5da,s_64f06650a7770,s_64f067133b3d4,s_64f195ea85e66,s_652922712cbd9,s_65604ce81f273,s_65fbfacddad56', ''),
('V4TGoyUTAaFnEWa', '01K0YW4Z6F1KM8Q2KQ7SWNJBYQ', 'Study', NULL, '', 3, 1, NULL, 'acd756', NULL, NULL, NULL, 1, 0, '2024-02-02 19:35:37', '2023-08-15 09:11:41', 's_64fc22db9764e,s_64fc22dcd75fa,s_64fc230093c6d,s_6523e7de651e7,s_65393969c7003,s_659747c8bc36b', ''),
('xraWvCrqrITZvnW', '01K0YW4Z6F1KM8Q2KQ7SWNJBYQ', 'Stuff Story', NULL, 'Развлечения и досуг', 14, 0, NULL, '000000', NULL, NULL, NULL, 1, 0, '2024-02-02 19:35:44', '2023-08-15 09:11:41', '', '');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `evt_sections`
--
ALTER TABLE `evt_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `evt_sections_user_id_access_index` (`user_id`,`access`),
  ADD KEY `evt_sections_user_id_is_archived_index` (`user_id`,`is_archived`),
  ADD KEY `evt_sections_user_id_sort_order_index` (`user_id`,`sort_order`),
  ADD KEY `evt_sections_user_id_index` (`user_id`),
  ADD KEY `evt_sections_access_index` (`access`);

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `evt_sections`
--
ALTER TABLE `evt_sections`
  ADD CONSTRAINT `evt_sections_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
