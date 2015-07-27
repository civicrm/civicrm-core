-- CRM-7494
-- update navigation.

UPDATE civicrm_navigation
   SET name  = 'Survey Report (Detail)',
       label = 'Survey Report (Detail)'
 WHERE name LIKE 'Walk List Survey Report';

-- update report instance.

UPDATE civicrm_report_instance
   SET title       = 'Survey Report (Detail)',
       description = 'Detailed report for canvassing, phone-banking, walk lists or other surveys.'
 WHERE report_id LIKE 'survey/detail';

-- update report template option values.
{if $multilingual}

  {foreach from=$locales item=loc}

    UPDATE  civicrm_option_value val
INNER JOIN  civicrm_option_group grp ON ( grp.id = val.option_group_id )
       SET  val.label_{$loc}       = '{ts escape="sql"}Survey Report (Detail){/ts}',
            val.description_{$loc} = '{ts escape="sql"}Detailed report for canvassing, phone-banking, walk lists or other surveys.{/ts}'
     WHERE  val.name = 'CRM_Report_Form_Campaign_SurveyDetails'
       AND  grp.name = 'report_template';

  {/foreach}

{else}

    UPDATE  civicrm_option_value val
INNER JOIN  civicrm_option_group grp ON ( grp.id = val.option_group_id )
       SET  val.label       = '{ts escape="sql"}Survey Report (Detail){/ts}',
            val.description = '{ts escape="sql"}Detailed report for canvassing, phone-banking, walk lists or other surveys.{/ts}'
     WHERE  val.name = 'CRM_Report_Form_Campaign_SurveyDetails'
       AND  grp.name = 'report_template';

{/if}

-- get rid of standalone tables CRM-7672
DROP TABLE IF EXISTS civicrm_openid_associations;
DROP TABLE IF EXISTS civicrm_openid_nonces;

-- insert 5395 Logo style event name badge CRM-7695
    SELECT @option_group_id_eventBadge := max(id) from civicrm_option_group where name = 'event_badge';
    {if $multilingual}
        INSERT INTO civicrm_option_value
      (option_group_id, {foreach from=$locales item=locale}label_{$locale}, description_{$locale}, {/foreach} value, name, weight, is_active, component_id )
        VALUES
            (@option_group_id_eventBadge , {foreach from=$locales item=locale}'5395 with Logo', 'Avery 5395 compatible labels with logo (4 up by 2, 59.2mm x 85.7mm)', {/foreach} '4', 'CRM_Event_Badge_Logo5395', 1,   1, NULL );
    {else}
        INSERT INTO civicrm_option_value
      (option_group_id, label, description, value, name, weight, is_active, component_id )
        VALUES
            (@option_group_id_eventBadge , '{ts escape="sql"}5395 with Logo{/ts}', '{ts escape="sql"}Avery 5395 compatible labels with logo (4 up by 2, 59.2mm x 85.7mm){/ts}', '4', 'CRM_Event_Badge_Logo5395', 1,   1, NULL );
    {/if}
