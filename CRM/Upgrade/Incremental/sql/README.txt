+--------------------------------------------------------------------+
| Copyright CiviCRM LLC. All rights reserved.                        |
|                                                                    |
| This work is published under the GNU AGPLv3 license with some      |
| permitted exceptions and without any warranty. For full license    |
| and copyright information, see https://civicrm.org/licensing       |
+--------------------------------------------------------------------+

This README file documents conventions used in Incremental Upgrade SQL statements.

====================
Specifying Domain ID
====================
SQL statements in CRM/Upgrade/Incremental/sql templates which include domain_id should use {$domainID} which is defined in CRM_Upgrade_Form::processLocales()
and is therefore available to all incremental .tpl files.

Correct usage:
----------------------------------------------------
INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( {$domainID}, 'Mailing Detail Report', 'mailing/detail', 'Provides reporting on Intended and Successful Deliveries, Unsubscribes and Opt-outs, Replies and Forwards.', '', '{literal}a:30:{s:6:"fields";a:6:{s:9:"sort_name";s:1:"1";s:12:"mailing_name";s:1:"1";s:11:"delivery_id";s:1:"1";s:14:"unsubscribe_id";s:1:"1";s:9:"optout_id";s:1:"1";s:5:"email";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:13:"mailing_id_op";s:2:"in";s:16:"mailing_id_value";a:0:{}s:18:"delivery_status_op";s:2:"eq";s:21:"delivery_status_value";s:0:"";s:18:"is_unsubscribed_op";s:2:"eq";s:21:"is_unsubscribed_value";s:0:"";s:12:"is_optout_op";s:2:"eq";s:15:"is_optout_value";s:0:"";s:13:"is_replied_op";s:2:"eq";s:16:"is_replied_value";s:0:"";s:15:"is_forwarded_op";s:2:"eq";s:18:"is_forwarded_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:9:"order_bys";a:1:{i:1;a:2:{s:6:"column";s:9:"sort_name";s:5:"order";s:3:"ASC";}}s:11:"description";s:21:"Mailing Detail Report";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:1:"0";s:9:"parent_id";s:0:"";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}{/literal}');
----------------------------------------------------

Previously, a SELECT INTO @domainID was used. This method is deprecated and should NOT be used:

----------------------------------------------------
-- get domain id
SELECT  @domainID := min(id) FROM civicrm_domain;
----------------------------------------------------


==========================
Translate or Localize Text
==========================
Text which is visible to users needs to be translated or set to localizable (which encompasses translation). Localize is used for fields that support
multiple language values in multi-language installs. Check the schema definition for a given field if you're not sure whether a string is localizable.

For example, to check if civicrm_option_value.label is localizable look at the Label field in xml/schema/Core/OptionValue.xml
<field>
     <name>label</name>
     <title>Option Label</title>
     <type>varchar</type>
     <required>true</required>
     <length>255</length>
     <localizable>true</localizable>
     <comment>Option string as displayed to users - e.g. the label in an HTML OPTION tag.</comment>
     <add>1.5</add>
</field>

Localizable is true so we need to do inserts using the {localize} tag around that column. Check the Option Value insert example in the next section.

If a field is NOT localizable, but we just need to make sure it can be translated - use the {ts} tag with sql escape parameter as shown below.

----------------------------------------------------
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( {$domainID}, 'civicrm/admin&reset=1', '{ts escape="sql" skip="true"}Administration Console{/ts}', 'Administration Console', 'administer CiviCRM', '', @adminlastID, '1', NULL, 1 );
----------------------------------------------------

===========================
Inserting Option Value Rows
===========================
When you insert an option value row during an upgrade, do NOT use hard-coded integers for the "value" and "weight" columns. Since in many cases additional
option value rows can be defined by users, you can't determine the next available unique value by looking at a sample installation. Use SELECT max() into
a variable and increment it.

Here's an example which localizes the Label and grabs next available integer for value and weight columns
------------------------------------------------------------------------------
SELECT @caseCompId := id FROM `civicrm_component` where `name` like 'CiviCase';

SELECT @option_group_id_activity_type := max(id) from civicrm_option_group where name = 'activity_type';
SELECT @max_val    := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = @option_group_id_activity_type;
SELECT @max_wt     := max(weight) from civicrm_option_value where option_group_id=@option_group_id_activity_type;

INSERT INTO civicrm_option_value
  (option_group_id,                {localize field='label'}label{/localize}, {localize field='description'}description{/localize}, value,                           name,               weight,                        filter, component_id)
VALUES
    (@option_group_id_activity_type, {localize}'{ts escape="sql"}Change Custom Data{/ts}'{/localize},{localize}''{/localize}, (SELECT @max_val := @max_val+1), 'Change Custom Data', (SELECT @max_wt := @max_wt+1), 0, @caseCompId);


If you are inserting an option value row that might already exist (for example, a Relative Date Filter could have already been added manually), we should only insert it if it doesn't already exist.

Here's an example which adds a Relative Date Filter with localization, the next available weight and does not insert if the value already exists (FROM DUAL is required for MariaDB)
------------------------------------------------------------------------------
SELECT @option_group_id_date_filter := max(id) from civicrm_option_group where name = 'relative_date_filters';

SELECT @max_wt := max(weight) from civicrm_option_value where option_group_id = @option_group_id_date_filter;
INSERT INTO
  `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`, `icon`)
SELECT
  @option_group_id_date_filter, {localize}'{ts escape="sql"}Previous 2 fiscal years{/ts}'{/localize}, 'previous_2.fiscal_year', 'previous_2.fiscal_year', NULL, NULL, 0, (SELECT @max_wt := @max_wt+1), {localize}NULL{/localize}, 0, 0, 1, NULL, NULL, NULL
FROM DUAL
WHERE NOT EXISTS (SELECT * FROM civicrm_option_value WHERE `value`='previous_2.fiscal_year' AND `option_group_id` = @option_group_id_date_filter);

------------------------------------------------------------------------------
More details: https://docs.civicrm.org/dev/en/latest/translation/database/#localised-fields-schema-changes
