-- CRM-7646
SELECT @option_group_id_pi := max(id) from civicrm_option_group where name = 'payment_instrument';
UPDATE civicrm_option_value
    SET is_reserved = 1
    WHERE option_group_id = @option_group_id_pi AND name = 'Check';
UPDATE civicrm_option_value
    SET is_reserved = 1
    WHERE option_group_id = @option_group_id_pi AND name = 'Debit Card';

-- CRM-7798
UPDATE civicrm_uf_field SET field_name = 'participant_status' WHERE field_name = 'participant_status_id';

UPDATE civicrm_uf_field SET field_name = 'participant_role' WHERE field_name = 'participant_role_id';

UPDATE civicrm_uf_field SET field_name = 'membership_type' WHERE field_name = 'membership_type_id';

UPDATE civicrm_uf_field SET field_name = 'membership_status' WHERE field_name = 'status_id';

UPDATE civicrm_uf_field SET field_name = 'contribution_type' WHERE field_name = 'contribution_type_id';

