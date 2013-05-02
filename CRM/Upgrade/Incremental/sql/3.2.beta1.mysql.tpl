-- CRM-5224
SELECT @country_id := id FROM civicrm_country WHERE name = 'United Kingdom';
INSERT IGNORE INTO civicrm_state_province (id, country_id,  abbreviation, name) VALUES (2712, @country_id, 'LIN', 'Lincolnshire');

-- CRM-5938

SELECT  @domainID := min(id) FROM civicrm_domain;
INSERT INTO `civicrm_dashboard` 
    ( `domain_id`, {localize field='label'}`label`{/localize}, `url`, `content`, `permission`, `permission_operator`, `column_no`, `is_minimized`, `is_active`, `weight`, `created_date`, `is_fullscreen`, `is_reserved`) 
    VALUES 
    ( @domainID, {localize}'My Cases'{/localize}, 'civicrm/dashlet/myCases&reset=1&snippet=4', NULL, 'access my cases and activities', NULL , 0, 0, 1, 2, NULL, 1, 1),
    ( @domainID, {localize}'All Cases'{/localize}, 'civicrm/dashlet/allCases&reset=1&snippet=4', NULL, 'access all cases and activities', NULL , 0, 0, 1, 3, NULL, 1, 1);

-- CRM-6294
SELECT @option_group_id_eventBadge := max(id) from civicrm_option_group where name = 'event_badge';
{if $multilingual}
    INSERT INTO civicrm_option_value
	(option_group_id, {foreach from=$locales item=locale}label_{$locale}, description_{$locale}, {/foreach} value, name, weight, is_active, component_id )
    VALUES
        (@option_group_id_eventBadge , {foreach from=$locales item=locale}'With Logo', 'You can set your own background image', {/foreach} '3', 'CRM_Event_Badge_Logo', 1,   1, NULL );
{else}
    INSERT INTO civicrm_option_value
	(option_group_id, label, description, value, name, weight, is_active, component_id )
    VALUES
        (@option_group_id_eventBadge , '{ts escape="sql"}With Logo{/ts}', '{ts escape="sql"}You can set your own background image{/ts}', '3', 'CRM_Event_Badge_Logo', 1,   1, NULL );
{/if}


