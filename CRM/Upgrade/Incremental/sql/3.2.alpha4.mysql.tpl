-- schema changes for 3.2 alpha4 tag
--  change is_hidden to is_tagset in civicrm_tag
ALTER TABLE `civicrm_tag` CHANGE `is_hidden` `is_tagset` TINYINT( 4 ) NULL DEFAULT '0';

-- CRM-6229
ALTER TABLE `civicrm_event` CHANGE `is_template` `is_template` TINYINT( 4 ) NULL DEFAULT '0' COMMENT 'whether the event has template';
UPDATE `civicrm_event` SET `is_template` = 0 WHERE `is_template` IS NULL ;

-- CRM-5970 
ALTER TABLE `civicrm_financial_account` ADD `contact_id` INT UNSIGNED NOT NULL COMMENT 'FK to civicrm_contact' AFTER `id` ;
ALTER TABLE `civicrm_financial_account`
     ADD CONSTRAINT `FK_civicrm_financial_account_contact_id` FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE;

-- CRM-6294 (event badge support)
{if $multilingual}
    INSERT INTO civicrm_option_group
        ( name,                 {foreach from=$locales item=locale}description_{$locale},   {/foreach} is_reserved, is_active)
    VALUES
        ( 'event_badge',    {foreach from=$locales item=locale}'Event Badge',       {/foreach} 0, 1 );
{else}
    INSERT INTO civicrm_option_group
        (name, description, is_reserved, is_active )
    VALUES
        ('event_badge', 'event_badge', 0, 1 );
{/if}

SELECT @option_group_id_eventBadge         := max(id) from civicrm_option_group where name = 'event_badge';

{if $multilingual}
    INSERT INTO civicrm_option_value
	(option_group_id, {foreach from=$locales item=locale}label_{$locale}, description_{$locale}, {/foreach} value, name, weight, is_active, component_id )
    VALUES
        (@option_group_id_eventBadge , {foreach from=$locales item=locale}'Name Only', 'Simple Event Name Badge', {/foreach} '1', 'CRM_Event_Badge_Simple', 1,   1, NULL ),
        (@option_group_id_eventBadge , {foreach from=$locales item=locale}'Name Tent', 'Name Tent', {/foreach} '2', 'CRM_Event_Badge_NameTent', 1,   1, NULL );
{else}
    INSERT INTO civicrm_option_value
	(option_group_id, label, description, value, name, weight, is_active, component_id )
    VALUES
        (@option_group_id_eventBadge , '{ts escape="sql"}Name Only{/ts}', '{ts escape="sql"}Simple Event Name Badge{/ts}', '1', 'CRM_Event_Badge_Simple', 1,   1, NULL ),
        (@option_group_id_eventBadge , '{ts escape="sql"}Name Tent{/ts}', '{ts escape="sql"}Name Tent{/ts}', '2', 'CRM_Event_Badge_NameTent', 1,   1, NULL );
{/if}

