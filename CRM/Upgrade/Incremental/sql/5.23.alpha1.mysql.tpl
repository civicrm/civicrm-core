{* file to handle db changes in 5.23.alpha1 during upgrade *}
UPDATE civicrm_payment_processor SET is_default = 0 WHERE is_default IS NULL;
UPDATE civicrm_payment_processor SET is_active = 1 WHERE is_active IS NULL;
UPDATE civicrm_payment_processor SET is_test = 0 WHERE is_test IS NULL;
UPDATE civicrm_payment_processor_type SET is_active = 1 WHERE is_active IS NULL;
UPDATE civicrm_payment_processor_type SET is_default = 0 WHERE is_default IS NULL;
ALTER TABLE civicrm_payment_processor ALTER COLUMN is_default SET DEFAULT 0;
ALTER TABLE civicrm_payment_processor ALTER COLUMN is_active SET DEFAULT 1;
ALTER TABLE civicrm_payment_processor ALTER COLUMN is_test SET DEFAULT 0;
ALTER TABLE civicrm_payment_processor_type ALTER COLUMN is_active SET DEFAULT 1;
ALTER TABLE civicrm_payment_processor_type ALTER COLUMN is_default SET DEFAULT 0;
