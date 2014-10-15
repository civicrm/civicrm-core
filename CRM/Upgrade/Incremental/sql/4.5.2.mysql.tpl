{* file to handle db changes in 4.5.2 during upgrade *}

-- CRM-15467 Also fix group_type for Honoree Profile
UPDATE civicrm_uf_group SET group_type = 'Individual,Contact' WHERE name = 'honoree_individual';

