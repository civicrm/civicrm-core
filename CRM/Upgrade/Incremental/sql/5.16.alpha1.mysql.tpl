{* file to handle db changes in 5.16.alpha1 during upgrade *}

-- dev/core#561 Update fields to take into account receive_date and cancel_date have unique names now
UPDATE civicrm_uf_field SET field_name = 'contribution_cancel_date' WHERE field_name = 'cancel_date';
UPDATE civicrm_mapping_field SET name = 'contribution_cancel_date' WHERE name = 'cancel_date';
