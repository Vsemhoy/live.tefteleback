-- phpMyAdmin SQL Dump
-- version 
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3308
-- Время создания: Апр 24 2026 г., 10:13
-- Версия сервера: 8.0.45-36
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
-- База данных: `host1334262_teftele2026`
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
  `sort_order` bigint UNSIGNED NOT NULL DEFAULT '0',
  `access` tinyint NOT NULL DEFAULT '1',
  `color` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bgcolor` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `decor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `seo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ;

--
-- Дамп данных таблицы `evt_sections`
--

INSERT INTO `evt_sections` (`id`, `user_id`, `name`, `literals`, `description`, `sort_order`, `access`, `color`, `bgcolor`, `icon`, `decor`, `seo`, `is_archived`, `is_default`, `created_at`, `updated_at`) VALUES
('01KNQEVACXFQ145FTFHSKWS9AE', '01KNHVWYBVJT0X6QN30HJ4VDVJ', 'TELEPORT', 'TPR', NULL, 4, 1, NULL, '#e8590c', NULL, NULL, NULL, 0, 0, '2026-04-08 18:09:22', '2026-04-10 19:14:08'),
('01KNQGPQFJ2PW4CJ7F1ANTBWC8', '01KNHVWYBVJT0X6QN30HJ4VDVJ', 'FamilyGuy', 'FML', NULL, 5, 1, NULL, '#9c36b5', NULL, NULL, NULL, 0, 0, '2026-04-08 18:41:49', '2026-04-10 19:14:08'),
('01KNQHPA9V1752P59FNJNH590D', '01KNHVWYBVJT0X6QN30HJ4VDVJ', 'NetRisk', 'NRS', NULL, 6, 1, NULL, '#ffc300', NULL, NULL, NULL, 0, 0, '2026-04-08 18:59:04', '2026-04-10 19:14:08'),
('01KNRJAM3CKXGV3AEWDATDNYWW', '01KNHVWYBVJT0X6QN30HJ4VDVJ', 'Server Life', 'SRV', NULL, 7, 1, NULL, '#e6608f', NULL, NULL, NULL, 0, 0, '2026-04-09 04:29:24', '2026-04-10 19:14:08'),
('01KNRT2HHA5NS18ECXEZQ1MM4A', '01KNHVWYBVJT0X6QN30HJ4VDVJ', 'Code Bucket', 'BUC', NULL, 3, 1, NULL, '#868e96', NULL, NULL, NULL, 0, 0, '2026-04-09 06:44:47', '2026-04-10 19:14:08'),
('01KNSG88B1XR3NW4X1H0D0G7CX', '01KNHVWYBVJT0X6QN30HJ4VDVJ', 'JOB', 'JOB', NULL, 1, 1, NULL, '#ff5f03', NULL, NULL, NULL, 0, 0, '2026-04-09 13:12:23', '2026-04-10 19:14:08'),
('01KNSQP3W8XACC0S9BBJ1818DG', '01KNHVWYBVJT0X6QN30HJ4VDVJ', 'Stuff Story', 'STF', NULL, 0, 1, NULL, '#67a2bf', NULL, NULL, NULL, 0, 0, '2026-04-09 15:22:17', '2026-04-10 19:14:08'),
('01KNWHNRQ8RMEEGQGWDW021SFP', '01KNHVWYBVJT0X6QN30HJ4VDVJ', 'Routines', 'RTN', NULL, 2, 1, NULL, '#8467db', NULL, NULL, NULL, 0, 0, '2026-04-10 17:34:58', '2026-04-10 19:14:08'),
('01KNWQB1B6ACC6QWN9PBNWKTT0', '01KNHVWYBVJT0X6QN30HJ4VDVJ', 'Media Igla', 'MIG', NULL, 8, 1, NULL, '#8c6278', NULL, NULL, NULL, 0, 0, '2026-04-10 19:13:58', '2026-04-10 19:14:08'),
('01KP8NQYW8G92YHETP8AQMY43P', '01KNHVWYBVJT0X6QN30HJ4VDVJ', 'PayDay', 'PAY', NULL, 0, 1, NULL, '#2946ba', NULL, NULL, NULL, 0, 0, '2026-04-15 10:36:57', '2026-04-15 10:36:57');

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
