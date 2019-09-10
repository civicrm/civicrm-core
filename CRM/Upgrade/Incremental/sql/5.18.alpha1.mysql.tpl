{* file to handle db changes in 5.18.alpha1 during upgrade *}

--  dev/financial#68: Check number doesn't get stored in associated financial_trxn record, if the contribution is made using 'Contribution/Membership batch data Entry' form
SELECT @uf_group_id_contribution := max(id) from civicrm_uf_group where name = 'contribution_batch_entry';
SELECT @uf_group_id_membership := max(id) from civicrm_uf_group where name = 'membership_batch_entry';

UPDATE civicrm_uf_field
SET field_name = 'check_number' WHERE uf_group_id IN (@uf_group_id_contribution, @uf_group_id_membership) AND field_name = 'contribution_check_number';
