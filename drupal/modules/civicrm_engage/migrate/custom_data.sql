-- data migration from old custom tables to new 

insert into civicrm_value_source_details (select * from civicrm_value_activity_source_details_18);

insert into civicrm_value_communication_details ( select * from civicrm_value_communication_details_19 );

insert into civicrm_value_contact_info (select * from civicrm_value_contact_info_6);

insert into civicrm_value_contribution_source (select * from civicrm_value_contribution_source_11);

insert into civicrm_value_door_knock_responses (select * from civicrm_value_door_knock_responses_12);

insert into civicrm_value_event_campaign_details (select * from civicrm_value_event_campaign_details_20);

insert into civicrm_value_grant_info (select * from civicrm_value_grant_info_7);

insert into civicrm_value_leadership_level (entity_id, leadership_level) (select entity_id, leadership_level_23 from civicrm_value_leadership_level_5);

insert into civicrm_value_participant_info (entity_id, childcare_needed, ride_to, ride_back, reminder_date, invitation_date, contact_attended, invitation_response, reminder_response, participant_campaign_code, second_call_date, second_call_response) (select entity_id, childcare_needed_42, ride_to_43, ride_back_44, reminderdate_86, invitation_date_90, contact_attended__91, invitation_response_92, reminder_response_93, participant_campaign_code_128, second_call_date_129, second_call_response_130 from civicrm_value_participant_info_10);

insert into civicrm_value_phone_bank_responses (select * from civicrm_value_phone_bank_responses_14);

insert into civicrm_value_primary_contact (select * from civicrm_value_primary_contact_1);

insert into civicrm_value_proposal_info (select * from civicrm_value_proposal_info_8);

insert into civicrm_value_voter_info (entity_id, precinct, state_district, city_district, federal_district, party_registration, if_other_party, state_voter_id, voted_in_2008_general_election, voted_in_2008_primary_election, voter_history, county_name ) 
(select entity_id, precinct_5, state_district_6, city_district_7, federal_district_8, party_registration_16, if_other_party__17, state_voter_id_18, voted_in_2008_general_election__24, voted_in_2008_primary_election__25, voter_history_75, county_89 from civicrm_value_voter_info_2);

insert into civicrm_value_event_details (entity_id, event_contact_person, event_source_code) 
(select entity_id, event_contact_person_95, event_source_code_105 from civicrm_value_event_details_16);

alter table civicrm_value_grassroots_info
add column `ehc_board_and_staff` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
add column `ehc_campaign` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL;

insert into civicrm_value_grassroots_info (entity_id, leadership_level, issues_interest, volunteer_interests, member_status, ehc_board_and_staff, ehc_campaign)
(select entity_id, leadership_level_12 ,issues_interest_34, volunteer_interests_35, member_status_76, ehc_board_and_staff_113, ehc_campaign_133 from civicrm_value_grassroots_info_4);

insert into civicrm_value_demographics (entity_id, ethnicity, primary_language, secondary_language, kids) 
(select entity_id, ethnicity_19, primary_language_20, secondary_language_106, kids__21 from civicrm_value_demographics_3);

insert into civicrm_value_core_info (entity_id, constituent_type, staff_responsible, date_started, other_name, how_started)
(select entity_id, contact_type_13, staff_responsible_22, date_started_57, other_name_72, how_started_73 from civicrm_value_demographics_3);

alter table civicrm_value_grassroots_info
add column `other_affiliation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
add column `toxinformer_flag` tinyint(4) DEFAULT NULL,
add column `community` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
add column `owner_or_renter` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL;

insert into civicrm_value_grassroots_info (entity_id, other_affiliation, toxinformer_flag, community, owner_or_renter)
(select entity_id, other_affiliation_108, toxinformer_flag_109, community_110, owner_or_renter_111 from civicrm_value_demographics_3)
ON DUPLICATE KEY UPDATE other_affiliation=other_affiliation_108, toxinformer_flag=toxinformer_flag_109, community=community_110, owner_or_renter=owner_or_renter_111;

-- migrate gender and dob to contact tables
update civicrm_contact cc
inner join civicrm_value_demographics_3 demo on cc.id = demo.entity_id
set cc.gender_id = IF(demo.gender_14 = 'Male', 2, IF(demo.gender_14 = 'Female', 1, NULL)),
cc.birth_date = demo.dob_15,
cc.postal_greeting_custom = demo.salutation_36,
cc.postal_greeting_display = demo.salutation_36,
cc.addressee_custom = demo.addressee_37,
cc.addressee_display = demo.addressee_37,
cc.email_greeting_custom = demo.email_salutation_38,
cc.email_greeting_display = demo.email_salutation_38;

-- drop all successfully migrated custom tables
DROP TABLE IF EXISTS `civicrm_value_activity_source_details_18`, `civicrm_value_communication_details_19`, `civicrm_value_contact_info_6`, `civicrm_value_contribution_source_11`, `civicrm_value_door_knock_responses_12`, `civicrm_value_event_campaign_details_20`, `civicrm_value_grant_info_7`, `civicrm_value_participant_info_10`, `civicrm_value_phone_bank_responses_14`, `civicrm_value_primary_contact_1`, `civicrm_value_proposal_info_8`, `civicrm_value_voter_info_2`, `civicrm_value_leadership_level_5`, `civicrm_value_demographics_3`, `civicrm_value_event_details_16`, `civicrm_value_grassroots_info_4`;
