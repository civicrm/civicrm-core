-- CRM-12142
{if !$multilingual}
  ALTER TABLE `civicrm_premiums`
    ADD COLUMN premiums_nothankyou_label varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Label displayed for No Thank-you
 option in premiums block (e.g. No thank you)';
{/if}

-- CRM-12151
ALTER TABLE civicrm_option_value
  DROP INDEX index_option_group_id_value,
  ADD INDEX index_option_group_id_value (value(128), option_group_id),
  DROP INDEX index_option_group_id_name,
  ADD INDEX index_option_group_id_name (option_group_id, name(128));


