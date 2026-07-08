CREATE TABLE IF NOT EXISTS `cnt_contents` (
  `id` char(26) NOT NULL,
  `user_id` char(26) NOT NULL,
  `source_module` varchar(32) NOT NULL,
  `source_id` char(26) NOT NULL,
  `field` varchar(64) NOT NULL DEFAULT 'content',
  `kind` varchar(32) NOT NULL DEFAULT 'markdown',
  `title` varchar(255) DEFAULT NULL,
  `body_md` longtext NOT NULL,
  `body_hash` char(64) DEFAULT NULL,
  `locale` varchar(10) DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT 1,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int NOT NULL DEFAULT 0,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cnt_contents_user_id_index` (`user_id`),
  KEY `cnt_contents_source_module_index` (`source_module`),
  KEY `cnt_contents_source_id_index` (`source_id`),
  KEY `cnt_contents_field_index` (`field`),
  KEY `cnt_contents_kind_index` (`kind`),
  KEY `cnt_contents_body_hash_index` (`body_hash`),
  KEY `cnt_contents_locale_index` (`locale`),
  KEY `cnt_contents_status_index` (`status`),
  KEY `cnt_contents_is_primary_index` (`is_primary`),
  KEY `cnt_contents_sort_order_index` (`sort_order`),
  KEY `cnt_source_field_kind_idx` (`source_module`, `source_id`, `field`, `kind`),
  KEY `cnt_source_primary_sort_idx` (`source_module`, `source_id`, `is_primary`, `sort_order`),
  KEY `cnt_user_module_field_idx` (`user_id`, `source_module`, `field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sys_templates` (
  `id` char(26) NOT NULL,
  `user_id` char(26) NOT NULL,
  `module` varchar(32) NOT NULL,
  `name` varchar(128) NOT NULL,
  `icon` varchar(64) DEFAULT NULL,
  `payload` json NOT NULL,
  `schedule` json DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT 1,
  `sort_order` int NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_templates_user_id_index` (`user_id`),
  KEY `sys_templates_module_index` (`module`),
  KEY `sys_templates_status_index` (`status`),
  KEY `sys_templates_sort_order_index` (`sort_order`),
  KEY `tpl_user_module_status_sort_idx` (`user_id`, `module`, `status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cnt_contents` (
  `id`,
  `user_id`,
  `source_module`,
  `source_id`,
  `field`,
  `kind`,
  `title`,
  `body_md`,
  `body_hash`,
  `status`,
  `is_primary`,
  `sort_order`,
  `created_at`,
  `updated_at`
)
SELECT
  SUBSTRING(REPLACE(UUID(), '-', ''), 1, 26),
  e.`user_id`,
  'eventor',
  e.`id`,
  'content',
  'markdown',
  e.`name`,
  TRIM(e.`content`),
  SHA2(TRIM(e.`content`), 256),
  1,
  1,
  0,
  NOW(),
  NOW()
FROM `evt_events` e
WHERE e.`content` IS NOT NULL
  AND TRIM(e.`content`) <> ''
  AND NOT EXISTS (
    SELECT 1
    FROM `cnt_contents` c
    WHERE c.`source_module` = 'eventor'
      AND c.`source_id` = e.`id`
      AND c.`field` = 'content'
      AND c.`kind` = 'markdown'
      AND c.`is_primary` = 1
  );
