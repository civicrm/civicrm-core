{* file to handle db changes in 4.4.5 during upgrade *}
-- CRM-14191
SELECT @option_group_id_batch_status   := max(id) from civicrm_option_group where name = 'batch_status';
SELECT @weight := MAX(ROUND(value)) FROM civicrm_option_value WHERE option_group_id = @option_group_id_batch_status;

UPDATE civicrm_option_value
SET value = (Select @weight := @weight +1),
weight = @weight
WHERE option_group_id = @option_group_id_batch_status AND name IN ('Data Entry', 'Reopened', 'Exported') AND value = 0 ORDER BY id;

SELECT @option_group_id_batch_modes := max(id) from civicrm_option_group where name = 'batch_mode';
SELECT @weights := MAX(ROUND(value)) FROM civicrm_option_value WHERE option_group_id = @option_group_id_batch_modes;

UPDATE civicrm_option_value
SET value = (Select @weights := @weights +1),
weight = @weights
WHERE option_group_id = @option_group_id_batch_modes AND name IN ('Manual Batch', 'Automatic Batch') AND value = 0;

SELECT @manual_mode_id := MAX(value) FROM civicrm_option_value WHERE option_group_id = @option_group_id_batch_modes AND name = 'Manual Batch';
UPDATE civicrm_batch SET mode_id = @manual_mode_id WHERE (mode_id IS NULL OR mode_id = 0) AND type_id IS NULL;

SELECT @data_entry_status_id := MAX(value) FROM civicrm_option_value WHERE option_group_id = @option_group_id_batch_status AND name = 'Data Entry';
UPDATE civicrm_batch SET status_id = @data_entry_status_id WHERE status_id = 3 AND type_id IS NOT NULL;

SELECT @exported_status_id := MAX(value) FROM civicrm_option_value WHERE option_group_id = @option_group_id_batch_status AND name = 'Exported';
UPDATE civicrm_navigation SET url = CONCAT('civicrm/financial/financialbatches?reset=1&batchStatus=', @exported_status_id) WHERE name = 'Exported Batches';

-- update status_id to Exported
SELECT @export_activity_type := max(value) FROM civicrm_option_value cov
INNER JOIN civicrm_option_group cog ON cog.id = cov.option_group_id
WHERE cog.name = 'activity_type' AND cov.name = 'Export Accounting Batch';

UPDATE civicrm_batch cb
INNER JOIN civicrm_activity ca ON ca.source_record_id = cb.id
SET cb.status_id = @exported_status_id
WHERE cb.status_id = 0 AND ca.activity_type_id = @export_activity_type;
