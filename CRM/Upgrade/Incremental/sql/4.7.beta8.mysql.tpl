{* file to handle db changes in 4.7.beta8 during upgrade *}

-- CRM-17552
UPDATE civicrm_membership_block cmb JOIN civicrm_price_set_entity cpse ON cmb.entity_table = cpse.entity_table AND cmb.entity_id = cpse.entity_id JOIN civicrm_price_set cps ON cpse.price_set_id = cps.id SET cmb.is_required = 1 WHERE cmb.entity_table = 'civicrm_contribution_page' AND cps.is_quick_config = 0;
