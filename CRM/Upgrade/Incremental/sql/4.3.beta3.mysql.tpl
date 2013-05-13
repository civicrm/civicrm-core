{include file='../CRM/Upgrade/4.3.beta3.msg_template/civicrm_msg_template.tpl'}
-- CRM-12077
DELETE cog, cov FROM `civicrm_option_group` cog
LEFT JOIN civicrm_option_value cov ON cov.option_group_id = cog.id
WHERE cog.name = 'account_type';

{if $multilingual}
  UPDATE civicrm_uf_field
  SET  field_name = 'financial_type'
  WHERE field_name LIKE 'contribution_type';
  {foreach from=$locales item=locale}
    UPDATE civicrm_uf_field
    SET label_{$locale} = 'Financial Type'
    WHERE field_name = 'financial_type' AND label_{$locale} = 'Contribution Type';
  {/foreach}

{else}
  UPDATE civicrm_uf_field
  SET  field_name = 'financial_type',
  label = CASE
  WHEN label = 'Contribution Type'
  THEN 'Financial Type'
  ELSE label
  END
  WHERE field_name = 'contribution_type';
{/if}

-- CRM-12065
UPDATE `civicrm_mapping_field`
SET name = replace(name, 'contribution_type', 'financial_type')
where name LIKE '%contribution_type%';