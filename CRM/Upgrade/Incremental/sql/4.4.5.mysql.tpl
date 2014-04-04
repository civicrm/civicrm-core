{* file to handle db changes in 4.4.5 during upgrade *}
-- CRM-14191
SELECT @option_group_id_batch_status   := max(id) from civicrm_option_group where name = 'batch_status';
SELECT @weight := MAX(value) FROM civicrm_option_value WHERE option_group_id = @option_group_id_batch_status;

UPDATE  civicrm_option_value 
SET value = (Select @weight := @weight +1),
weight = @weight
where option_group_id = @option_group_id_batch_status AND name IN ('Data Entry', 'Reopened', 'Exported') AND value = 0 ORDER BY id;

SELECT @option_group_id_batch_modes := max(id) from civicrm_option_group where name = 'batch_mode';
SELECT @weights := MAX(value) FROM civicrm_option_value WHERE option_group_id = @option_group_id_batch_modes;

UPDATE  civicrm_option_value 
SET value = (Select @weights := @weights +1),
weight = @weights
where option_group_id = @option_group_id_batch_modes AND name IN ('Manual Batch', 'Automatic Batch') AND value = 0;