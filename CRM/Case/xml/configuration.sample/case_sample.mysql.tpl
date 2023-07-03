-- /**********************************************************************
-- *
-- * Configuration Data for CiviCase Component
-- * For: Sample Case Types - Housing Support and Adult Day Care Referral
-- *
-- **********************************************************************/

SELECT @caseCompId := id FROM `civicrm_component` where `name` like 'CiviCase';

-- /*******************************************************
-- *
-- * Case Types
-- *
-- *******************************************************/
SELECT @max_wt  :=  COALESCE( max(weight), 0 ) from civicrm_case_type;

INSERT IGNORE INTO `civicrm_case_type` (  {localize field='title'}`title`{/localize}, `name`, {localize field='description'}`description`{/localize}, `weight`, `is_reserved`, `is_active`) VALUES
  ({localize}'{ts escape="sql"}Housing Support{/ts}'{/localize}, 'housing_support', {localize}'{ts escape="sql"}Help homeless individuals obtain temporary and long-term housing{/ts}'{/localize}, @max_wt + 1, 0, 1),
  ({localize}'{ts escape="sql"}Adult Day Care Referral{/ts}'{/localize}, 'adult_day_care_referral', {localize}'{ts escape="sql"}Arranging adult day care for senior individuals{/ts}'{/localize}, @max_wt + 2, 0, 1);

-- CRM-15343 set the auto increment civicrm_case_type.id start point to max id to avoid conflict in future insertion
SELECT @max_case_type_id := max(id) from civicrm_case_type;
SET @query  = CONCAT("ALTER TABLE civicrm_case_type AUTO_INCREMENT = ", IFNULL(@max_case_type_id,1));
PREPARE alter_case_type_auto_inc FROM @query;
EXECUTE alter_case_type_auto_inc;
DEALLOCATE PREPARE alter_case_type_auto_inc;

-- /*******************************************************
-- *
-- * Case Status - Set names for Open and Closed
-- *
-- *******************************************************/
SELECT @csgId        := max(id) from civicrm_option_group where name = 'case_status';
{if $multilingual}
  {foreach from=$locales item=locale}
    UPDATE civicrm_option_value SET name = 'Open' where option_group_id = @csgId AND label_{$locale} = 'Ongoing';
    UPDATE civicrm_option_value SET name = 'Closed' where option_group_id = @csgId AND label_{$locale} = 'Resolved';
  {/foreach}
{else}
  UPDATE civicrm_option_value SET name = 'Open' where option_group_id = @csgId AND label = 'Ongoing';
  UPDATE civicrm_option_value SET name = 'Closed' where option_group_id = @csgId AND label = 'Resolved';
{/if}

-- /*******************************************************
-- *
-- * Activity Types
-- *
-- *******************************************************/
SELECT @option_group_id_activity_type        := max(id) from civicrm_option_group where name = 'activity_type';

SELECT @max_val := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = @option_group_id_activity_type;

INSERT INTO `civicrm_option_value` ( `option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id` )
(SELECT @option_group_id_activity_type, {localize}'{ts escape="sql"}Medical evaluation{/ts}'{/localize}, (SELECT @max_val := @max_val+1), 'Medical evaluation',  NULL, 0,  0, (SELECT @max_val := @max_val+1),  0, 0, 1, @caseCompId
 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_option_value`  WHERE `name` = 'Medical evaluation'));

INSERT INTO `civicrm_option_value` ( `option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`,  `is_optgroup`, `is_reserved`, `is_active`, `component_id` )
(SELECT @option_group_id_activity_type, {localize}'{ts escape="sql"}Mental health evaluation{/ts}'{/localize}, (SELECT @max_val := @max_val+1), 'Mental health evaluation',  NULL, 0,  0, (SELECT @max_val := @max_val+1),  0, 0, 1, @caseCompId
 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_option_value`  WHERE `name` = 'Mental health evaluation'));

INSERT INTO `civicrm_option_value` ( `option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`,  `is_optgroup`, `is_reserved`, `is_active`, `component_id` )
(SELECT @option_group_id_activity_type, {localize}'{ts escape="sql"}Secure temporary housing{/ts}'{/localize}, (SELECT @max_val := @max_val+1), 'Secure temporary housing',  NULL, 0,  0, (SELECT @max_val := @max_val+1),  0, 0, 1, @caseCompId
 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_option_value`  WHERE `name` = 'Secure temporary housing'));

INSERT INTO `civicrm_option_value` ( `option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`,  `is_optgroup`, `is_reserved`, `is_active`, `component_id` )
(SELECT @option_group_id_activity_type, {localize}'{ts escape="sql"}Income and benefits stabilization{/ts}'{/localize}, (SELECT @max_val := @max_val+1), 'Income and benefits stabilization',  NULL, 0,  0, (SELECT @max_val := @max_val+1),  0, 0, 1, @caseCompId
 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_option_value`  WHERE `name` = 'Income and benefits stabilization'));

INSERT INTO `civicrm_option_value` ( `option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`,  `is_optgroup`, `is_reserved`, `is_active`, `component_id` )
(SELECT @option_group_id_activity_type, {localize}'{ts escape="sql"}Long-term housing plan{/ts}'{/localize}, (SELECT @max_val := @max_val+1), 'Long-term housing plan',  NULL, 0,  0, (SELECT @max_val := @max_val+1),  0, 0, 1, @caseCompId
 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_option_value`  WHERE `name` = 'Long-term housing plan'));

INSERT INTO `civicrm_option_value` ( `option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`,  `is_optgroup`, `is_reserved`, `is_active`, `component_id` )
(SELECT @option_group_id_activity_type, {localize}'{ts escape="sql"}ADC referral{/ts}'{/localize}, (SELECT @max_val := @max_val+1), 'ADC referral',  NULL, 0,  0, (SELECT @max_val := @max_val+1),  0, 0, 1, @caseCompId
 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_option_value`  WHERE `name` = 'ADC referral'));

