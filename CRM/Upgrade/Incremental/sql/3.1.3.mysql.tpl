-- CRM-5824 
{if $multilingual} 
  {foreach from=$locales item=locale}
    UPDATE civicrm_option_value ov 
INNER JOIN civicrm_option_group og ON ( og.id = ov.option_group_id )
       SET ov.name = ov.label_{$locale}
     WHERE og.name = 'activity_type' 
       AND ov.name IS NULL;
  {/foreach}
{else}
    UPDATE civicrm_option_value ov 
INNER JOIN civicrm_option_group og ON ( og.id = ov.option_group_id )
       SET ov.name = ov.label
     WHERE og.name = 'activity_type' 
       AND ov.name IS NULL;
{/if}
 
-- CRM-5825
    UPDATE civicrm_menu
           SET is_ssl = 1
    WHERE path = 'civicrm/event/manage';
    
-- CRM-5872
    
    UPDATE civicrm_option_value ov 
    INNER JOIN civicrm_option_group og ON ( og.id = ov.option_group_id )
       SET ov.is_reserved = 1
     WHERE ov.name = 'Bulk Email' AND
           og.name = 'activity_type';
     