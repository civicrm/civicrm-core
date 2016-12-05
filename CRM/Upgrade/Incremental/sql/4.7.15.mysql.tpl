{* file to handle db changes in 4.7.15 during upgrade *}

-- CRM-19685 (fix for inconsistencies)
UPDATE civicrm_contact SET preferred_mail_format = 'Both' WHERE preferred_mail_format IS NULL;

-- CRM-19715
SELECT @closing_accounting_at := cov.value FROM civicrm_option_group cog
  INNER JOIN civicrm_option_value cov 
    ON cov.option_group_id = cog.id AND cog.name = 'activity_type' AND cov.name = 'Close Accounting Period';

-- Delete all activities for Close Accounting Period
DELETE FROM civicrm_activity WHERE activity_type_id = @closing_accounting_at;

-- Delete Close Accounting Period activity type
DELETE cov.* FROM civicrm_option_group cog
  INNER JOIN civicrm_option_value cov 
    ON cov.option_group_id = cog.id AND cog.name = 'activity_type' AND cov.name = 'Close Accounting Period';

-- Delete Close Accounting Period Menu item
SELECT @contributionNavId := id, @domainID := domain_id FROM civicrm_navigation WHERE name = 'Contributions';

UPDATE civicrm_navigation SET has_separator = 0 WHERE name = 'Manage Price Sets' AND parent_id = @contributionNavId;

DELETE FROM civicrm_navigation WHERE name = 'Close Accounting Period' AND parent_id = @contributionNavId;

-- Drop field opening_balance and current_period_opening_balance
ALTER TABLE `civicrm_financial_account`
  DROP `opening_balance`,
  DROP `current_period_opening_balance`;
