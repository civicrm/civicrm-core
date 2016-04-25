-- drop standard profiles
delete from civicrm_uf_join where uf_group_id IN (select id from civicrm_uf_group where title IN ('Update Contact Information','Update Grassroots Info','Update Event Participant Info','Update Voter Info','Update Constituent Info', 'Update Core Info'));
-- below also deletes fields
delete from civicrm_uf_group where title IN ('Update Contact Information','Update Grassroots Info','Update Event Participant Info','Update Voter Info','Update Constituent Info', 'Update Core Info');

--
-- Dumping data for table `civicrm_uf_group`
--

INSERT INTO `civicrm_uf_group` (`is_active`, `group_type`, `title`, `help_pre`, `help_post`, `limit_listings_group_id`, `post_URL`, `add_to_group_id`, `add_captcha`, `is_map`, `is_edit_link`, `is_uf_link`, `is_update_dupe`, `cancel_URL`, `is_cms_user`, `notify`, `is_reserved`, `name`, `created_id`, `created_date`) VALUES
( 1, 'Individual,Contact', 'Update Contact Information', '', NULL, NULL, NULL, NULL, 0, 0, 0, 0, 1, NULL, 0, NULL, NULL, 'update_contact_information', NULL, NULL),
( 1, 'Individual', 'Update Grassroots Info', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 'update_grassroots_info', NULL, NULL),
( 1, 'Participant', 'Update Event Participant Info', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 'update_event_participant_info', NULL, NULL),
( 1, 'Individual', 'Update Voter Info', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 'update_voter_info', NULL, NULL),
( 1, 'Individual', 'Update Constituent Info', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, NULL, 0, NULL, NULL, 'update_core_info', NULL, NULL);

--
-- Dumping data for table `civicrm_uf_join`
--
SELECT @option_group_id_update_contact_information := min(id) from civicrm_uf_group where name = 'update_contact_information';
SELECT @option_group_id_update_grassroots_info := min(id) from civicrm_uf_group where name = 'update_grassroots_info';
SELECT @option_group_id_update_event_participant_info := min(id) from civicrm_uf_group where name = 'update_event_participant_info';
SELECT @option_group_id_update_voter_info := min(id) from civicrm_uf_group where name = 'update_voter_info';
SELECT @option_group_id_update_core_info := min(id) from civicrm_uf_group where name = 'update_core_info';

INSERT INTO `civicrm_uf_join` (`is_active`, `module`, `entity_table`, `entity_id`, `weight`, `uf_group_id`) VALUES
( 1, 'Profile', NULL, NULL, 1, @option_group_id_update_contact_information),
( 1, 'Profile', NULL, NULL, 1, @option_group_id_update_grassroots_info),
( 1, 'Profile', NULL, NULL, 1, @option_group_id_update_event_participant_info),
( 1, 'Profile', NULL, NULL, 1, @option_group_id_update_voter_info),
( 1, 'Profile', NULL, NULL, 1, @option_group_id_update_core_info);

SELECT @option_custom_grassroots_info :=min(id) from civicrm_custom_group where table_name = 'civicrm_value_grassroots_info'; 
SELECT @option_custom_participant_info :=min(id) from civicrm_custom_group where table_name = 'civicrm_value_participant_info'; 
SELECT @option_custom_voter_info :=min(id) from civicrm_custom_group where table_name = 'civicrm_value_voter_info'; 
SELECT @option_custom_demographics :=min(id) from civicrm_custom_group where table_name = 'civicrm_value_demographics'; 
SELECT @option_custom_core_info :=min(id) from civicrm_custom_group where table_name = 'civicrm_value_core_info'; 

--
-- Dumping data for table `civicrm_uf_field`
--

