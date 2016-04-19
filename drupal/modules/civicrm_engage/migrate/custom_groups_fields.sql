update civicrm_custom_group set table_name = replace(table_name, substring(table_name, - locate('_', reverse(table_name))), '') 
where replace(table_name, substring(table_name, - locate('_', reverse(table_name))), '') 
IN ( 
'civicrm_value_primary_contact',
'civicrm_value_voter_info',
'civicrm_value_core_info',
'civicrm_value_grassroots_info',
'civicrm_value_leadership_level',
'civicrm_value_contact_info',
'civicrm_value_grant_info',
'civicrm_value_proposal_info',
'civicrm_value_participant_info',
'civicrm_value_contribution_source',
'civicrm_value_door_knock_responses',
'civicrm_value_phone_bank_responses',
'civicrm_value_event_details',
'civicrm_value_source_details',
'civicrm_value_activity_source_details',
'civicrm_value_communication_details',
'civicrm_value_event_campaign_details',
'civicrm_value_organizational_details',
'civicrm_value_demographics'
);

update civicrm_custom_field set column_name = replace(column_name, substring(column_name, - locate('_', reverse(column_name))), '')
where custom_group_id IN (select id from civicrm_custom_group where table_name IN (
'civicrm_value_primary_contact',
'civicrm_value_voter_info',
'civicrm_value_core_info',
'civicrm_value_grassroots_info',
'civicrm_value_leadership_level',
'civicrm_value_contact_info',
'civicrm_value_grant_info',
'civicrm_value_proposal_info',
'civicrm_value_participant_info',
'civicrm_value_contribution_source',
'civicrm_value_door_knock_responses',
'civicrm_value_phone_bank_responses',
'civicrm_value_event_details',
'civicrm_value_source_details',
'civicrm_value_activity_source_details',
'civicrm_value_communication_details',
'civicrm_value_event_campaign_details',
'civicrm_value_organizational_details',
'civicrm_value_demographics'
));

update civicrm_custom_field set column_name = TRIM(TRAILING '_' FROM column_name)
where custom_group_id IN (select id from civicrm_custom_group where table_name IN (
'civicrm_value_primary_contact',
'civicrm_value_voter_info',
'civicrm_value_core_info',
'civicrm_value_grassroots_info',
'civicrm_value_leadership_level',
'civicrm_value_contact_info',
'civicrm_value_grant_info',
'civicrm_value_proposal_info',
'civicrm_value_participant_info',
'civicrm_value_contribution_source',
'civicrm_value_door_knock_responses',
'civicrm_value_phone_bank_responses',
'civicrm_value_event_details',
'civicrm_value_source_details',
'civicrm_value_activity_source_details',
'civicrm_value_communication_details',
'civicrm_value_event_campaign_details',
'civicrm_value_organizational_details',
'civicrm_value_demographics'
));

update civicrm_custom_group set table_name = 'civicrm_value_source_details' where table_name = 'civicrm_value_activity_source_details';

-- do column_mapping renaming, check migrate.sql

update civicrm_custom_field set column_name = 'county_name' 
where column_name like 'county%' AND 
custom_group_id IN (select id from civicrm_custom_group where table_name = 'civicrm_value_voter_info');

update civicrm_custom_field set column_name = 'constituent_type' 
where column_name like 'contact_type' AND custom_group_id IN 
(select id from civicrm_custom_group where table_name IN ( 'civicrm_value_demographics', 'civicrm_value_contact_info' ));

update civicrm_custom_field set column_name = 'reminder_date' 
where column_name like 'reminderdate' AND 
custom_group_id IN (select id from civicrm_custom_group where table_name = 'civicrm_value_participant_info');

update civicrm_custom_field set column_name = 'is_this_a_field_engage_campaign' 
where column_name like 'is_this_a_field_canvass_campaign' AND 
custom_group_id IN (select id from civicrm_custom_group where table_name = 'civicrm_value_door_knock_responses');

update civicrm_custom_group 
set name = 'Demographics', title = 'Demographics', style = 'Tab' 
where table_name = 'civicrm_value_demographics';

-- add missing custom groups in db like 'civicrm_value_organizational_details' and 'core_info'. add fields if not already present

