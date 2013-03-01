{include file='../CRM/Upgrade/3.1.beta4.msg_template/civicrm_msg_template.tpl'}

-- Set default participant role filter = 1, CRM-4924
   UPDATE   civicrm_option_value val
LEFT JOIN   civicrm_option_group gr ON ( gr.id = val.option_group_id ) 
      SET   val.filter = 1
    WHERE   gr.name = 'participant_role'
      AND   val.name IN ( 'Attendee', 'Host', 'Speaker', 'Volunteer' );

SELECT @option_group_id_report := max(id) from civicrm_option_group where name = 'report_template';
INSERT INTO civicrm_option_value
    (option_group_id, {localize field='label'}`label`{/localize}, {localize field='description'}description{/localize}, value, name, weight, is_active, component_id )
VALUES
    (@option_group_id_report , {localize}'{ts escape="sql"}Activity Report (Summary){/ts}'{/localize}, {localize}'{ts escape="sql"}Shows activity statistics by type / date{/ts}'{/localize}, 'activitySummary', 'CRM_Report_Form_ActivitySummary', 28, 1, NULL );