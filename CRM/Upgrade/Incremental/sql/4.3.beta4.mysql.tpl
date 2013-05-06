-- CRM-12151
ALTER TABLE civicrm_option_value
  DROP INDEX index_option_group_id_value,
  ADD INDEX index_option_group_id_value (value(128), option_group_id),
  DROP INDEX index_option_group_id_name,
  ADD INDEX index_option_group_id_name (option_group_id, name(128));

-- CRM-12127
UPDATE civicrm_membership_type cmt
LEFT JOIN civicrm_price_field_value cpfv ON cpfv.membership_type_id = cmt.id 
LEFT JOIN civicrm_price_field cpf ON cpf.id = cpfv.price_field_id
LEFT JOIN civicrm_price_set cps ON cps.id = cpf.price_set_id
SET 
cpfv.financial_type_id = cmt.financial_type_id,
{if !$multilingual}
  cpfv.label = cmt.name,
  cpfv.description = cmt.description,
{else}
  {foreach from=$locales item=locale}
    cpfv.label_{$locale} = cmt.name_{$locale},
    cpfv.description_{$locale} = cmt.description_{$locale},
  {/foreach}
{/if}
cpfv.amount = IFNULL(cmt.minimum_fee, 0.00)	
WHERE cps.is_quick_config = 1 AND cpfv.membership_type_id IS NOT NULL;