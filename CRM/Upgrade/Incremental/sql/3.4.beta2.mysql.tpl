-- CRM-7817
{include file='../CRM/Upgrade/3.4.beta2.msg_template/civicrm_msg_template.tpl'}


-- CRM-7801
SELECT @domain_id       := min(id) FROM civicrm_domain;
SELECT @nav_case        := id FROM civicrm_navigation WHERE name = 'CiviCase';
SELECT @nav_case_weight := MAX(ROUND(weight)) from civicrm_navigation WHERE parent_id = @nav_case;

INSERT INTO civicrm_navigation
        ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
	( @domain_id, 'civicrm/admin/options/encounter_medium&group=encounter_medium&reset=1', '{ts escape="sql"}Encounter Medium{/ts}','Encounter Medium',  'administer CiviCase', NULL, @nav_case, '1', NULL, @nav_case_weight+1 );

