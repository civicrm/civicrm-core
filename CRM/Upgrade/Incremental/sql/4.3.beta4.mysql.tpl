-- CRM-12142
{if !$multilingual}
  ALTER TABLE `civicrm_premiums` 
    ADD COLUMN premiums_nothankyou_label varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Label displayed for No Thank-you option in premiums block (e.g. No thank you)';
{/if}