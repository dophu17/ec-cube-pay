-- dtb_customer テストユーザー投入（MySQL用）
INSERT INTO dtb_customer (
  id, customer_status_id, sex_id, job_id, country_id, pref_id, name01, name02, kana01, kana02, company_name,
  postal_code, addr01, addr02, email, phone_number, birth, password, salt, secret_key, first_buy_date,
  last_buy_date, buy_times, buy_total, note, reset_key, reset_expire, point, create_date, update_date,
  discriminator_type, stripe_customer_id
) VALUES (
  1, 2, NULL, NULL, NULL, 13, 'テスト', 'テスト', 'テスト', 'テスト', NULL,
  '1760021', '練馬区貫井', '123', 'testuser@example.com', '000011112222', NULL,
  '$2y$13$1BNdWKyDqfLSyyQQ4qu7PuTa61xk9hdOjRP9TVVVn6QZZEjRyquCG', NULL, 'EpU6Sot8In4CpL1yHDEL6A2SoXxDmUJC', NULL,
  NULL, 0, 0.00, NULL, NULL, NULL, 0, '2025-06-25T14:56:21', '2025-06-25T14:56:21',
  'customer', NULL
)
ON DUPLICATE KEY UPDATE id=id; -- MySQL用
