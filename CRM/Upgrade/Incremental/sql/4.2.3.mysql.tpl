-- CRM-10969
SELECT @mailingsID := MAX(id) FROM civicrm_navigation WHERE name = 'Mailings' AND domain_id = {$domainID};
SELECT @navWeight := MAX(id) FROM civicrm_navigation WHERE name = 'New SMS' AND parent_id = @mailingsID;

UPDATE civicrm_navigation SET has_separator = NULL
WHERE name = 'New SMS' AND parent_id = @mailingsID AND has_separator = 1;

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( {$domainID}, 'civicrm/mailing/browse?reset=1&sms=1', '{ts escape="sql" skip="true"}Find Mass SMS{/ts}', 'Find Mass SMS', 'administer CiviCRM', NULL, @mailingsID, '1', 1, @navWeight+1 );

-- CRM-10980 and CRM-11014
SELECT @optionID := max(id) FROM civicrm_option_value WHERE name = 'BULK SMS';
{if $multilingual}
    {foreach from=$locales item=locale}
    UPDATE `civicrm_option_value` SET label_{$locale} = '{ts escape="sql"}Mass SMS{/ts}',name = 'Mass SMS',description_{$locale} = '{ts escape="sql"}Mass SMS{/ts}' WHERE id = @optionID;
      ALTER TABLE `civicrm_price_field_value` CHANGE name name VARCHAR(255) NULL DEFAULT NULL, CHANGE label_{$locale} label_{$locale} VARCHAR(255)  NULL DEFAULT NULL;
    {/foreach}
{else}
  UPDATE `civicrm_option_value` SET label = '{ts escape="sql"}Mass SMS{/ts}',name = 'Mass SMS',description = '{ts escape="sql"}Mass SMS{/ts}' WHERE name = 'BULK SMS';
  ALTER TABLE `civicrm_price_field_value` CHANGE `name` `name` VARCHAR(255) NULL DEFAULT NULL, CHANGE `label` `label` VARCHAR(255)  NULL DEFAULT NULL;
{/if}

-- CRM-11014
  ALTER TABLE `civicrm_line_item` CHANGE `label` `label` VARCHAR(255) NULL DEFAULT NULL;

-- CRM-10986: Rename Batches UI elements to Bulk Data Entry
-- update reserved profile titles
{if $multilingual}
  {foreach from=$locales item=locale}
    UPDATE `civicrm_uf_group` SET title_{$locale} = '{ts escape="sql"}Contribution Bulk Entry{/ts}' WHERE name = 'contribution_batch_entry';
    UPDATE `civicrm_uf_group` SET title_{$locale} = '{ts escape="sql"}Membership Bulk Entry{/ts}' WHERE name = 'membership_batch_entry';
  {/foreach}
{else}
  UPDATE `civicrm_uf_group` SET title = '{ts escape="sql"}Contribution Bulk Entry{/ts}' WHERE name = 'contribution_batch_entry';
  UPDATE `civicrm_uf_group` SET title = '{ts escape="sql"}Membership Bulk Entry{/ts}' WHERE name = 'membership_batch_entry';
{/if}

-- update navigation menu items and fix typo in permission column
UPDATE `civicrm_navigation` SET label = '{ts escape="sql"}Bulk Data Entry{/ts}', name = 'Bulk Data Entry',
permission = 'access CiviMember, access CiviContribute'
WHERE url = 'civicrm/batch&reset=1';

-- CRM-11018
ALTER TABLE civicrm_discount DROP FOREIGN KEY FK_civicrm_discount_option_group_id;
ALTER TABLE `civicrm_discount`
  ADD CONSTRAINT `FK_civicrm_discount_option_group_id` FOREIGN KEY (`option_group_id`) REFERENCES `civicrm_price_set` (`id`) ON DELETE CASCADE;
ALTER TABLE `civicrm_discount` CHANGE `option_group_id` `option_group_id` int(10) unsigned NOT NULL COMMENT 'FK to civicrm_price_set';