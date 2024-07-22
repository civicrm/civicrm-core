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

INSERT IGNORE INTO `civicrm_case_type` (  `title`, `name`, `description`, `weight`, `is_reserved`, `is_active`) VALUES
  ('{ts}Housing Support{/ts}', 'housing_support', '{ts}Help homeless individuals obtain temporary and long-term housing{/ts}', @max_wt + 1, 0, 1),
  ('{ts}Adult Day Care Referral{/ts}', 'adult_day_care_referral', '{ts}Arranging adult day care for senior individuals{/ts}', @max_wt + 2, 0, 1);

-- /*******************************************************
-- *
-- * Case Status - Set names for Open and Closed
-- *
-- *******************************************************/
SELECT @csgId        := max(id) from civicrm_option_group where name = 'case_status';
UPDATE civicrm_option_value SET name = 'Open' where option_group_id = @csgId AND label = 'Ongoing';
UPDATE civicrm_option_value SET name = 'Closed' where option_group_id = @csgId AND label = 'Resolved';

-- /*******************************************************
-- *
-- * Activity Types
-- *
-- *******************************************************/
SELECT @option_group_id_activity_type        := max(id) from civicrm_option_group where name = 'activity_type';

SELECT @max_val := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = @option_group_id_activity_type;

INSERT INTO `civicrm_option_value` ( `option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id` )
(SELECT @option_group_id_activity_type, 'Medical evaluation', (SELECT @max_val := @max_val+1), 'Medical evaluation',  NULL, 0,  0, (SELECT @max_val := @max_val+1), '', 0, 0, 1, @caseCompId
 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_option_value`  WHERE `name` = 'Medical evaluation'));

INSERT INTO `civicrm_option_value` ( `option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id` )
(SELECT @option_group_id_activity_type, 'Mental health evaluation', (SELECT @max_val := @max_val+1), 'Mental health evaluation',  NULL, 0,  0, (SELECT @max_val := @max_val+1), '', 0, 0, 1, @caseCompId
 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_option_value`  WHERE `name` = 'Mental health evaluation'));

INSERT INTO `civicrm_option_value` ( `option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id` )
(SELECT @option_group_id_activity_type, 'Secure temporary housing', (SELECT @max_val := @max_val+1), 'Secure temporary housing',  NULL, 0,  0, (SELECT @max_val := @max_val+1), '', 0, 0, 1, @caseCompId
 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_option_value`  WHERE `name` = 'Secure temporary housing'));

INSERT INTO `civicrm_option_value` ( `option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id` )
(SELECT @option_group_id_activity_type, 'Income and benefits stabilization', (SELECT @max_val := @max_val+1), 'Income and benefits stabilization',  NULL, 0,  0, (SELECT @max_val := @max_val+1), '', 0, 0, 1, @caseCompId
 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_option_value`  WHERE `name` = 'Income and benefits stabilization'));

INSERT INTO `civicrm_option_value` ( `option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id` )
(SELECT @option_group_id_activity_type, 'Long-term housing plan', (SELECT @max_val := @max_val+1), 'Long-term housing plan',  NULL, 0,  0, (SELECT @max_val := @max_val+1), '', 0, 0, 1, @caseCompId
 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_option_value`  WHERE `name` = 'Long-term housing plan'));

INSERT INTO `civicrm_option_value` ( `option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id` )
(SELECT @option_group_id_activity_type, 'ADC referral', (SELECT @max_val := @max_val+1), 'ADC referral',  NULL, 0,  0, (SELECT @max_val := @max_val+1), '', 0, 0, 1, @caseCompId
 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_option_value`  WHERE `name` = 'ADC referral'));

