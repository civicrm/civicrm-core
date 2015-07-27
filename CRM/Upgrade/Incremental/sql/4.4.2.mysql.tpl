{* file to handle db changes in 4.4.2 during upgrade *}
-- CRM-13758
UPDATE civicrm_uf_field SET field_name = 'gender_id' WHERE field_name = 'gender';
UPDATE civicrm_uf_field SET field_name = 'prefix_id' WHERE field_name = 'individual_prefix';
UPDATE civicrm_uf_field SET field_name = 'suffix_id' WHERE field_name = 'individual_suffix';