-- /*******************************************************
-- *
-- * Relationship Types
-- *
-- *******************************************************/
INSERT INTO `civicrm_relationship_type` ( `name_a_b`, {localize field='label_a_b'}`label_a_b`{/localize}, `name_b_a`, {localize field='label_b_a'}`label_b_a`{/localize}, {localize field='description'}`description`{/localize}, `contact_type_a`, `contact_type_b`, `is_reserved`, `is_active` ) (SELECT 'Homeless Services Coordinator is', {localize}'{ts escape="sql"}Homeless Services Coordinator is{/ts}'{/localize}, 'Homeless Services Coordinator', {localize}'{ts escape="sql"}Homeless Services Coordinator{/ts}'{/localize},  {localize}'{ts escape="sql"}Homeless Services Coordinator{/ts}'{/localize}, 'Individual', 'Individual', 0, 1 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_relationship_type`  WHERE `name_a_b` = 'Homeless Services Coordinator is'));


INSERT INTO `civicrm_relationship_type` ( `name_a_b`, {localize field='label_a_b'}`label_a_b`{/localize}, `name_b_a`, {localize field='label_b_a'}`label_b_a`{/localize}, {localize field='description'}`description`{/localize}, `contact_type_a`, `contact_type_b`, `is_reserved`, `is_active` ) (
SELECT 'Health Services Coordinator is', {localize}'{ts escape="sql"}Health Services Coordinator is{/ts}'{/localize}, 'Health Services Coordinator', {localize}'{ts escape="sql"}Health Services Coordinator{/ts}'{/localize},  {localize}'{ts escape="sql"}Health Services Coordinator{/ts}'{/localize}, 'Individual', 'Individual', 0, 1 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_relationship_type`  WHERE `name_a_b` = 'Health Services Coordinator is'));


INSERT INTO `civicrm_relationship_type` ( `name_a_b`, {localize field='label_a_b'}`label_a_b`{/localize}, `name_b_a`, {localize field='label_b_a'}`label_b_a`{/localize}, {localize field='description'}`description`{/localize}, `contact_type_a`, `contact_type_b`, `is_reserved`, `is_active` ) (
SELECT 'Senior Services Coordinator is', {localize}'{ts escape="sql"}Senior Services Coordinator is{/ts}'{/localize}, 'Senior Services Coordinator', {localize}'{ts escape="sql"}Senior Services Coordinator{/ts}'{/localize}, {localize}'{ts escape="sql"}Senior Services Coordinator{/ts}'{/localize}, 'Individual', 'Individual', 0, 1 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_relationship_type`  WHERE `name_a_b` = 'Senior Services Coordinator is'));

INSERT INTO `civicrm_relationship_type` ( `name_a_b`, {localize field='label_a_b'}`label_a_b`{/localize}, `name_b_a`, {localize field='label_b_a'}`label_b_a`{/localize}, {localize field='description'}`description`{/localize}, `contact_type_a`, `contact_type_b`, `is_reserved`, `is_active` ) (
SELECT 'Benefits Specialist is', {localize}'{ts escape="sql"}Benefits Specialist is{/ts}'{/localize}, 'Benefits Specialist', {localize}'{ts escape="sql"}Benefits Specialist{/ts}'{/localize}, {localize}'{ts escape="sql"}Benefits Specialist{/ts}'{/localize}, 'Individual', 'Individual', 0, 1 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_relationship_type`  WHERE `name_a_b` = 'Benefits Specialist is'));

-- /*******************************************************
-- *
-- * Case Resources Group
-- *
-- *******************************************************/

INSERT INTO `civicrm_group` (  `name`, {localize field='title'}`title`{/localize},{localize field='frontend_title'}`frontend_title`{/localize}, `description`, `source`, `saved_search_id`, `is_active`, `visibility`, `where_clause`, `select_tables`, `where_tables`, `group_type`, `cache_date`, `parents`, `children`, `is_hidden` ) (SELECT 'Case_Resources', {localize}'{ts escape="sql"}Case Resources{/ts}'{/localize},{localize}'{ts escape="sql"}Case Resources{/ts}'{/localize}, 'Contacts in this group are listed with their phone number and email when viewing case. You also can send copies of case activities to these contacts.', NULL, NULL, 1, 'User and User Admin Only', ' ( `civicrm_group_contact-5`.group_id IN ( 5 ) AND `civicrm_group_contact-5`.status IN ("Added") ) ', '{literal}a:10:{s:15:"civicrm_contact";i:1;s:15:"civicrm_address";i:1;s:22:"civicrm_state_province";i:1;s:15:"civicrm_country";i:1;s:13:"civicrm_email";i:1;s:13:"civicrm_phone";i:1;s:10:"civicrm_im";i:1;s:19:"civicrm_worldregion";i:1;s:25:"`civicrm_group_contact-5`";s:114:" LEFT JOIN civicrm_group_contact `civicrm_group_contact-5` ON contact_a.id = `civicrm_group_contact-5`.contact_id ";s:6:"gender";i:1;}{/literal}', '{literal}a:2:{s:15:"civicrm_contact";i:1;s:25:"`civicrm_group_contact-5`";s:114:" LEFT JOIN civicrm_group_contact `civicrm_group_contact-5` ON contact_a.id = `civicrm_group_contact-5`.contact_id ";}{/literal}', '2', NULL, NULL, NULL, 0 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_group`  WHERE `name` = 'Case_Resources'));