-- /*******************************************************
-- *
-- * Relationship Types
-- *
-- *******************************************************/
INSERT INTO `civicrm_relationship_type` ( `name_a_b`, `label_a_b`, `name_b_a`, `label_b_a`, `description`, `contact_type_a`, `contact_type_b`, `is_reserved`, `is_active` ) (SELECT 'Homeless Services Coordinator is', 'Homeless Services Coordinator is', 'Homeless Services Coordinator', 'Homeless Services Coordinator',  'Homeless Services Coordinator', 'Individual', 'Individual', 0, 1 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_relationship_type`  WHERE `name_a_b` = 'Homeless Services Coordinator is'));


INSERT INTO `civicrm_relationship_type` ( `name_a_b`, `label_a_b`, `name_b_a`, `label_b_a`, `description`, `contact_type_a`, `contact_type_b`, `is_reserved`, `is_active` ) (
SELECT 'Health Services Coordinator is', 'Health Services Coordinator is', 'Health Services Coordinator', 'Health Services Coordinator',  'Health Services Coordinator', 'Individual', 'Individual', 0, 1 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_relationship_type`  WHERE `name_a_b` = 'Health Services Coordinator is'));


INSERT INTO `civicrm_relationship_type` ( `name_a_b`, `label_a_b`, `name_b_a`, `label_b_a`, `description`, `contact_type_a`, `contact_type_b`, `is_reserved`, `is_active` ) (
SELECT 'Senior Services Coordinator is', 'Senior Services Coordinator is', 'Senior Services Coordinator', 'Senior Services Coordinator', 'Senior Services Coordinator', 'Individual', 'Individual', 0, 1 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_relationship_type`  WHERE `name_a_b` = 'Senior Services Coordinator is'));

INSERT INTO `civicrm_relationship_type` ( `name_a_b`, `label_a_b`, `name_b_a`, `label_b_a`, `description`, `contact_type_a`, `contact_type_b`, `is_reserved`, `is_active` ) (
SELECT 'Benefits Specialist is', 'Benefits Specialist is', 'Benefits Specialist', 'Benefits Specialist', 'Benefits Specialist', 'Individual', 'Individual', 0, 1 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_relationship_type`  WHERE `name_a_b` = 'Benefits Specialist is'));

-- /*******************************************************
-- *
-- * Case Resources Group
-- *
-- *******************************************************/

INSERT INTO `civicrm_group` (`name`, `title`,  `frontend_title`, `description`, `source`, `saved_search_id`, `is_active`, `visibility`, `where_clause`, `select_tables`, `where_tables`, `group_type`, `cache_date`, `parents`, `children`, `is_hidden` ) (SELECT 'Case_Resources', 'Case Resources', 'Case Resources', 'Contacts in this group are listed with their phone number and email when viewing case. You also can send copies of case activities to these contacts.', NULL, NULL, 1, 'User and User Admin Only', ' ( `civicrm_group_contact-5`.group_id IN ( 5 ) AND `civicrm_group_contact-5`.status IN ("Added") ) ', 'a:10:{ldelim}s:15:"civicrm_contact";i:1;s:15:"civicrm_address";i:1;s:22:"civicrm_state_province";i:1;s:15:"civicrm_country";i:1;s:13:"civicrm_email";i:1;s:13:"civicrm_phone";i:1;s:10:"civicrm_im";i:1;s:19:"civicrm_worldregion";i:1;s:25:"`civicrm_group_contact-5`";s:114:" LEFT JOIN civicrm_group_contact `civicrm_group_contact-5` ON contact_a.id = `civicrm_group_contact-5`.contact_id ";s:6:"gender";i:1;{rdelim}', 'a:2:{ldelim}s:15:"civicrm_contact";i:1;s:25:"`civicrm_group_contact-5`";s:114:" LEFT JOIN civicrm_group_contact `civicrm_group_contact-5` ON contact_a.id = `civicrm_group_contact-5`.contact_id ";{rdelim}', '2', NULL, NULL, NULL, 0 FROM dual WHERE NOT EXISTS (SELECT * FROM `civicrm_group`  WHERE `name` = 'Case_Resources'));