INSERT INTO `civicrm_uf_field` (`uf_group_id`, `field_name`, `is_active`, `is_view`, `is_required`, `weight`, `help_post`, `visibility`, `in_selector`, `is_searchable`, `location_type_id`, `phone_type_id`, `label`, `field_type`, `is_reserved`) VALUES
( @option_group_id_update_contact_information, 'first_name', 1, 0, 1, 1, '', 'Public Pages', 0, 1, NULL, NULL, 'First Name', 'Individual', NULL),
( @option_group_id_update_contact_information, 'last_name', 1, 0, 1, 2, '', 'Public Pages', 0, 1, NULL, NULL, 'Last Name', 'Individual', NULL),
( @option_group_id_update_contact_information, 'email', 1, 0, 1, 3, '', 'Public Pages', 0, 0, NULL, NULL, 'Email', 'Contact', NULL),
( @option_group_id_update_contact_information, 'phone', 1, 0, 0, 4, '', 'Public Pages', 0, 0, NULL, NULL, 'Phone', 'Contact', NULL),
( @option_group_id_update_contact_information, 'street_address', 1, 0, 0, 5, '', 'Public Pages', 0, 0, NULL, NULL, 'Street Address', 'Contact', NULL),
( @option_group_id_update_contact_information, 'city', 1, 0, 0, 6, '', 'Public Pages', 0, 0, NULL, NULL, 'City', 'Contact', NULL),
( @option_group_id_update_contact_information, 'state_province', 1, 0, 0, 7, '', 'Public Pages', 1, 1, NULL, NULL, 'State', 'Contact', NULL),
( @option_group_id_update_contact_information, 'postal_code', 1, 0, 0, 8, '', 'Public Pages', 0, 0, NULL, NULL, 'Zip Code', 'Contact', NULL),
( @option_group_id_update_contact_information, 'preferred_communication_method', 1, 0, 0, 9, '', 'Public Pages', 0, 0, NULL, NULL, 'Preferred Communication Method', 'Contact', NULL),
( @option_group_id_update_grassroots_info,CONCAT('custom_',(SELECT min(id) from civicrm_custom_field where column_name = 'issues_interest' AND custom_group_id = @option_custom_grassroots_info) ), 1, 0, 0, 2, '', 'User and User Admin Only', 0, 0, NULL, NULL, 'Issues Interest', 'Individual', NULL),
( @option_group_id_update_grassroots_info,CONCAT('custom_',(SELECT min(id) from civicrm_custom_field where column_name = 'leadership_level' AND custom_group_id = @option_custom_grassroots_info)), 1, 0, 0, 3, '', 'User and User Admin Only', 0, 0, NULL, NULL, 'Leadership Level', 'Individual', NULL),
( @option_group_id_update_grassroots_info, CONCAT('custom_',(SELECT min(id) from civicrm_custom_field where column_name = 'volunteer_interests' AND custom_group_id = @option_custom_grassroots_info)), 1, 0, 0, 4, '', 'User and User Admin Only', 0, 0, NULL, NULL, 'Volunteer Interests', 'Individual', NULL),
( @option_group_id_update_event_participant_info, 'participant_status_id', 1, 0, 0, 1, '', 'User and User Admin Only', 0, 0, NULL, NULL, 'Participant Status', 'Participant', NULL),
( @option_group_id_update_event_participant_info, CONCAT('custom_',(SELECT min(id) from civicrm_custom_field where column_name = 'childcare_needed' AND custom_group_id = @option_custom_participant_info)), 1, 0, 0, 2, 'Enter number of children if participant needs childcare - otherwise leave it as 0 if no childcare is needed', 'User and User Admin Only', 0, 0, NULL, NULL, 'Childcare Needed', 'Participant', NULL),
( @option_group_id_update_event_participant_info, CONCAT('custom_',(SELECT min(id) from civicrm_custom_field where column_name = 'ride_to' AND custom_group_id = @option_custom_participant_info)), 1, 0, 0, 3, 'Enter number of rides participant needs TO event (include children)', 'User and User Admin Only', 0, 0, NULL, NULL, 'Ride TO', 'Participant', NULL),
( @option_group_id_update_event_participant_info,  CONCAT('custom_',(SELECT min(id) from civicrm_custom_field where column_name = 'ride_back' AND custom_group_id = @option_custom_participant_info)), 1, 0, 0, 4, 'Enter number of rides participant needs BACK from event (include children)', 'User and User Admin Only', 0, 0, NULL, NULL, 'Ride BACK', 'Participant', NULL),
( @option_group_id_update_voter_info, CONCAT('custom_',(SELECT min(id) from civicrm_custom_field where column_name = 'voted_in_2008_general_election' AND custom_group_id = @option_custom_voter_info)) , 1, 0, 0, 4, '', 'Public Pages and Listings', 1, 1, NULL, NULL, 'Voted in 2008 General Election?', 'Individual', NULL),
( @option_group_id_update_voter_info, CONCAT('custom_',(SELECT min(id) from civicrm_custom_field where column_name = 'voted_in_2008_primary_election' AND custom_group_id = @option_custom_voter_info)), 1, 0, 0, 5, '', 'Public Pages and Listings', 1, 1, NULL, NULL, 'Voted in 2008 Primary Election?', 'Individual', NULL),
( @option_group_id_update_voter_info, CONCAT('custom_',(SELECT min(id) from civicrm_custom_field where column_name = 'county_name' AND custom_group_id = @option_custom_voter_info)), 1, 0, 0, 1, '', 'User and User Admin Only', 0, 0, NULL, NULL, 'County Name', 'Individual', NULL),
( @option_group_id_update_core_info, CONCAT('custom_',(SELECT min(id) from civicrm_custom_field where column_name = 'constituent_type' AND custom_group_id = @option_custom_core_info)), 1, 0, 0, 1, '', 'User and User Admin Only', 0, 0, NULL, NULL, 'Constituent Type', 'Individual', NULL),
( @option_group_id_update_core_info, CONCAT('custom_',(SELECT min(id) from civicrm_custom_field where column_name = 'primary_language' AND custom_group_id = @option_custom_demographics)), 1, 0, 0, 2, '', 'User and User Admin Only', 0, 0, NULL, NULL, 'Primary Language', 'Individual', NULL),
( @option_group_id_update_core_info, CONCAT('custom_',(SELECT min(id)from civicrm_custom_field where column_name = 'kids' AND custom_group_id = @option_custom_demographics)), 1, 0, 0, 3, '', 'User and User Admin Only', 0, 0, NULL, NULL, 'Kids?', 'Individual', NULL),
( @option_group_id_update_core_info,CONCAT('custom_',(SELECT min(id) from civicrm_custom_field where column_name = 'date_started' AND custom_group_id = @option_custom_core_info)), 1, 0, 0, 4, '', 'User and User Admin Only', 0, 0, NULL, NULL, 'Date Started', 'Individual', NULL),
( @option_group_id_update_core_info, CONCAT('custom_',(SELECT min(id) from civicrm_custom_field where column_name = 'staff_responsible' AND custom_group_id = @option_custom_core_info)), 1, 0, 0, 5, '', 'User and User Admin Only', 0, 0, NULL, NULL, 'Staff Responsible', 'Individual', NULL),
( @option_group_id_update_voter_info, CONCAT('custom_',(SELECT min(id) from civicrm_custom_field where column_name = 'voter_history' AND custom_group_id = @option_custom_voter_info)), 1, 0, 0, 3, 'A - Always \nO - Occasional\nN - New', 'User and User Admin Only', 0, 0, NULL, NULL, 'Voter History', 'Individual', NULL),
( @option_group_id_update_voter_info, CONCAT('custom_',(SELECT min(id) from civicrm_custom_field where column_name = 'party_registration' AND custom_group_id = @option_custom_voter_info)), 1, 0, 0, 2, '', 'User and User Admin Only', 0, 0, NULL, NULL, 'Party Registration', 'Individual', NULL),
( @option_group_id_update_grassroots_info, CONCAT('custom_',(SELECT min(id) from civicrm_custom_field where column_name = 'member_status' AND custom_group_id = @option_custom_grassroots_info)), 1, 0, 0, 1, '', 'User and User Admin Only', 0, 0, NULL, NULL, 'Member Status', 'Individual', NULL);

