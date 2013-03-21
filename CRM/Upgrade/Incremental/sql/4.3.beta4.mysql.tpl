-- CRM-12142
{if !$multilingual}
  ALTER TABLE `civicrm_premiums`
    ADD COLUMN premiums_nothankyou_label varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Label displayed for No Thank-you
 option in premiums block (e.g. No thank you)';

-- Also need to populate default text for premiums_nothankyou_label
  UPDATE `civicrm_premiums` SET premiums_nothankyou_label = '{ts escape="sql"}No thank-you{/ts}';
{else}
  {foreach from=$locales item=locale}
    UPDATE `civicrm_premiums` SET premiums_nothankyou_label_{$locale} = '{ts escape="sql"}No thank-you{/ts}';	   
  {/foreach}
{/if}


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
cpfv.amount = cmt.minimum_fee	
WHERE cps.is_quick_config = 1 AND cpfv.membership_type_id IS NOT NULL;