START TRANSACTION;

INSERT INTO `users` (
  `id`,
  `name`,
  `email`,
  `password`,
  `status`,
  `created_at`,
  `updated_at`
)
VALUES (
  '01KNHVWYBVJT0X6QN30HJ4VDVJ',
  'Vsemhoy Import User',
  'vsemhoy@live.ru',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  1,
  NOW(),
  NOW()
)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `password` = VALUES(`password`),
  `status` = VALUES(`status`),
  `updated_at` = NOW();

COMMIT;
