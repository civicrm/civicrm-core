{* file to handle db changes in 4.7.beta8 during upgrade *}

-- CRM-17552
UPDATE civicrm_membership_block cmb JOIN civicrm_price_set_entity cpse ON cmb.entity_table = cpse.entity_table AND cmb.entity_id = cpse.entity_id JOIN civicrm_price_set cps ON cpse.price_set_id = cps.id SET cmb.is_required = 1 WHERE cmb.entity_table = 'civicrm_contribution_page' AND cps.is_quick_config = 0;

-- CRM-17429 - Old contributions may be using this payment processor type, so we'll disable rather than delete it for existing installs:
UPDATE civicrm_payment_processor_type SET is_active = 0 WHERE name = 'Google_Checkout';
UPDATE civicrm_payment_processor pp, civicrm_payment_processor_type ppt SET pp.is_active = 0 WHERE pp.payment_processor_type_id = ppt.id AND ppt.name = 'Google_Checkout';

-- CRM-17815
{include file='../CRM/Upgrade/4.7.beta8.msg_template/civicrm_msg_template.tpl'}