INSERT INTO `civicrm_custom_group` (`name`, `title`, `extends`, `extends_entity_column_id`, `extends_entity_column_value`, `style`, `collapse_display`, `help_pre`, `help_post`, `weight`, `is_active`, `table_name`, `is_multiple`, `min_multiple`, `max_multiple`, `collapse_adv_display`, `created_id`, `created_date`) VALUES
('Constituent_Info__Individuals', 'Constituent Info - Individuals', 'Individual', NULL, NULL, 'Inline', 0, '', '', 4, 1, 'civicrm_value_core_info', 0, NULL, NULL, 0, NULL, NULL),
('Organizational_Details', 'Organizational Details', 'Organization', NULL, NULL, 'Inline', 1, '', '', 17, 1, 'civicrm_value_organizational_details', 0, NULL, NULL, 0, NULL, NULL);

select @cg_core_info_id  := id from civicrm_custom_group where table_name = 'civicrm_value_core_info';
select @cg_org_detail_id := id from civicrm_custom_group where table_name = 'civicrm_value_organizational_details';
select @cg_demographics_id := id from civicrm_custom_group where table_name = 'civicrm_value_demographics';
select @cg_grassroots_id := id from civicrm_custom_group where table_name = 'civicrm_value_grassroots_info';
select @cg_event_details_id := id from civicrm_custom_group where table_name = 'civicrm_value_event_details';
select @cg_leadership_level_id := id from civicrm_custom_group where table_name = 'civicrm_value_leadership_level';

INSERT INTO `civicrm_option_group` (`name`, `label`, `description`, `is_reserved`, `is_active`) VALUES
(CONCAT('rating_' , DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')) , 'Rating', NULL, NULL, 1);
SET @opt_group_id := LAST_INSERT_ID();
INSERT INTO `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `domain_id`, `visibility_id`) VALUES
(@opt_group_id, 'Supporter or Ally', 'Supporter or Ally', NULL, NULL, NULL, 0, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
(@opt_group_id, 'Our Network', 'Our Network', 'Our_Network', NULL, NULL, 0, 1, NULL, 0, 0, 1, NULL, NULL, NULL);

INSERT INTO `civicrm_custom_field` (`custom_group_id`, `label`, `data_type`, `html_type`, `default_value`, `is_required`, `is_searchable`, `is_search_range`, `weight`, `help_pre`, `help_post`, `mask`, `attributes`, `javascript`, `is_active`, `is_view`, `options_per_line`, `text_length`, `start_date_years`, `end_date_years`, `date_format`, `time_format`, `note_columns`, `note_rows`, `column_name`, `option_group_id`) VALUES
(@cg_org_detail_id, 'Rating', 'String', 'Select', NULL, 0, 1, 0, 2, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, 'MdY', NULL, 60, 4, 'rating', @opt_group_id);

-- some of the old fields needs to be migrated to newly inserted group
update civicrm_custom_field set custom_group_id = @cg_core_info_id 
where column_name IN ('constituent_type', 'staff_responsible', 'date_started', 'other_name', 'how_started') and custom_group_id = @cg_demographics_id;

update civicrm_custom_field set custom_group_id = @cg_grassroots_id 
where column_name IN ('other_affiliation', 'toxinformer_flag', 'community', 'owner_or_renter') and custom_group_id = @cg_demographics_id;

-- check dropped/migrated_to_core columns and drop them from custom_fields table too

delete from civicrm_custom_field 
where column_name IN ('salutation', 'addressee', 'email_salutation', 'gender', 'dob') and 
custom_group_id = @cg_demographics_id;

delete from civicrm_custom_field 
where column_name IN ('event_external_id') and custom_group_id = @cg_event_details_id;

delete from civicrm_custom_field 
where column_name IN ('ehc_board_and_staff') and custom_group_id = @cg_leadership_level_id;

-- add new report templates
SELECT @option_group_id_report         := max(id) from civicrm_option_group where name = 'report_template';
SELECT @maxWeight  := max(weight) from civicrm_option_value where option_group_id=@option_group_id_report;

INSERT INTO civicrm_option_value
    (option_group_id, label, value, name, weight, description, is_active, component_id)
VALUES
    (@option_group_id_report, 'Phonebank List', 'contact/phonebank', 'Engage_Report_Form_CallList', (SELECT @maxWeight := @maxWeight + 1), 'Phonebank List', 1, NULL),
    (@option_group_id_report, 'Canvass Walk List', 'contact/walklist', 'Engage_Report_Form_WalkList', (SELECT @maxWeight := @maxWeight + 1), 'Canvass Walk List', 1, NULL);
