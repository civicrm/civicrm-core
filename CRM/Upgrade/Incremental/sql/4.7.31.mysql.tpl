{* file to handle db changes in 4.7.31 during upgrade *}

-- CRM-18300 Remove "_[id]" suffix from name column for profiles referenced
-- by name in core code.
UPDATE IGNORE civicrm_uf_group SET name = 'new_individual' WHERE name = concat('new_individual_', id);
UPDATE IGNORE civicrm_uf_group SET name = 'new_organization' WHERE name = concat('new_organization_', id);
UPDATE IGNORE civicrm_uf_group SET name = 'new_household' WHERE name = concat('new_household_', id);
UPDATE IGNORE civicrm_uf_group SET name = 'summary_overlay' WHERE name = concat('summary_overlay_', id);
UPDATE IGNORE civicrm_uf_group SET name = 'contribution_batch_entry' WHERE name = concat('contribution_batch_entry_', id);
UPDATE IGNORE civicrm_uf_group SET name = 'membership_batch_entry' WHERE name = concat('membership_batch_entry_', id);
UPDATE IGNORE civicrm_uf_group SET name = 'shared_address' WHERE name = concat('shared_address_', id);
UPDATE IGNORE civicrm_uf_group SET name = 'event_registration' WHERE name = concat('event_registration_', id);
