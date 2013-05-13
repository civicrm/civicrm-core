
-- CRM-5711

UPDATE civicrm_custom_group SET extends_entity_column_value = CONCAT(CHAR( 01 ), extends_entity_column_value, CHAR( 01 ))
WHERE LOCATE( char( 01 ), extends_entity_column_value ) <= 0;

-- CRM-5472
INSERT INTO civicrm_acl
    (name, deny, entity_table, entity_id, operation, object_table, object_id, acl_table, acl_id, is_active)
VALUES
    ('Core ACL', 0, 'civicrm_acl_role', 1, 'All', 'edit all events', NULL, NULL, NULL, 1);

-- CRM-5636
{if $addDeceasedStatus}
   {if $multilingual}
      INSERT INTO  civicrm_membership_status
          ( {foreach from=$locales item=locale}name_{$locale}, {/foreach} is_current_member, is_admin, is_active, is_reserved, weight, is_default )
      VALUES
          ( {foreach from=$locales item=locale}'Deceased',{/foreach} 0, 1, 1, 1, (SELECT @maxWeight := @maxWeight + 1), 0 );
   {else}
      INSERT INTO  civicrm_membership_status
          ( name, is_current_member, is_admin, is_active, is_reserved, weight, is_default )
      VALUES
          ( 'Deceased', 0, 1, 1, 1, (SELECT @maxWeight := @maxWeight + 1), 0 );
   {/if}
{/if}
