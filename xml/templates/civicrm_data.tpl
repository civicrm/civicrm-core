-- +--------------------------------------------------------------------+
-- | CiviCRM version 5                                                  |
-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC (c) 2004-2018                                |
-- +--------------------------------------------------------------------+
-- | This file is a part of CiviCRM.                                    |
-- |                                                                    |
-- | CiviCRM is free software; you can copy, modify, and distribute it  |
-- | under the terms of the GNU Affero General Public License           |
-- | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
-- |                                                                    |
-- | CiviCRM is distributed in the hope that it will be useful, but     |
-- | WITHOUT ANY WARRANTY; without even the implied warranty of         |
-- | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
-- | See the GNU Affero General Public License for more details.        |
-- |                                                                    |
-- | You should have received a copy of the GNU Affero General Public   |
-- | License and the CiviCRM Licensing Exception along                  |
-- | with this program; if not, contact CiviCRM LLC                     |
-- | at info[AT]civicrm[DOT]org. If you have questions about the        |
-- | GNU Affero General Public License or the licensing of CiviCRM,     |
-- | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
-- +--------------------------------------------------------------------+
--
-- Generated from {$smarty.template}
-- {$generated}
--
-- This file provides template to civicrm_data.mysql. Inserts all base data needed for a new CiviCRM DB

SET @domainName := 'Default Domain Name';
SET @defaultOrganization := 'Default Organization';

-- Add components to system wide registry
-- We're doing it early to avoid constraint errors.
INSERT INTO civicrm_component (name, namespace) VALUES ('CiviEvent'     , 'CRM_Event' );
INSERT INTO civicrm_component (name, namespace) VALUES ('CiviContribute', 'CRM_Contribute' );
INSERT INTO civicrm_component (name, namespace) VALUES ('CiviMember'    , 'CRM_Member' );
INSERT INTO civicrm_component (name, namespace) VALUES ('CiviMail'      , 'CRM_Mailing' );
INSERT INTO civicrm_component (name, namespace) VALUES ('CiviGrant'     , 'CRM_Grant' );
INSERT INTO civicrm_component (name, namespace) VALUES ('CiviPledge'    , 'CRM_Pledge' );
INSERT INTO civicrm_component (name, namespace) VALUES ('CiviCase'      , 'CRM_Case' );
INSERT INTO civicrm_component (name, namespace) VALUES ('CiviReport'    , 'CRM_Report' );
INSERT INTO civicrm_component (name, namespace) VALUES ('CiviCampaign'  , 'CRM_Campaign' );

-- Create organization contact
INSERT INTO civicrm_contact( `contact_type`, `sort_name`, `display_name`, `legal_name`, `organization_name`)
VALUES ('Organization', @defaultOrganization, @defaultOrganization, @defaultOrganization, @defaultOrganization);
SET @contactID := LAST_INSERT_ID();

INSERT INTO civicrm_email (contact_id, location_type_id, email, is_primary, is_billing, on_hold, hold_date, reset_date)
VALUES
(@contactID, 1, 'fixme.domainemail@example.org', 0, 0, 0, NULL, NULL);

INSERT INTO civicrm_domain (name, version, contact_id) VALUES (@domainName, '2.2', @contactID);
SELECT @domainID := id FROM civicrm_domain where name = 'Default Domain Name';

-- Sample location types
-- CRM-9120 for legacy reasons we are continuing to translate the 'name', but this
-- field is used mainly as an ID, and display_name will be shown to the user, but
-- we have not yet finished modifying all places where the 'name' is shown.
INSERT INTO civicrm_location_type( name, display_name, vcard_name, description, is_reserved, is_active, is_default ) VALUES( '{ts escape="sql"}Home{/ts}', '{ts escape="sql"}Home{/ts}', 'HOME', '{ts escape="sql"}Place of residence{/ts}', 0, 1, 1 );
INSERT INTO civicrm_location_type( name, display_name, vcard_name, description, is_reserved, is_active ) VALUES( '{ts escape="sql"}Work{/ts}', '{ts escape="sql"}Work{/ts}', 'WORK', '{ts escape="sql"}Work location{/ts}', 0, 1 );
INSERT INTO civicrm_location_type( name, display_name, vcard_name, description, is_reserved, is_active ) VALUES( '{ts escape="sql"}Main{/ts}', '{ts escape="sql"}Main{/ts}', NULL, '{ts escape="sql"}Main office location{/ts}', 0, 1 );
INSERT INTO civicrm_location_type( name, display_name, vcard_name, description, is_reserved, is_active ) VALUES( '{ts escape="sql"}Other{/ts}', '{ts escape="sql"}Other{/ts}', NULL, '{ts escape="sql"}Other location{/ts}', 0, 1 );
-- the following location must stay with the untranslated Billing name, CRM-2064
INSERT INTO civicrm_location_type( name, display_name, vcard_name, description, is_reserved, is_active ) VALUES( 'Billing',  '{ts escape="sql"}Billing{/ts}', NULL, '{ts escape="sql"}Billing Address location{/ts}', 1, 1 );

-- Sample relationship types
INSERT INTO civicrm_relationship_type( name_a_b,label_a_b, name_b_a,label_b_a, description, contact_type_a, contact_type_b, is_reserved )
    VALUES( 'Child of', '{ts escape="sql"}Child of{/ts}', 'Parent of', '{ts escape="sql"}Parent of{/ts}', '{ts escape="sql"}Parent/child relationship.{/ts}', 'Individual', 'Individual', 0 ),
          ( 'Spouse of', '{ts escape="sql"}Spouse of{/ts}', 'Spouse of', '{ts escape="sql"}Spouse of{/ts}', '{ts escape="sql"}Spousal relationship.{/ts}', 'Individual', 'Individual', 0 ),
          ( 'Partner of', '{ts escape="sql"}Partner of{/ts}', 'Partner of', '{ts escape="sql"}Partner of{/ts}', '{ts escape="sql"}Partner relationship.{/ts}', 'Individual', 'Individual', 0 ),
          ( 'Sibling of', '{ts escape="sql"}Sibling of{/ts}', 'Sibling of', '{ts escape="sql"}Sibling of{/ts}', '{ts escape="sql"}Sibling relationship.{/ts}', 'Individual','Individual', 0 ),
          ( 'Employee of', '{ts escape="sql"}Employee of{/ts}', 'Employer of', '{ts escape="sql"}Employer of{/ts}', '{ts escape="sql"}Employment relationship.{/ts}','Individual','Organization', 1 ),
          ( 'Volunteer for', '{ts escape="sql"}Volunteer for{/ts}', 'Volunteer is', '{ts escape="sql"}Volunteer is{/ts}', '{ts escape="sql"}Volunteer relationship.{/ts}','Individual','Organization', 0 ),
          ( 'Head of Household for', '{ts escape="sql"}Head of Household for{/ts}', 'Head of Household is', '{ts escape="sql"}Head of Household is{/ts}', '{ts escape="sql"}Head of household.{/ts}','Individual','Household', 1 ),
          ( 'Household Member of', '{ts escape="sql"}Household Member of{/ts}', 'Household Member is', '{ts escape="sql"}Household Member is{/ts}', '{ts escape="sql"}Household membership.{/ts}','Individual','Household', 1 );

-- Relationship Types for CiviCase
INSERT INTO civicrm_relationship_type( name_a_b,label_a_b, name_b_a,label_b_a, description, contact_type_a, contact_type_b, is_reserved )
    VALUES( 'Case Coordinator is', 'Case Coordinator is', 'Case Coordinator', 'Case Coordinator', 'Case Coordinator', 'Individual', 'Individual', 0 );
INSERT INTO civicrm_relationship_type( name_a_b,label_a_b, name_b_a,label_b_a, description, contact_type_a, contact_type_b, is_reserved )
    VALUES( 'Supervised by', 'Supervised by', 'Supervisor', 'Supervisor', 'Immediate workplace supervisor', 'Individual', 'Individual', 0 );


-- Sample Tags
INSERT INTO civicrm_tag( name, description, parent_id,used_for )
    VALUES
    ( '{ts escape="sql"}Non-profit{/ts}', '{ts escape="sql"}Any not-for-profit organization.{/ts}', NULL,'civicrm_contact'),
    ( '{ts escape="sql"}Company{/ts}', '{ts escape="sql"}For-profit organization.{/ts}', NULL,'civicrm_contact'),
    ( '{ts escape="sql"}Government Entity{/ts}', '{ts escape="sql"}Any governmental entity.{/ts}', NULL,'civicrm_contact'),
    ( '{ts escape="sql"}Major Donor{/ts}', '{ts escape="sql"}High-value supporter of our organization.{/ts}', NULL,'civicrm_contact'),
    ( '{ts escape="sql"}Volunteer{/ts}', '{ts escape="sql"}Active volunteers.{/ts}', NULL,'civicrm_contact' );

{capture assign=subgroup}{ldelim}subscribe.group{rdelim}{/capture}
{capture assign=suburl}{ldelim}subscribe.url{rdelim}{/capture}
{capture assign=welgroup}{ldelim}welcome.group{rdelim}{/capture}
{capture assign=unsubgroup}{ldelim}unsubscribe.group{rdelim}{/capture}
{capture assign=actresub}{ldelim}action.resubscribe{rdelim}{/capture}
{capture assign=actresuburl}{ldelim}action.resubscribeUrl{rdelim}{/capture}
{capture assign=resubgroup}{ldelim}resubscribe.group{rdelim}{/capture}
{capture assign=actunsub}{ldelim}action.unsubscribe{rdelim}{/capture}
{capture assign=actunsuburl}{ldelim}action.unsubscribeUrl{rdelim}{/capture}
{capture assign=domname}{ldelim}domain.name{rdelim}{/capture}

-- sample CiviCRM mailing components
INSERT INTO civicrm_mailing_component
    (name,component_type,subject,body_html,body_text,is_default,is_active)
VALUES
    ('{ts escape="sql"}Mailing Header{/ts}','Header','{ts escape="sql"}Descriptive Title for this Header{/ts}','{ts escape="sql"}Sample Header for HTML formatted content.{/ts}','{ts escape="sql"}Sample Header for TEXT formatted content.{/ts}',1,1),
    ('{ts escape="sql"}Mailing Footer{/ts}','Footer','{ts escape="sql"}Descriptive Title for this Footer.{/ts}','{ts escape="sql"}Sample Footer for HTML formatted content<br/><a href="{ldelim}action.optOutUrl{rdelim}">Unsubscribe</a>  <br/> {ldelim}domain.address{rdelim}{/ts}','{ts escape="sql"}to unsubscribe: {ldelim}action.optOutUrl{rdelim}
{ldelim}domain.address{rdelim}{/ts}',1,1),
    ('{ts escape="sql"}Subscribe Message{/ts}','Subscribe','{ts escape="sql"}Subscription Confirmation Request{/ts}','{ts escape="sql" 1=$subgroup 2=$suburl}You have a pending subscription to the %1 mailing list. To confirm this subscription, reply to this email or click <a href="%2">here</a>.{/ts}','{ts escape="sql" 1=$subgroup 2=$suburl}You have a pending subscription to the %1 mailing list. To confirm this subscription, reply to this email or click on this link: %2{/ts}',1,1),
    ('{ts escape="sql"}Welcome Message{/ts}','Welcome','{ts escape="sql"}Your Subscription has been Activated{/ts}','{ts escape="sql" 1=$welgroup}Welcome. Your subscription to the %1 mailing list has been activated.{/ts}','{ts escape="sql" 1=$welgroup}Welcome. Your subscription to the %1 mailing list has been activated.{/ts}',1,1),
    ('{ts escape="sql"}Unsubscribe Message{/ts}','Unsubscribe','{ts escape="sql"}Un-subscribe Confirmation{/ts}','{ts escape="sql" 1=$unsubgroup 2=$actresub 3=$actresuburl}You have been un-subscribed from the following groups: %1. You can re-subscribe by mailing %2 or clicking <a href="%3">here</a>.{/ts}','{ts escape="sql" 1=$unsubgroup 2=$actresub}You have been un-subscribed from the following groups: %1. You can re-subscribe by mailing %2 or clicking %3{/ts}',1,1),
    ('{ts escape="sql"}Resubscribe Message{/ts}','Resubscribe','{ts escape="sql"}Re-subscribe Confirmation{/ts}','{ts escape="sql" 1=$resubgroup 2=$actunsub 3=$actunsuburl}You have been re-subscribed to the following groups: %1. You can un-subscribe by mailing %2 or clicking <a href="%3">here</a>.{/ts}','{ts escape="sql" 1=$resubgroup 2=$actunsub 3=$actunsuburl}You have been re-subscribed to the following groups: %1. You can un-subscribe by mailing %2 or clicking %3{/ts}',1,1),
    ('{ts escape="sql"}Opt-out Message{/ts}','OptOut','{ts escape="sql"}Opt-out Confirmation{/ts}','{ts escape="sql" 1=$domname}Your email address has been removed from %1 mailing lists.{/ts}','{ts escape="sql" 1=$domname}Your email address has been removed from %1 mailing lists.{/ts}',1,1),
    ('{ts escape="sql"}Auto-responder{/ts}','Reply','{ts escape="sql"}Please Send Inquiries to Our Contact Email Address{/ts}','{ts escape="sql"}This is an automated reply from an un-attended mailbox. Please send any inquiries to the contact email address listed on our web-site.{/ts}','{ts escape="sql"}This is an automated reply from an un-attended mailbox. Please send any inquiries to the contact email address listed on our web-site.{/ts}',1,1);


-- contribution types
INSERT INTO
   civicrm_financial_type(name, is_reserved, is_active, is_deductible)
VALUES
  ( '{ts escape="sql"}Donation{/ts}'             , 0, 1, 1 ),
  ( '{ts escape="sql"}Member Dues{/ts}'          , 0, 1, 1 ),
  ( '{ts escape="sql"}Campaign Contribution{/ts}', 0, 1, 0 ),
  ( '{ts escape="sql"}Event Fee{/ts}'            , 0, 1, 0 );

-- option groups and values for 'preferred communication methods' , 'activity types', 'gender', etc.

INSERT INTO
   `civicrm_option_group` (`name`, `title`, `data_type`, `is_reserved`, `is_active`, `is_locked`)
VALUES
   ('preferred_communication_method', '{ts escape="sql"}Preferred Communication Method{/ts}'     , NULL, 1, 1, 0),
   ('activity_type'                 , '{ts escape="sql"}Activity Type{/ts}'                      , 'Integer', 1, 1, 0),
   ('gender'                        , '{ts escape="sql"}Gender{/ts}'                             , 'Integer', 1, 1, 0),
   ('instant_messenger_service'     , '{ts escape="sql"}Instant Messenger (IM) screen-names{/ts}', NULL, 1, 1, 0),
   ('mobile_provider'               , '{ts escape="sql"}Mobile Phone Providers{/ts}'             , NULL, 1, 1, 0),
   ('individual_prefix'             , '{ts escape="sql"}Individual contact prefixes{/ts}'        , NULL, 1, 1, 0),
   ('individual_suffix'             , '{ts escape="sql"}Individual contact suffixes{/ts}'        , NULL, 1, 1, 0),
   ('acl_role'                      , '{ts escape="sql"}ACL Role{/ts}'                           , NULL, 1, 1, 0),
   ('accept_creditcard'             , '{ts escape="sql"}Accepted Credit Cards{/ts}'              , NULL, 1, 1, 0),
   ('payment_instrument'            , '{ts escape="sql"}Payment Methods{/ts}'                    , 'Integer', 1, 1, 0),
   ('contribution_status'           , '{ts escape="sql"}Contribution Status{/ts}'                , NULL, 1, 1, 1),
   ('pcp_status'                    , '{ts escape="sql"}PCP Status{/ts}'                         , NULL, 1, 1, 1),
   ('pcp_owner_notify'              , '{ts escape="sql"}PCP owner notifications{/ts}'            , NULL, 1, 1, 1),
   ('participant_role'              , '{ts escape="sql"}Participant Role{/ts}'                   , 'Integer', 1, 1, 0),
   ('event_type'                    , '{ts escape="sql"}Event Type{/ts}'                         , 'Integer', 1, 1, 0),
   ('contact_view_options'          , '{ts escape="sql"}Contact View Options{/ts}'               , NULL, 1, 1, 1),
   ('contact_smart_group_display'   , '{ts escape="sql"}Contact Smart Group View Options{/ts}'   , NULL, 1, 1, 1),
   ('contact_edit_options'          , '{ts escape="sql"}Contact Edit Options{/ts}'               , NULL, 1, 1, 1),
   ('advanced_search_options'       , '{ts escape="sql"}Advanced Search Options{/ts}'            , NULL, 1, 1, 1),
   ('user_dashboard_options'        , '{ts escape="sql"}User Dashboard Options{/ts}'             , NULL, 1, 1, 1),
   ('address_options'               , '{ts escape="sql"}Addressing Options{/ts}'                 , NULL, 1, 1, 0),
   ('group_type'                    , '{ts escape="sql"}Group Type{/ts}'                         , NULL, 1, 1, 0),
   ('grant_status'                  , '{ts escape="sql"}Grant status{/ts}'                       , NULL, 1, 1, 0),
   ('grant_type'                    , '{ts escape="sql"}Grant Type{/ts}'                         , NULL, 1, 1, 0),
   ('custom_search'                 , '{ts escape="sql"}Custom Search{/ts}'                      , NULL, 1, 1, 0),
   ('activity_status'               , '{ts escape="sql"}Activity Status{/ts}'                    , 'Integer', 1, 1, 0),
   ('case_type'                     , '{ts escape="sql"}Case Type{/ts}'                          , NULL, 1, 1, 0),
   ('case_status'                   , '{ts escape="sql"}Case Status{/ts}'                        , NULL, 1, 1, 0),
   ('participant_listing'           , '{ts escape="sql"}Participant Listing{/ts}'                , NULL, 1, 1, 0),
   ('safe_file_extension'           , '{ts escape="sql"}Safe File Extension{/ts}'                , NULL, 1, 1, 0),
   ('from_email_address'            , '{ts escape="sql"}From Email Address{/ts}'                 , NULL, 1, 1, 0),
   ('mapping_type'                  , '{ts escape="sql"}Mapping Type{/ts}'                       , NULL, 1, 1, 1),
   ('wysiwyg_editor'                , '{ts escape="sql"}WYSIWYG Editor{/ts}'                     , NULL, 1, 1, 0),
   ('recur_frequency_units'         , '{ts escape="sql"}Recurring Frequency Units{/ts}'          , NULL, 1, 1, 0),
   ('phone_type'                    , '{ts escape="sql"}Phone Type{/ts}'                         , NULL, 1, 1, 0),
   ('custom_data_type'              , '{ts escape="sql"}Custom Data Type{/ts}'                   , NULL, 1, 1, 0),
   ('visibility'                    , '{ts escape="sql"}Visibility{/ts}'                         , NULL, 1, 1, 0),
   ('mail_protocol'                 , '{ts escape="sql"}Mail Protocol{/ts}'                      , NULL, 1, 1, 0),
   ('priority'                      , '{ts escape="sql"}Priority{/ts}'                           , NULL, 1, 1, 0),
   ('redaction_rule'                , '{ts escape="sql"}Redaction Rule{/ts}'                     , NULL, 1, 1, 0),
   ('report_template'               , '{ts escape="sql"}Report Template{/ts}'                    , NULL, 1, 1, 0),
   ('email_greeting'                , '{ts escape="sql"}Email Greeting Type{/ts}'                , NULL, 1, 1, 0),
   ('postal_greeting'               , '{ts escape="sql"}Postal Greeting Type{/ts}'               , NULL, 1, 1, 0),
   ('addressee'                     , '{ts escape="sql"}Addressee Type{/ts}'                     , NULL, 1, 1, 0),
   ('contact_autocomplete_options'  , '{ts escape="sql"}Autocomplete Contact Search{/ts}'        , NULL, 1, 1, 1),
   ('contact_reference_options'     , '{ts escape="sql"}Contact Reference Autocomplete Options{/ts}', NULL, 1, 1, 1),
   ('website_type'                  , '{ts escape="sql"}Website Type{/ts}'                       , NULL, 1, 1, 0),
   ('tag_used_for'                  , '{ts escape="sql"}Tag Used For{/ts}'                       , NULL, 1, 1, 1),
   ('currencies_enabled'            , '{ts escape="sql"}Currencies Enabled{/ts}'                 , NULL, 1, 1, 0),
   ('event_badge'                   , '{ts escape="sql"}Event Name Badge{/ts}'                   , NULL, 1, 1, 0),
   ('note_privacy'                  , '{ts escape="sql"}Privacy levels for notes{/ts}'           , NULL, 1, 1, 0),
   ('campaign_type'                 , '{ts escape="sql"}Campaign Type{/ts}'                      , NULL, 1, 1, 0),
   ('campaign_status'               , '{ts escape="sql"}Campaign Status{/ts}'                    , NULL, 1, 1, 0),
   ('system_extensions'             , '{ts escape="sql"}CiviCRM Extensions{/ts}'                 , NULL, 1, 1, 0),
   ('mail_approval_status'          , '{ts escape="sql"}CiviMail Approval Status{/ts}'           , NULL, 1, 1, 0),
   ('engagement_index'              , '{ts escape="sql"}Engagement Index{/ts}'                   , NULL, 1, 1, 0),
   ('cg_extend_objects'             , '{ts escape="sql"}Objects a custom group extends to{/ts}'  , NULL, 1, 1, 0),
   ('paper_size'                    , '{ts escape="sql"}Paper Size{/ts}'                         , NULL, 1, 1, 0),
   ('pdf_format'                    , '{ts escape="sql"}PDF Page Format{/ts}'                    , NULL, 1, 1, 0),
   ('label_format'                  , '{ts escape="sql"}Mailing Label Format{/ts}'               , NULL, 1, 1, 0),
   ('activity_contacts'             , '{ts escape="sql"}Activity Contacts{/ts}'                  , NULL, 1, 1, 1),
   ('account_relationship'          , '{ts escape="sql"}Account Relationship{/ts}'               , NULL, 1, 1, 0),
   ('event_contacts'                , '{ts escape="sql"}Event Recipients{/ts}'                   , NULL, 1, 1, 0),
   ('conference_slot'               , '{ts escape="sql"}Conference Slot{/ts}'                    , NULL, 1, 1, 0),
   ('batch_type'                    , '{ts escape="sql"}Batch Type{/ts}'                         , NULL, 1, 1, 1),
   ('batch_mode'                    , '{ts escape="sql"}Batch Mode{/ts}'                         , NULL, 1, 1, 1),
   ('batch_status'                  , '{ts escape="sql"}Batch Status{/ts}'                       , NULL, 1, 1, 1),
   ('sms_api_type'                  , '{ts escape="sql"}Api Type{/ts}'                           , NULL, 1, 1, 0),
   ('sms_provider_name'             , '{ts escape="sql"}Sms Provider Internal Name{/ts}'         , NULL, 1, 1, 0),
   ('auto_renew_options'            , '{ts escape="sql"}Auto Renew Options{/ts}'                 , NULL, 1, 1, 1),
   ('financial_account_type'        , '{ts escape="sql"}Financial Account Type{/ts}'             , NULL, 1, 1, 0),
   ('financial_item_status'         , '{ts escape="sql"}Financial Item Status{/ts}'              , NULL, 1, 1, 1),
   ('label_type'                    , '{ts escape="sql"}Label Type{/ts}'                         , NULL, 1, 1, 0),
   ('name_badge'                    , '{ts escape="sql"}Name Badge Format{/ts}'                  , NULL, 1, 1, 0),
   ('communication_style'           , '{ts escape="sql"}Communication Style{/ts}'                , NULL, 1, 1, 0),
   ('msg_mode'                      , '{ts escape="sql"}Message Mode{/ts}'                       , NULL, 1, 1, 0),
   ('contact_date_reminder_options' , '{ts escape="sql"}Contact Date Reminder Options{/ts}'      , NULL, 1, 1, 1),
   ('wysiwyg_presets'               , '{ts escape="sql"}WYSIWYG Editor Presets{/ts}'             , NULL, 1, 1, 0),
   ('relative_date_filters'         , '{ts escape="sql"}Relative Date Filters{/ts}'              , NULL, 1, 1, 0),
   ('pledge_status'                 , '{ts escape="sql"}Pledge Status{/ts}'                      , NULL, 1, 1, 1),
   ('environment'                   , '{ts escape="sql"}Environment{/ts}'                        , NULL, 1, 1, 0),
   ('activity_default_assignee'     , '{ts escape="sql"}Activity default assignee{/ts}'          , NULL, 1, 1, 0);

SELECT @option_group_id_pcm            := max(id) from civicrm_option_group where name = 'preferred_communication_method';
SELECT @option_group_id_act            := max(id) from civicrm_option_group where name = 'activity_type';
SELECT @option_group_id_gender         := max(id) from civicrm_option_group where name = 'gender';
SELECT @option_group_id_IMProvider     := max(id) from civicrm_option_group where name = 'instant_messenger_service';
SELECT @option_group_id_mobileProvider := max(id) from civicrm_option_group where name = 'mobile_provider';
SELECT @option_group_id_prefix         := max(id) from civicrm_option_group where name = 'individual_prefix';
SELECT @option_group_id_suffix         := max(id) from civicrm_option_group where name = 'individual_suffix';
SELECT @option_group_id_aclRole        := max(id) from civicrm_option_group where name = 'acl_role';
SELECT @option_group_id_acc            := max(id) from civicrm_option_group where name = 'accept_creditcard';
SELECT @option_group_id_pi             := max(id) from civicrm_option_group where name = 'payment_instrument';
SELECT @option_group_id_cs             := max(id) from civicrm_option_group where name = 'contribution_status';
SELECT @option_group_id_pcp            := max(id) from civicrm_option_group where name = 'pcp_status';
SELECT @option_group_id_pcpOwnerNotify := max(id) from civicrm_option_group where name = 'pcp_owner_notify';
SELECT @option_group_id_pRole          := max(id) from civicrm_option_group where name = 'participant_role';
SELECT @option_group_id_etype          := max(id) from civicrm_option_group where name = 'event_type';
SELECT @option_group_id_cvOpt          := max(id) from civicrm_option_group where name = 'contact_view_options';
SELECT @option_group_id_csgOpt         := max(id) from civicrm_option_group where name = 'contact_smart_group_display';
SELECT @option_group_id_ceOpt          := max(id) from civicrm_option_group where name = 'contact_edit_options';
SELECT @option_group_id_asOpt          := max(id) from civicrm_option_group where name = 'advanced_search_options';
SELECT @option_group_id_udOpt          := max(id) from civicrm_option_group where name = 'user_dashboard_options';
SELECT @option_group_id_adOpt          := max(id) from civicrm_option_group where name = 'address_options';
SELECT @option_group_id_gType          := max(id) from civicrm_option_group where name = 'group_type';
SELECT @option_group_id_grantSt        := max(id) from civicrm_option_group where name = 'grant_status';
SELECT @option_group_id_grantTyp       := max(id) from civicrm_option_group where name = 'grant_type';
SELECT @option_group_id_csearch        := max(id) from civicrm_option_group where name = 'custom_search';
SELECT @option_group_id_acs            := max(id) from civicrm_option_group where name = 'activity_status';
SELECT @option_group_id_ct             := max(id) from civicrm_option_group where name = 'case_type';
SELECT @option_group_id_cas            := max(id) from civicrm_option_group where name = 'case_status';
SELECT @option_group_id_pl             := max(id) from civicrm_option_group where name = 'participant_listing';
SELECT @option_group_id_sfe            := max(id) from civicrm_option_group where name = 'safe_file_extension';
SELECT @option_group_id_mt             := max(id) from civicrm_option_group where name = 'mapping_type';
SELECT @option_group_id_we             := max(id) from civicrm_option_group where name = 'wysiwyg_editor';
SELECT @option_group_id_fu             := max(id) from civicrm_option_group where name = 'recur_frequency_units';
SELECT @option_group_id_pht            := max(id) from civicrm_option_group where name = 'phone_type';
SELECT @option_group_id_fma            := max(id) from civicrm_option_group where name = 'from_email_address';
SELECT @option_group_id_cdt            := max(id) from civicrm_option_group where name = 'custom_data_type';
SELECT @option_group_id_vis            := max(id) from civicrm_option_group where name = 'visibility';
SELECT @option_group_id_mp             := max(id) from civicrm_option_group where name = 'mail_protocol';
SELECT @option_group_id_priority       := max(id) from civicrm_option_group where name = 'priority';
SELECT @option_group_id_rr             := max(id) from civicrm_option_group where name = 'redaction_rule';
SELECT @option_group_id_emailGreeting  := max(id) from civicrm_option_group where name = 'email_greeting';
SELECT @option_group_id_postalGreeting := max(id) from civicrm_option_group where name = 'postal_greeting';
SELECT @option_group_id_addressee      := max(id) from civicrm_option_group where name = 'addressee';
SELECT @option_group_id_report         := max(id) from civicrm_option_group where name = 'report_template';
SELECT @option_group_id_acsOpt         := max(id) from civicrm_option_group where name = 'contact_autocomplete_options';
SELECT @option_group_id_acConRef       := max(id) from civicrm_option_group where name = 'contact_reference_options';
SELECT @option_group_id_website        := max(id) from civicrm_option_group where name = 'website_type';
SELECT @option_group_id_tuf            := max(id) from civicrm_option_group where name = 'tag_used_for';
SELECT @option_group_id_currency       := max(id) from civicrm_option_group where name = 'currencies_enabled';
SELECT @option_group_id_eventBadge     := max(id) from civicrm_option_group where name = 'event_badge';
SELECT @option_group_id_notePrivacy    := max(id) from civicrm_option_group where name = 'note_privacy';
SELECT @option_group_id_campaignType   := max(id) from civicrm_option_group where name = 'campaign_type';
SELECT @option_group_id_campaignStatus := max(id) from civicrm_option_group where name = 'campaign_status';
SELECT @option_group_id_extensions     := max(id) from civicrm_option_group where name = 'system_extensions';
SELECT @option_group_id_mail_approval_status := max(id) from civicrm_option_group where name = 'mail_approval_status';
SELECT @option_group_id_engagement_index := max(id) from civicrm_option_group where name = 'engagement_index';
SELECT @option_group_id_cgeo           := max(id) from civicrm_option_group where name = 'cg_extend_objects';
SELECT @option_group_id_paperSize      := max(id) from civicrm_option_group where name = 'paper_size';
SELECT @option_group_id_label          := max(id) from civicrm_option_group where name = 'label_format';
SELECT @option_group_id_aco            := max(id) from civicrm_option_group where name = 'activity_contacts';
SELECT @option_group_id_arel           := max(id) from civicrm_option_group where name = 'account_relationship';
SELECT @option_group_id_ere            := max(id) from civicrm_option_group where name = 'event_contacts';
SELECT @option_group_id_conference_slot := max(id) from civicrm_option_group where name = 'conference_slot';
SELECT @option_group_id_batch_type     := max(id) from civicrm_option_group where name = 'batch_type';
SELECT @option_group_id_batch_status   := max(id) from civicrm_option_group where name = 'batch_status';
SELECT @option_group_id_batch_mode     := max(id) from civicrm_option_group where name = 'batch_mode';
SELECT @option_group_id_sms_api_type   := max(id) from civicrm_option_group where name = 'sms_api_type';
SELECT @option_group_id_sms_provider_name := max(id) from civicrm_option_group where name = 'sms_provider_name';
SELECT @option_group_id_aro := max(id) from civicrm_option_group where name = 'auto_renew_options';
SELECT @option_group_id_fat            := max(id) from civicrm_option_group where name = 'financial_account_type';
SELECT @option_group_id_financial_item_status := max(id) from civicrm_option_group where name = 'financial_item_status';
SELECT @option_group_id_label_type := max(id) from civicrm_option_group where name = 'label_type';
SELECT @option_group_id_name_badge := max(id) from civicrm_option_group where name = 'name_badge';
SELECT @option_group_id_communication_style := max(id) from civicrm_option_group where name = 'communication_style';
SELECT @option_group_id_msg_mode := max(id) from civicrm_option_group where name = 'msg_mode';
SELECT @option_group_id_contactDateMode := max(id) from civicrm_option_group where name = 'contact_date_reminder_options';
SELECT @option_group_id_date_filter    := max(id) from civicrm_option_group where name = 'relative_date_filters';
SELECT @option_group_id_wysiwyg_presets    := max(id) from civicrm_option_group where name = 'wysiwyg_presets';
SELECT @option_group_id_ps    := max(id) from civicrm_option_group where name = 'pledge_status';
SELECT @option_group_id_env    := max(id) from civicrm_option_group where name = 'environment';
SELECT @option_group_id_default_assignee := max(id) from civicrm_option_group where name = 'activity_default_assignee';

SELECT @contributeCompId := max(id) FROM civicrm_component where name = 'CiviContribute';
SELECT @eventCompId      := max(id) FROM civicrm_component where name = 'CiviEvent';
SELECT @memberCompId     := max(id) FROM civicrm_component where name = 'CiviMember';
SELECT @pledgeCompId     := max(id) FROM civicrm_component where name = 'CiviPledge';
SELECT @caseCompId       := max(id) FROM civicrm_component where name = 'CiviCase';
SELECT @grantCompId      := max(id) FROM civicrm_component where name = 'CiviGrant';
SELECT @campaignCompId   := max(id) FROM civicrm_component where name = 'CiviCampaign';
SELECT @mailCompId       := max(id) FROM civicrm_component where name = 'CiviMail';

INSERT INTO
   `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`, `icon`)
VALUES
   (@option_group_id_pcm, '{ts escape="sql"}Phone{/ts}',       1, 'Phone', NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_pcm, '{ts escape="sql"}Email{/ts}',       2, 'Email', NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_pcm, '{ts escape="sql"}Postal Mail{/ts}', 3, 'Postal Mail', NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_pcm, '{ts escape="sql"}SMS{/ts}',         4, 'SMS', NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_pcm, '{ts escape="sql"}Fax{/ts}',         5, 'Fax', NULL, 0, NULL, 5, NULL, 0, 0, 1, NULL, NULL, NULL),

   (@option_group_id_act, '{ts escape="sql"}Meeting{/ts}',                1,  'Meeting',               NULL, 0, NULL, 1,  NULL,                                                                                         0, 1, 1, NULL, NULL, 'fa-slideshare'),
   (@option_group_id_act, '{ts escape="sql"}Phone Call{/ts}',             2,  'Phone Call',            NULL, 0, NULL, 2,  NULL,                                                                                         0, 1, 1, NULL, NULL, 'fa-phone'),
   (@option_group_id_act, '{ts escape="sql"}Email{/ts}',                  3,  'Email',                 NULL, 1, NULL, 3,  '{ts escape="sql"}Email sent.{/ts}',                                                          0, 1, 1, NULL, NULL, 'fa-envelope-o'),
   (@option_group_id_act, '{ts escape="sql"}Outbound SMS{/ts}',           4,  'SMS',                   NULL, 1, NULL, 4,  '{ts escape="sql"}Text message (SMS) sent.{/ts}',                                             0, 1, 1, NULL, NULL, 'fa-mobile'),
   (@option_group_id_act, '{ts escape="sql"}Event Registration{/ts}',     5,  'Event Registration',    NULL, 1, NULL, 5,  '{ts escape="sql"}Online or offline event registration.{/ts}',                                0, 1, 1, @eventCompId, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}Contribution{/ts}',           6,  'Contribution',          NULL, 1, NULL, 6,  '{ts escape="sql"}Online or offline contribution.{/ts}',                                      0, 1, 1, @contributeCompId, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}Membership Signup{/ts}',      7,  'Membership Signup',     NULL, 1, NULL, 7,  '{ts escape="sql"}Online or offline membership signup.{/ts}',                                 0, 1, 1, @memberCompId, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}Membership Renewal{/ts}',     8,  'Membership Renewal',    NULL, 1, NULL, 8,  '{ts escape="sql"}Online or offline membership renewal.{/ts}',                                0, 1, 1, @memberCompId, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}Tell a Friend{/ts}',          9,  'Tell a Friend',         NULL, 1, NULL, 9,  '{ts escape="sql"}Send information about a contribution campaign or event to a friend.{/ts}', 0, 1, 1, NULL, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}Pledge Acknowledgment{/ts}',  10, 'Pledge Acknowledgment', NULL, 1, NULL, 10, '{ts escape="sql"}Send Pledge Acknowledgment.{/ts}',                                          0, 1, 1, @pledgeCompId, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}Pledge Reminder{/ts}',        11, 'Pledge Reminder',       NULL, 1, NULL, 11, '{ts escape="sql"}Send Pledge Reminder.{/ts}',                                                0, 1, 1, @pledgeCompId, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}Inbound Email{/ts}',          12, 'Inbound Email',         NULL, 1, NULL, 12, '{ts escape="sql"}Inbound Email.{/ts}',                                                       0, 1, 1, NULL, NULL, NULL),

-- Activity Types for case activities
   (@option_group_id_act, '{ts escape="sql"}Open Case{/ts}',          13, 'Open Case',          NULL, 0,  0, 13, '', 0, 1, 1, @caseCompId, NULL, 'fa-folder-open-o'),
   (@option_group_id_act, '{ts escape="sql"}Follow up{/ts}',          14, 'Follow up',          NULL, 0,  0, 14, '', 0, 1, 1, @caseCompId, NULL, 'fa-share-square-o'),
   (@option_group_id_act, '{ts escape="sql"}Change Case Type{/ts}',   15, 'Change Case Type',   NULL, 0,  0, 15, '', 0, 1, 1, @caseCompId, NULL, 'fa-random'),
   (@option_group_id_act, '{ts escape="sql"}Change Case Status{/ts}', 16, 'Change Case Status', NULL, 0,  0, 16, '', 0, 1, 1, @caseCompId, NULL, 'fa-pencil-square-o'),
   (@option_group_id_act, '{ts escape="sql"}Change Case Subject{/ts}',53, 'Change Case Subject',NULL, 0,  0, 53, '', 0, 1, 1, @caseCompId, NULL, 'fa-pencil-square-o'),
   (@option_group_id_act, '{ts escape="sql"}Change Custom Data{/ts}', 33, 'Change Custom Data', NULL, 0,  0, 33, '', 0, 1, 1, @caseCompId, NULL, 'fa-table'),

   (@option_group_id_act, '{ts escape="sql"}Membership Renewal Reminder{/ts}',        17, 'Membership Renewal Reminder',  NULL, 1, NULL, 17, '{ts escape="sql"}offline membership renewal reminder.{/ts}',                      0, 1, 1, @memberCompId, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}Change Case Start Date{/ts}',             18, 'Change Case Start Date',         NULL, 0,  0, 18, '', 0, 1, 1, @caseCompId, NULL , 'fa-calendar'),
   (@option_group_id_act, '{ts escape="sql"}Bulk Email{/ts}',                         19, 'Bulk Email',         NULL, 1, NULL, 19, '{ts escape="sql"}Bulk Email Sent.{/ts}',                                                    0, 1, 1, NULL, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}Assign Case Role{/ts}',                   20, 'Assign Case Role', NULL,0, 0, 20, '', 0, 1, 1, @caseCompId, NULL, 'fa-user-plus'),
   (@option_group_id_act, '{ts escape="sql"}Remove Case Role{/ts}',                   21, 'Remove Case Role', NULL,0, 0, 21, '', 0, 1, 1, @caseCompId, NULL, 'fa-user-times'),
   (@option_group_id_act, '{ts escape="sql"}Print/Merge Document{/ts}',               22, 'Print PDF Letter',    NULL, 0, NULL, 22, '{ts escape="sql"}Export letters and other printable documents.{/ts}',                     0, 1, 1, NULL, NULL, 'fa-file-pdf-o'),
   (@option_group_id_act, '{ts escape="sql"}Merge Case{/ts}',                         23, 'Merge Case', NULL, 0,  NULL, 23, '', 0, 1, 1, @caseCompId, NULL , 'fa-compress'),
   (@option_group_id_act, '{ts escape="sql"}Reassigned Case{/ts}',                    24, 'Reassigned Case', NULL, 0,  NULL, 24, '', 0, 1, 1, @caseCompId, NULL , 'fa-user-circle-o'),
   (@option_group_id_act, '{ts escape="sql"}Link Cases{/ts}',                         25, 'Link Cases', NULL, 0,  NULL, 25, '', 0, 1, 1, @caseCompId, NULL , 'fa-link'),
   (@option_group_id_act, '{ts escape="sql"}Change Case Tags{/ts}',                   26, 'Change Case Tags', NULL,0, 0, 26, '', 0, 1, 1, @caseCompId, NULL, 'fa-tags'),
   (@option_group_id_act, '{ts escape="sql"}Add Client To Case{/ts}',                 27, 'Add Client To Case', NULL,0, 0, 26, '', 0, 1, 1, @caseCompId, NULL, 'fa-users'),

-- Activity Types for CiviCampaign
   (@option_group_id_act, '{ts escape="sql"}Survey{/ts}',                             28, 'Survey', NULL,0, 0, 27, '', 0, 1, 1, @campaignCompId, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}Canvass{/ts}',                            29, 'Canvass', NULL,0, 0, 28, '', 0, 1, 1, @campaignCompId, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}PhoneBank{/ts}',                          30, 'PhoneBank', NULL,0, 0, 29, '', 0, 1, 1, @campaignCompId, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}WalkList{/ts}',                           31, 'WalkList', NULL,0, 0, 30, '', 0, 1, 1, @campaignCompId, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}Petition Signature{/ts}',                 32, 'Petition', NULL,0, 0, 31, '', 0, 1, 1, @campaignCompId, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}Mass SMS{/ts}',                           34, 'Mass SMS',         NULL, 1, NULL, 34, '{ts escape="sql"}Mass SMS{/ts}',                                                    0, 1, 1, NULL, NULL, NULL),

-- Additional Membership-related Activity Types
   (@option_group_id_act, '{ts escape="sql"}Change Membership Status{/ts}',           35, 'Change Membership Status',   NULL, 1, NULL, 35, '{ts escape="sql"}Change Membership Status.{/ts}',                         0, 1, 1, @memberCompId, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}Change Membership Type{/ts}',             36, 'Change Membership Type',     NULL, 1, NULL, 36, '{ts escape="sql"}Change Membership Type.{/ts}',                           0, 1, 1, @memberCompId, NULL, NULL),

   (@option_group_id_act, '{ts escape="sql"}Cancel Recurring Contribution{/ts}',      37, 'Cancel Recurring Contribution', NULL,1, 0, 37, '', 0, 1, 1, @contributeCompId, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}Update Recurring Contribution Billing Details{/ts}',      38, 'Update Recurring Contribution Billing Details', NULL,1, 0, 38, '', 0, 1, 1, @contributeCompId, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}Update Recurring Contribution{/ts}',      39, 'Update Recurring Contribution', NULL,1, 0, 39, '', 0, 1, 1, @contributeCompId, NULL, NULL),

   (@option_group_id_act, '{ts escape="sql"}Reminder Sent{/ts}',                40, 'Reminder Sent', NULL, 1, 0, 40, '', 0, 1, 1, NULL, NULL, NULL),

 -- Activity Types for Financial Transactions Batch
   (@option_group_id_act, '{ts escape="sql"}Export Accounting Batch{/ts}', 41, 'Export Accounting Batch', NULL, 1, 0, 41, 'Export Accounting Batch', 0, 1, 1, @contributeCompId, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}Create Batch{/ts}', 42, 'Create Batch', NULL, 1, 0, 42, 'Create Batch', 0, 1, 1, @contributeCompId, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}Edit Batch{/ts}', 43, 'Edit Batch', NULL, 1, 0, 43, 'Edit Batch', 0, 1, 1, @contributeCompId, NULL, NULL),

-- new sms options
   (@option_group_id_act, '{ts escape="sql"}SMS delivery{/ts}', 44, 'SMS delivery', NULL, 1, NULL, 44, '{ts escape="sql"}SMS delivery{/ts}', 0, 1, 1, NULL, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}Inbound SMS{/ts}',  45, 'Inbound SMS', NULL, 1, NULL,  45, '{ts escape="sql"}Inbound SMS{/ts}', 0, 1, 1, NULL, NULL, NULL),


 -- Activity types for particial payment
   (@option_group_id_act, '{ts escape="sql"}Payment{/ts}', 46, 'Payment', NULL, 1, NULL, 46, '{ts escape="sql"}Additional payment recorded for event or membership fee.{/ts}', 0, 1, 1, @contributeCompId, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}Refund{/ts}', 47, 'Refund', NULL, 1, NULL, 47, '{ts escape="sql"}Refund recorded for event or membership fee.{/ts}', 0, 1, 1, @contributeCompId, NULL, NULL),

 -- for selection changes
   (@option_group_id_act, '{ts escape="sql"}Change Registration{/ts}', 48, 'Change Registration', NULL, 1, NULL, 48, '{ts escape="sql"}Changes to an existing event registration.{/ts}', 0, 1, 1, @eventCompId, NULL, NULL),
 -- for Print or Email Contribution Invoices
   (@option_group_id_act, '{ts escape="sql"}Downloaded Invoice{/ts}', 49, 'Downloaded Invoice',      NULL, 1, NULL, 49, '{ts escape="sql"}Downloaded Invoice.{/ts}',0, 1, 1, NULL, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}Emailed Invoice{/ts}', 50, 'Emailed Invoice',      NULL, 1, NULL, 50, '{ts escape="sql"}Emailed Invoice.{/ts}',0, 1, 1, NULL, NULL, NULL),

  -- for manual contact merge
   (@option_group_id_act, '{ts escape="sql"}Contact Merged{/ts}', 51, 'Contact Merged', NULL, 1, NULL, 51, '{ts escape="sql"}Contact Merged{/ts}',0, 1, 1, NULL, NULL, NULL),
   (@option_group_id_act, '{ts escape="sql"}Contact Deleted by Merge{/ts}', 52, 'Contact Deleted by Merge', NULL, 1, NULL, 52, '{ts escape="sql"}Contact was merged into another contact{/ts}',0, 1, 1, NULL, NULL, NULL),

  -- Activity Type for failed payment
   (@option_group_id_act, 'Failed Payment', 54, 'Failed Payment', NULL, 1, 0, 54, 'Failed Payment', 0, 1, 1, @contributeCompId, NULL, NULL),

   (@option_group_id_gender, '{ts escape="sql"}Female{/ts}', 1, 'Female', NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_gender, '{ts escape="sql"}Male{/ts}',   2, 'Male',   NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_gender, '{ts escape="sql"}Other{/ts}',  3, 'Other',  NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),

   (@option_group_id_IMProvider, 'Yahoo', 1, 'Yahoo', NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_IMProvider, 'MSN',   2, 'Msn',   NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_IMProvider, 'AIM',   3, 'Aim',   NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_IMProvider, 'GTalk', 4, 'Gtalk', NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_IMProvider, 'Jabber',5, 'Jabber',NULL, 0, NULL, 5, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_IMProvider, 'Skype', 6, 'Skype', NULL, 0, NULL, 6, NULL, 0, 0, 1, NULL, NULL, NULL),

   (@option_group_id_mobileProvider, 'Sprint'  , 1, 'Sprint'  , NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_mobileProvider, 'Verizon' , 2, 'Verizon' , NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_mobileProvider, 'Cingular', 3, 'Cingular', NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),

   (@option_group_id_prefix, '{ts escape="sql"}Mrs.{/ts}', 1, 'Mrs.', NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_prefix, '{ts escape="sql"}Ms.{/ts}',  2, 'Ms.',  NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_prefix, '{ts escape="sql"}Mr.{/ts}',  3, 'Mr.',  NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_prefix, '{ts escape="sql"}Dr.{/ts}',  4, 'Dr.',  NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, NULL, NULL),

   (@option_group_id_suffix, '{ts escape="sql"}Jr.{/ts}',  1, 'Jr.', NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_suffix, '{ts escape="sql"}Sr.{/ts}',  2, 'Sr.', NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_suffix, 'II',  3, 'II',  NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_suffix, 'III', 4, 'III', NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_suffix, 'IV',  5, 'IV',  NULL, 0, NULL, 5, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_suffix, 'V',   6, 'V',   NULL, 0, NULL, 6, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_suffix, 'VI',  7, 'VI',  NULL, 0, NULL, 7, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_suffix, 'VII', 8, 'VII', NULL, 0, NULL, 8, NULL, 0, 0, 1, NULL, NULL, NULL),

   (@option_group_id_aclRole, '{ts escape="sql"}Administrator{/ts}',  1, 'Admin', NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_aclRole, '{ts escape="sql"}Authenticated{/ts}',  2, 'Auth' , NULL, 0, NULL, 2, NULL, 0, 1, 1, NULL, NULL, NULL),

   (@option_group_id_acc, 'Visa'      ,  1, 'Visa'      , NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_acc, 'MasterCard',  2, 'MasterCard', NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_acc, 'Amex'      ,  3, 'Amex'      , NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_acc, 'Discover'  ,  4, 'Discover'  , NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, NULL, NULL),

  (@option_group_id_pi, '{ts escape="sql"}Credit Card{/ts}',  1, 'Credit Card', NULL, 0, 0, 1, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_pi, '{ts escape="sql"}Debit Card{/ts}',   2, 'Debit Card',  NULL, 0, 0, 2, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_pi, '{ts escape="sql"}Cash{/ts}',         3, 'Cash',        NULL, 0, 0, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_pi, '{ts escape="sql"}Check{/ts}',        4, 'Check',       NULL, 0, 1, 4, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_pi, '{ts escape="sql"}EFT{/ts}',          5, 'EFT',         NULL, 0, 0, 5, NULL, 0, 0, 1, NULL, NULL, NULL),

  (@option_group_id_cs, '{ts escape="sql"}Completed{/ts}'  , 1, 'Completed'  , NULL, 0, NULL, 1, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_cs, '{ts escape="sql"}Pending{/ts}'    , 2, 'Pending'    , NULL, 0, NULL, 2, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_cs, '{ts escape="sql"}Cancelled{/ts}'  , 3, 'Cancelled'  , NULL, 0, NULL, 3, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_cs, '{ts escape="sql"}Failed{/ts}'     , 4, 'Failed'     , NULL, 0, NULL, 4, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_cs, '{ts escape="sql"}In Progress{/ts}', 5, 'In Progress', NULL, 0, NULL, 5, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_cs, '{ts escape="sql"}Overdue{/ts}'    , 6, 'Overdue'    , NULL, 0, NULL, 6, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_cs, '{ts escape="sql"}Refunded{/ts}'   , 7, 'Refunded'   , NULL, 0, NULL, 7, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_cs, '{ts escape="sql"}Partially paid{/ts}', 8, 'Partially paid', NULL, 0, NULL, 8, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_cs, '{ts escape="sql"}Pending refund{/ts}', 9, 'Pending refund', NULL, 0, NULL, 9, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_cs, '{ts escape="sql"}Chargeback{/ts}', 10, 'Chargeback', NULL, 0, NULL, 10, NULL, 0, 1, 1, NULL, NULL, NULL),

  (@option_group_id_pcp, '{ts escape="sql"}Waiting Review{/ts}', 1, 'Waiting Review', NULL, 0, NULL, 1, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_pcp, '{ts escape="sql"}Approved{/ts}'      , 2, 'Approved'      , NULL, 0, NULL, 2, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_pcp, '{ts escape="sql"}Not Approved{/ts}'  , 3, 'Not Approved'  , NULL, 0, NULL, 3, NULL, 0, 1, 1, NULL, NULL, NULL),

  (@option_group_id_pcpOwnerNotify, '{ts escape="sql"}Owner chooses whether to receive notifications{/ts}', 1, 'owner_chooses', NULL, 0, 1, 1, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_pcpOwnerNotify, '{ts escape="sql"}Notifications are sent to ALL owners{/ts}'      , 2, 'all_owners'      , NULL, 0, 0, 2, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_pcpOwnerNotify, '{ts escape="sql"}Notifications are NOT available{/ts}'  , 3, 'no_notifications'  , NULL, 0, 0, 3, NULL, 0, 1, 1, NULL, NULL, NULL),

  (@option_group_id_pRole, '{ts escape="sql"}Attendee{/ts}',  1, 'Attendee',  NULL, 1, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_pRole, '{ts escape="sql"}Volunteer{/ts}', 2, 'Volunteer', NULL, 1, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_pRole, '{ts escape="sql"}Host{/ts}',      3, 'Host',      NULL, 1, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_pRole, '{ts escape="sql"}Speaker{/ts}',   4, 'Speaker',   NULL, 1, NULL, 4, NULL, 0, 0, 1, NULL, NULL, NULL),

  (@option_group_id_etype, '{ts escape="sql"}Conference{/ts}', 1, 'Conference',  NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_etype, '{ts escape="sql"}Exhibition{/ts}', 2, 'Exhibition',  NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_etype, '{ts escape="sql"}Fundraiser{/ts}', 3, 'Fundraiser',  NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_etype, '{ts escape="sql"}Meeting{/ts}',    4, 'Meeting',     NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_etype, '{ts escape="sql"}Performance{/ts}',5, 'Performance', NULL, 0, NULL, 5, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_etype, '{ts escape="sql"}Workshop{/ts}',   6, 'Workshop',    NULL, 0, NULL, 6, NULL, 0, 0, 1, NULL, NULL, NULL),

-- note that these are not ts'ed since they are used for logic in most cases and not display
-- they are used for display only in the prefernces field settings
  (@option_group_id_cvOpt, '{ts escape="sql"}Activities{/ts}'   ,   1, 'activity', NULL, 0, NULL,  1,  NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_cvOpt, '{ts escape="sql"}Relationships{/ts}',   2, 'rel', NULL, 0, NULL,  2,  NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_cvOpt, '{ts escape="sql"}Groups{/ts}'       ,   3, 'group', NULL, 0, NULL,  3,  NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_cvOpt, '{ts escape="sql"}Notes{/ts}'        ,   4, 'note', NULL, 0, NULL,  4,  NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_cvOpt, '{ts escape="sql"}Tags{/ts}'         ,   5, 'tag', NULL, 0, NULL,  5,  NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_cvOpt, '{ts escape="sql"}Change Log{/ts}'   ,   6, 'log', NULL, 0, NULL,  6,  NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_cvOpt, '{ts escape="sql"}Contributions{/ts}',   7, 'CiviContribute', NULL, 0, NULL,  7,  NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_cvOpt, '{ts escape="sql"}Memberships{/ts}'  ,   8, 'CiviMember', NULL, 0, NULL,  8,  NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_cvOpt, '{ts escape="sql"}Events{/ts}'       ,   9, 'CiviEvent', NULL, 0, NULL,  9,  NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_cvOpt, '{ts escape="sql"}Cases{/ts}'        ,  10, 'CiviCase', NULL, 0, NULL,  10, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_cvOpt, '{ts escape="sql"}Grants{/ts}'       ,  11, 'CiviGrant', NULL, 0, NULL,  11, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_cvOpt, '{ts escape="sql"}Pledges{/ts}'      ,  13, 'CiviPledge', NULL, 0, NULL,  13, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_cvOpt, '{ts escape="sql"}Mailings{/ts}'     ,  14, 'CiviMail', NULL, 0, NULL,  14, NULL, 0, 0, 1, NULL, NULL, NULL),


  (@option_group_id_csgOpt, '{ts escape="sql"}Show Smart Groups on Demand{/ts}',1, 'showondemand', NULL, 0, NULL,  1,  NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_csgOpt, '{ts escape="sql"}Always Show Smart Groups{/ts}',   2, 'alwaysshow', NULL, 0, NULL,  2,  NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_csgOpt, '{ts escape="sql"}Hide Smart Groups{/ts}'       ,   3, 'hide', NULL, 0, NULL,  3,  NULL, 0, 0, 1, NULL, NULL, NULL),

  (@option_group_id_ceOpt, '{ts escape="sql"}Custom Data{/ts}'              ,   1, 'CustomData', NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_ceOpt, '{ts escape="sql"}Address{/ts}'                  ,   2, 'Address', NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_ceOpt, '{ts escape="sql"}Communication Preferences{/ts}',   3, 'CommunicationPreferences', NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_ceOpt, '{ts escape="sql"}Notes{/ts}'                    ,   4, 'Notes', NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_ceOpt, '{ts escape="sql"}Demographics{/ts}'             ,   5, 'Demographics', NULL, 0, NULL, 5, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_ceOpt, '{ts escape="sql"}Tags and Groups{/ts}'          ,   6, 'TagsAndGroups', NULL, 0, NULL, 6, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_ceOpt, '{ts escape="sql"}Email{/ts}'                    ,   7, 'Email', NULL, 1, NULL, 7, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_ceOpt, '{ts escape="sql"}Phone{/ts}'                    ,   8, 'Phone', NULL, 1, NULL, 8, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_ceOpt, '{ts escape="sql"}Instant Messenger{/ts}'        ,   9, 'IM', NULL, 1, NULL, 9, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_ceOpt, '{ts escape="sql"}Open ID{/ts}'                  ,   10, 'OpenID', NULL, 1, NULL, 10, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_ceOpt, '{ts escape="sql"}Website{/ts}'                  ,   11, 'Website', NULL, 1, NULL, 11, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_ceOpt, '{ts escape="sql"}Prefix{/ts}'                   ,   12, 'Prefix', NULL, 2, NULL, 12, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_ceOpt, '{ts escape="sql"}Formal Title{/ts}'             ,   13, 'Formal Title', NULL, 2, NULL, 13, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_ceOpt, '{ts escape="sql"}First Name{/ts}'               ,   14, 'First Name', NULL, 2, NULL, 14, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_ceOpt, '{ts escape="sql"}Middle Name{/ts}'              ,   15, 'Middle Name', NULL, 2, NULL, 15, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_ceOpt, '{ts escape="sql"}Last Name{/ts}'                ,   16, 'Last Name', NULL, 2, NULL, 16, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_ceOpt, '{ts escape="sql"}Suffix{/ts}'                   ,   17, 'Suffix', NULL, 2, NULL, 17, NULL, 0, 0, 1, NULL, NULL, NULL),

  (@option_group_id_asOpt, '{ts escape="sql"}Address Fields{/ts}'          ,   1, 'location', NULL, 0, NULL,  1, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_asOpt, '{ts escape="sql"}Custom Fields{/ts}'           ,   2, 'custom', NULL, 0, NULL,  2, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_asOpt, '{ts escape="sql"}Activities{/ts}'              ,   3, 'activity', NULL, 0, NULL,  4, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_asOpt, '{ts escape="sql"}Relationships{/ts}'           ,   4, 'relationship', NULL, 0, NULL,  5, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_asOpt, '{ts escape="sql"}Notes{/ts}'                   ,   5, 'notes', NULL, 0, NULL,  6, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_asOpt, '{ts escape="sql"}Change{/ts} Log'              ,   6, 'changeLog', NULL, 0, NULL,  7, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_asOpt, '{ts escape="sql"}Contributions{/ts}'           ,   7, 'CiviContribute', NULL, 0, NULL,  8, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_asOpt, '{ts escape="sql"}Memberships{/ts}'             ,   8, 'CiviMember', NULL, 0, NULL,  9, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_asOpt, '{ts escape="sql"}Events{/ts}'                  ,   9, 'CiviEvent', NULL, 0, NULL, 10, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_asOpt, '{ts escape="sql"}Cases{/ts}'                   ,  10, 'CiviCase', NULL, 0, NULL, 11, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_asOpt, 'Grants'                                        ,  12, 'CiviGrant', NULL, 0, NULL, 14, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_asOpt, '{ts escape="sql"}Demographics{/ts}'            ,  13, 'demographics', NULL, 0, NULL, 15, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_asOpt, '{ts escape="sql"}Pledges{/ts}'                 ,  15, 'CiviPledge', NULL, 0, NULL, 17, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_asOpt, '{ts escape="sql"}Contact Type{/ts}'            ,  16, 'contactType', NULL, 0, NULL, 18, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_asOpt, '{ts escape="sql"}Groups{/ts}'                  ,  17, 'groups', NULL, 0, NULL, 19, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_asOpt, '{ts escape="sql"}Tags{/ts}'                    ,  18, 'tags', NULL, 0, NULL, 20, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_asOpt, '{ts escape="sql"}Mailing{/ts}'                 ,  19, 'CiviMail', NULL, 0, NULL, 21, NULL, 0, 0, 1, NULL, NULL, NULL),

  (@option_group_id_udOpt, '{ts escape="sql"}Groups{/ts}'                     , 1, 'Groups', NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_udOpt, '{ts escape="sql"}Contributions{/ts}'              , 2, 'CiviContribute', NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_udOpt, '{ts escape="sql"}Memberships{/ts}'                , 3, 'CiviMember', NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_udOpt, '{ts escape="sql"}Events{/ts}'                     , 4, 'CiviEvent', NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_udOpt, '{ts escape="sql"}My Contacts / Organizations{/ts}', 5, 'Permissioned Orgs', NULL, 0, NULL, 5, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_udOpt, '{ts escape="sql"}Pledges{/ts}'                    , 7, 'CiviPledge', NULL, 0, NULL, 7, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_udOpt, '{ts escape="sql"}Personal Campaign Pages{/ts}'    , 8, 'PCP', NULL, 0, NULL, 8, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_udOpt, '{ts escape="sql"}Assigned Activities{/ts}'        , 9, 'Assigned Activities', NULL, 0, NULL, 9, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_udOpt, '{ts escape="sql"}Invoices / Credit Notes{/ts}'     , 10, 'Invoices / Credit Notes', NULL, 0, NULL, 10, NULL, 0, 0, 1, NULL, NULL, NULL),

  (@option_group_id_acsOpt, '{ts escape="sql"}Email Address{/ts}'   , 2, 'email'         , NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_acsOpt, '{ts escape="sql"}Phone{/ts}'           , 3, 'phone'         , NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_acsOpt, '{ts escape="sql"}Street Address{/ts}'  , 4, 'street_address', NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_acsOpt, '{ts escape="sql"}City{/ts}'            , 5, 'city'          , NULL, 0, NULL, 5, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_acsOpt, '{ts escape="sql"}State/Province{/ts}'  , 6, 'state_province', NULL, 0, NULL, 6, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_acsOpt, '{ts escape="sql"}Country{/ts}'         , 7, 'country'       , NULL, 0, NULL, 7, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_acsOpt, '{ts escape="sql"}Postal Code{/ts}'     , 8, 'postal_code'   , NULL, 0, NULL, 8, NULL, 0, 0, 1, NULL, NULL, NULL),

  (@option_group_id_acConRef, '{ts escape="sql"}Email Address{/ts}'   , 2, 'email'         , NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_acConRef, '{ts escape="sql"}Phone{/ts}'           , 3, 'phone'         , NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_acConRef, '{ts escape="sql"}Street Address{/ts}'  , 4, 'street_address', NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_acConRef, '{ts escape="sql"}City{/ts}'            , 5, 'city'          , NULL, 0, NULL, 5, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_acConRef, '{ts escape="sql"}State/Province{/ts}'  , 6, 'state_province', NULL, 0, NULL, 6, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_acConRef, '{ts escape="sql"}Country{/ts}'         , 7, 'country'       , NULL, 0, NULL, 7, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_acConRef, '{ts escape="sql"}Postal Code{/ts}'     , 8, 'country'       , NULL, 0, NULL, 8, NULL, 0, 0, 1, NULL, NULL, NULL),

  (@option_group_id_adOpt, '{ts escape="sql"}Street Address{/ts}'    ,  1, 'street_address', NULL, 0, NULL,  1, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_adOpt, '{ts escape="sql"}Supplemental Address 1{/ts}'  ,  2, 'supplemental_address_1', NULL, 0, NULL,  2, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_adOpt, '{ts escape="sql"}Supplemental Address 2{/ts}'  ,  3, 'supplemental_address_2', NULL, 0, NULL,  3, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_adOpt, '{ts escape="sql"}Supplemental Address 3{/ts}'  ,  4, 'supplemental_address_3', NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_adOpt, '{ts escape="sql"}City{/ts}'              ,  5, 'city'          , NULL, 0, NULL,  5, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_adOpt, '{ts escape="sql"}Postal Code{/ts}' ,  6, 'postal_code'   , NULL, 0, NULL,  6, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_adOpt, '{ts escape="sql"}Postal Code Suffix{/ts}',  7, 'postal_code_suffix', NULL, 0, NULL,  7, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_adOpt, '{ts escape="sql"}County{/ts}'            ,  8, 'county'        , NULL, 0, NULL,  8, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_adOpt, '{ts escape="sql"}State/Province{/ts}'  ,  9, 'state_province', NULL, 0, NULL,  9, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_adOpt, '{ts escape="sql"}Country{/ts}'           , 10, 'country'       , NULL, 0, NULL, 10, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_adOpt, '{ts escape="sql"}Latitude{/ts}'          , 11, 'geo_code_1'    , NULL, 0, NULL, 11, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_adOpt, '{ts escape="sql"}Longitude{/ts}'         , 12, 'geo_code_2', NULL, 0, NULL, 12, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_adOpt, '{ts escape="sql"}Address Name{/ts}'      , 13, 'address_name', NULL, 0, NULL, 13, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_adOpt, '{ts escape="sql"}Street Address Parsing{/ts}', 14, 'street_address_parsing', NULL, 0, NULL, 14, NULL, 0, 0, 1, NULL, NULL, NULL),

  (@option_group_id_gType, '{ts escape="sql"}Access Control{/ts}', 1, 'Access Control', NULL, 0, NULL, 1, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_gType, '{ts escape="sql"}Mailing List{/ts}',   2, 'Mailing List',   NULL, 0, NULL, 2, NULL, 0, 1, 1, NULL, NULL, NULL),

  (@option_group_id_grantSt, '{ts escape="sql"}Submitted{/ts}', 1, 'Submitted',  NULL, 0, 1,    1, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_grantSt, '{ts escape="sql"}Eligible{/ts}', 2, 'Eligible',  NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_grantSt, '{ts escape="sql"}Ineligible{/ts}', 3, 'Ineligible', NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_grantSt, '{ts escape="sql"}Paid{/ts}', 4, 'Paid', NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_grantSt, '{ts escape="sql"}Awaiting Information{/ts}', 5, 'Awaiting Information', NULL, 0, NULL, 5, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_grantSt, '{ts escape="sql"}Withdrawn{/ts}', 6, 'Withdrawn', NULL, 0, NULL, 6, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_grantSt, '{ts escape="sql"}Approved for Payment{/ts}',  7, 'Approved for Payment', NULL, 0, NULL, 7, NULL, 0, 0, 1, NULL, NULL, NULL),

  (@option_group_id_csearch , 'CRM_Contact_Form_Search_Custom_Sample'               , 1, 'CRM_Contact_Form_Search_Custom_Sample'      , NULL, 0, NULL, 1, '{ts escape="sql"}Household Name and State{/ts}', 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_csearch , 'CRM_Contact_Form_Search_Custom_ContributionAggregate', 2, 'CRM_Contact_Form_Search_Custom_ContributionAggregate', NULL, 0, NULL, 2, '{ts escape="sql"}Contribution Aggregate{/ts}', 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_csearch , 'CRM_Contact_Form_Search_Custom_Basic'                , 3, 'CRM_Contact_Form_Search_Custom_Basic'       , NULL, 0, NULL, 3, '{ts escape="sql"}Basic Search{/ts}', 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_csearch , 'CRM_Contact_Form_Search_Custom_Group'                , 4, 'CRM_Contact_Form_Search_Custom_Group'       , NULL, 0, NULL, 4, '{ts escape="sql"}Include / Exclude Search{/ts}', 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_csearch , 'CRM_Contact_Form_Search_Custom_PostalMailing'        , 5, 'CRM_Contact_Form_Search_Custom_PostalMailing', NULL, 0, NULL, 5, '{ts escape="sql"}Postal Mailing{/ts}', 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_csearch , 'CRM_Contact_Form_Search_Custom_Proximity'            , 6, 'CRM_Contact_Form_Search_Custom_Proximity', NULL, 0, NULL, 6, '{ts escape="sql"}Proximity Search{/ts}', 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_csearch , 'CRM_Contact_Form_Search_Custom_EventAggregate'       , 7, 'CRM_Contact_Form_Search_Custom_EventAggregate', NULL, 0, NULL, 7, '{ts escape="sql"}Event Aggregate{/ts}', 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_csearch , 'CRM_Contact_Form_Search_Custom_ActivitySearch'       , 8, 'CRM_Contact_Form_Search_Custom_ActivitySearch', NULL, 0, NULL, 8, '{ts escape="sql"}Activity Search{/ts}', 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_csearch , 'CRM_Contact_Form_Search_Custom_PriceSet'             , 9, 'CRM_Contact_Form_Search_Custom_PriceSet', NULL, 0, NULL, 9, '{ts escape="sql"}Price Set Details for Event Participants{/ts}', 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_csearch , 'CRM_Contact_Form_Search_Custom_ZipCodeRange'         ,10, 'CRM_Contact_Form_Search_Custom_ZipCodeRange', NULL, 0, NULL, 10, '{ts escape="sql"}Zip Code Range{/ts}', 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_csearch , 'CRM_Contact_Form_Search_Custom_DateAdded'            ,11, 'CRM_Contact_Form_Search_Custom_DateAdded', NULL, 0, NULL, 11, '{ts escape="sql"}Date Added to CiviCRM{/ts}', 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_csearch , 'CRM_Contact_Form_Search_Custom_MultipleValues'       ,12, 'CRM_Contact_Form_Search_Custom_MultipleValues', NULL, 0, NULL, 12, '{ts escape="sql"}Custom Group Multiple Values Listing{/ts}', 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_csearch , 'CRM_Contact_Form_Search_Custom_ContribSYBNT'         ,13, 'CRM_Contact_Form_Search_Custom_ContribSYBNT', NULL, 0, NULL, 13, '{ts escape="sql"}Contributions made in Year X and not Year Y{/ts}', 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_csearch , 'CRM_Contact_Form_Search_Custom_TagContributions'     ,14, 'CRM_Contact_Form_Search_Custom_TagContributions', NULL, 0, NULL, 14, '{ts escape="sql"}Find Contribution Amounts by Tag{/ts}', 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_csearch , 'CRM_Contact_Form_Search_Custom_FullText'             ,15, 'CRM_Contact_Form_Search_Custom_FullText', NULL, 0, NULL, 15, '{ts escape="sql"}Full-text Search{/ts}', 0, 0, 1, NULL, NULL, NULL),

-- report templates
  (@option_group_id_report , '{ts escape="sql"}Constituent Report (Summary){/ts}',            'contact/summary',                'CRM_Report_Form_Contact_Summary',                NULL, 0, NULL, 1,  '{ts escape="sql"}Provides a list of address and telephone information for constituent records in your system.{/ts}', 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Constituent Report (Detail){/ts}',             'contact/detail',                 'CRM_Report_Form_Contact_Detail',                 NULL, 0, NULL, 2,  '{ts escape="sql"}Provides contact-related information on contributions, memberships, events and activities.{/ts}',   0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Activity Details Report{/ts}',                 'activity',                       'CRM_Report_Form_Activity',                       NULL, 0, NULL, 3,  '{ts escape="sql"}Provides a list of constituent activity including activity statistics for one/all contacts during a given date range(required){/ts}', 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Walk / Phone List Report{/ts}',                'walklist',                       'CRM_Report_Form_Walklist_Walklist',                       NULL, 0, NULL, 4,  '{ts escape="sql"}Provides a detailed report for your walk/phonelist for targeted contacts{/ts}', 0, 0, 0, NULL, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Current Employer Report{/ts}',                 'contact/currentEmployer',        'CRM_Report_Form_Contact_CurrentEmployer',        NULL, 0, NULL, 5,  '{ts escape="sql"}Provides detail list of employer employee relationships along with employment details Ex Join Date{/ts}', 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Contribution Summary Report{/ts}',             'contribute/summary',             'CRM_Report_Form_Contribute_Summary',             NULL, 0, NULL, 6,  '{ts escape="sql"}Groups and totals contributions by criteria including contact, time period, financial type, contributor location, etc.{/ts}', 0, 0, 1, @contributeCompId, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Contribution Detail Report{/ts}',              'contribute/detail',              'CRM_Report_Form_Contribute_Detail',              NULL, 0, NULL, 7,  '{ts escape="sql"}Lists specific contributions by criteria including contact, time period, financial type, contributor location, etc. Contribution summary report points to this report for contribution details.{/ts}', 0, 0, 1, @contributeCompId, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Repeat Contributions Report{/ts}',             'contribute/repeat',              'CRM_Report_Form_Contribute_Repeat',              NULL, 0, NULL, 8,  '{ts escape="sql"}Given two date ranges, shows contacts who contributed in both the date ranges with the amount contributed in each and the percentage increase / decrease.{/ts}', 0, 0, 1, @contributeCompId, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Contributions by Organization Report{/ts}',    'contribute/organizationSummary', 'CRM_Report_Form_Contribute_OrganizationSummary', NULL, 0, NULL, 9,  '{ts escape="sql"}Displays a detailed list of contributions grouped by organization, which includes contributions made by employees for the organisation.{/ts}', 0, 0, 1, @contributeCompId, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Contributions by Household Report{/ts}',       'contribute/householdSummary',    'CRM_Report_Form_Contribute_HouseholdSummary',    NULL, 0, NULL, 10, '{ts escape="sql"}Displays a detailed list of contributions grouped by household which includes contributions made by members of the household.{/ts}', 0, 0, 1, @contributeCompId, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Top Donors Report{/ts}',                       'contribute/topDonor',            'CRM_Report_Form_Contribute_TopDonor',            NULL, 0, NULL, 11, '{ts escape="sql"}Provides a list of the top donors during a time period you define. You can include as many donors as you want (for example, top 100 of your donors).{/ts}', 0, 0, 1, @contributeCompId, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}SYBUNT Report{/ts}',                           'contribute/sybunt',              'CRM_Report_Form_Contribute_Sybunt',              NULL, 0, NULL, 12, '{ts escape="sql"}SYBUNT means some year(s) but not this year. Provides a list of constituents who donated at some time in the history of your organization but did not donate during the time period you specify.{/ts}', 0, 0, 1, @contributeCompId, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}LYBUNT Report{/ts}',                           'contribute/lybunt',              'CRM_Report_Form_Contribute_Lybunt',              NULL, 0, NULL, 13, '{ts escape="sql"}LYBUNT means last year but not this year. Provides a list of constituents who donated last year but did not donate during the time period you specify as the current year.{/ts}', 0, 0, 1, @contributeCompId, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Soft Credit Report{/ts}',                      'contribute/softcredit',          'CRM_Report_Form_Contribute_SoftCredit',          NULL, 0, NULL, 14, '{ts escape="sql"}Shows contributions made by contacts that have been soft-credited to other contacts.{/ts}', 0, 0, 1,@contributeCompId, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Membership Report (Summary){/ts}',             'member/summary',                 'CRM_Report_Form_Member_Summary',                 NULL, 0, NULL, 15, '{ts escape="sql"}Provides a summary of memberships by type and join date.{/ts}', 0, 0, 1, @memberCompId, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Membership Report (Detail){/ts}',              'member/detail',                  'CRM_Report_Form_Member_Detail',                  NULL, 0, NULL, 16, '{ts escape="sql"}Provides a list of members along with their membership status and membership details (Join Date, Start Date, End Date). Can also display contributions (payments) associated with each membership.{/ts}', 0, 0, 1, @memberCompId, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Membership Report (Lapsed){/ts}',              'member/lapse',                   'CRM_Report_Form_Member_Lapse',                   NULL, 0, NULL, 17, '{ts escape="sql"}Provides a list of memberships that lapsed or will lapse before the date you specify.{/ts}', 0, 0, 1, @memberCompId, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Event Participant Report (List){/ts}',         'event/participantListing',       'CRM_Report_Form_Event_ParticipantListing',       NULL, 0, NULL, 18, '{ts escape="sql"}Provides lists of participants for an event.{/ts}', 0, 0, 1, @eventCompId, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Event Income Report (Summary){/ts}',           'event/summary',                  'CRM_Report_Form_Event_Summary',                  NULL, 0, NULL, 19, '{ts escape="sql"}Provides an overview of event income. You can include key information such as event ID, registration, attendance, and income generated to help you determine the success of an event.{/ts}', 0, 0, 1, @eventCompId, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Event Income Report (Detail){/ts}',            'event/income',                   'CRM_Report_Form_Event_Income',                   NULL, 0, NULL, 20, '{ts escape="sql"}Helps you to analyze the income generated by an event. The report can include details by participant type, status and payment method.{/ts}', 0, 0, 1, @eventCompId, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Pledge Detail Report{/ts}',                    'pledge/detail',                  'CRM_Report_Form_Pledge_Detail',                  NULL, 0, NULL, 21, '{ts escape="sql"}List of pledges including amount pledged, pledge status, next payment date, balance due, total amount paid etc.{/ts}', 0, 0, 1, @pledgeCompId, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Pledged but not Paid Report{/ts}',             'pledge/pbnp',                    'CRM_Report_Form_Pledge_Pbnp',                    NULL, 0, NULL, 22, '{ts escape="sql"}Pledged but not Paid Report{/ts}', 0, 0, 1, @pledgeCompId, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Relationship Report{/ts}',                     'contact/relationship',           'CRM_Report_Form_Contact_Relationship',           NULL, 0, NULL, 23, '{ts escape="sql"}Relationship Report{/ts}', 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Case Summary Report{/ts}',                     'case/summary',                   'CRM_Report_Form_Case_Summary',                   NULL, 0, NULL, 24, '{ts escape="sql"}Provides a summary of cases and their duration by date range, status, staff member and / or case role.{/ts}', 0, 0, 1, @caseCompId, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Case Time Spent Report{/ts}',                  'case/timespent',                 'CRM_Report_Form_Case_TimeSpent',                 NULL, 0, NULL, 25, '{ts escape="sql"}Aggregates time spent on case and / or non-case activities by activity type and contact.{/ts}', 0, 0, 1, @caseCompId, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Contact Demographics Report{/ts}',             'case/demographics',              'CRM_Report_Form_Case_Demographics',              NULL, 0, NULL, 26, '{ts escape="sql"}Demographic breakdown for case clients (and or non-case contacts) in your database. Includes custom contact fields.{/ts}', 0, 0, 1, @caseCompId, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Database Log Report{/ts}',                     'contact/log',                    'CRM_Report_Form_Contact_Log',                    NULL, 0, NULL, 27, '{ts escape="sql"}Log of contact and activity records created or updated in a given date range.{/ts}', 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Activity Summary Report{/ts}',                 'activitySummary',                'CRM_Report_Form_ActivitySummary',                NULL, 0, NULL, 28, '{ts escape="sql"}Shows activity statistics by type / date{/ts}', 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_report, '{ts escape="sql"}Bookkeeping Transactions Report{/ts}',          'contribute/bookkeeping',         'CRM_Report_Form_Contribute_Bookkeeping',         NULL, 0, 0, 29,    '{ts escape="sql"}Shows Bookkeeping Transactions Report{/ts}', 0, 0, 1, 2, NULL, NULL),
  (@option_group_id_report , {localize}'{ts escape="sql"}Grant Report (Detail){/ts}'{/localize}, 'grant/detail', 'CRM_Report_Form_Grant_Detail', NULL, 0, 0, 30, {localize}'{ts escape="sql"}Grant Report Detail{/ts}'{/localize}, 0, 0, 1, @grantCompId, NULL, NULL),
  (@option_group_id_report, {localize}'{ts escape="sql"}Participant list Count Report{/ts}'{/localize}, 'event/participantlist', 'CRM_Report_Form_Event_ParticipantListCount', NULL, 0, 0, 31, {localize}'{ts escape="sql"}Shows the Participant list with Participant Count.{/ts}'{/localize}, 0, 0, 1, @eventCompId, NULL, NULL),
  (@option_group_id_report, {localize}'{ts escape="sql"}Income Count Summary Report{/ts}'{/localize}, 'event/incomesummary', 'CRM_Report_Form_Event_IncomeCountSummary', NULL, 0, 0, 32, {localize}'{ts escape="sql"}Shows the Income Summary of events with Count.{/ts}'{/localize}, 0, 0, 1, @eventCompId, NULL, NULL),
  (@option_group_id_report, {localize}'{ts escape="sql"}Case Detail Report{/ts}'{/localize}, 'case/detail', 'CRM_Report_Form_Case_Detail', NULL, 0, 0, 33, {localize}'{ts escape="sql"}Case Details{/ts}'{/localize}, 0, 0, 1, @caseCompId, NULL, NULL),
  (@option_group_id_report, {localize}'{ts escape="sql"}Mail Bounce Report{/ts}'{/localize}, 'Mailing/bounce', 'CRM_Report_Form_Mailing_Bounce', NULL, 0, NULL, 34, {localize}'{ts escape="sql"}Bounce Report for mailings{/ts}'{/localize}, 0, 0, 1, @mailCompId, NULL, NULL),
  (@option_group_id_report, {localize}'{ts escape="sql"}Mail Summary Report{/ts}'{/localize}, 'Mailing/summary', 'CRM_Report_Form_Mailing_Summary', NULL, 0, NULL, 35, {localize}'{ts escape="sql"}Summary statistics for mailings{/ts}'{/localize}, 0, 0, 1, @mailCompId, NULL, NULL),
  (@option_group_id_report, {localize}'{ts escape="sql"}Mail Opened Report{/ts}'{/localize}, 'Mailing/opened', 'CRM_Report_Form_Mailing_Opened', NULL, 0, NULL, 36, {localize}'{ts escape="sql"}Display contacts who opened emails from a mailing{/ts}'{/localize}, 0, 0, 1, @mailCompId, NULL, NULL),
  (@option_group_id_report, {localize}'{ts escape="sql"}Mail Click-Through Report{/ts}'{/localize}, 'Mailing/clicks', 'CRM_Report_Form_Mailing_Clicks', NULL, 0, NULL, 37, {localize}'{ts escape="sql"}Display clicks from each mailing{/ts}'{/localize}, 0, 0, 1, @mailCompId, NULL, NULL),
  (@option_group_id_report, {localize}'{ts escape="sql"}Contact Logging Report (Summary){/ts}'{/localize}, 'logging/contact/summary', 'CRM_Report_Form_Contact_LoggingSummary', NULL, 0, NULL, 38, {localize}'{ts escape="sql"}Contact modification report for the logging infrastructure (summary).{/ts}'{/localize}, 0, 0, 0, NULL, NULL, NULL),
  (@option_group_id_report, {localize}'{ts escape="sql"}Contact Logging Report (Detail){/ts}'{/localize}, 'logging/contact/detail', 'CRM_Report_Form_Contact_LoggingDetail', NULL, 0, NULL, 39, {localize}'{ts escape="sql"}Contact modification report for the logging infrastructure (detail).{/ts}'{/localize}, 0, 0, 0, NULL, NULL, NULL),
  (@option_group_id_report, {localize}'{ts escape="sql"}Grant Report (Statistics){/ts}'{/localize}, 'grant/statistics', 'CRM_Report_Form_Grant_Statistics', NULL, 0, NULL, 42, {localize}'{ts escape="sql"}Shows statistics for Grants.{/ts}'{/localize}, 0, 0, 1, @grantCompId, NULL, NULL),
  (@option_group_id_report, {localize}'{ts escape="sql"}Survey Report (Detail){/ts}'{/localize},    'survey/detail', 'CRM_Report_Form_Campaign_SurveyDetails',  NULL, 0, NULL, 43, {localize}'{ts escape="sql"}Detailed report for canvassing, phone-banking, walk lists or other surveys.{/ts}'{/localize}, 0, 0, 1, @campaignCompId, NULL, NULL),
  (@option_group_id_report, {localize}'{ts escape="sql"}Personal Campaign Page Report{/ts}'{/localize}, 'contribute/pcp', 'CRM_Report_Form_Contribute_PCP', NULL, 0, NULL, 44, {localize}'{ts escape="sql"}Summarizes amount raised and number of contributors for each Personal Campaign Page.{/ts}'{/localize}, 0, 0, 1, @contributeCompId, NULL, NULL),
  (@option_group_id_report , {localize}'{ts escape="sql"}Pledge Summary Report{/ts}'{/localize}, 'pledge/summary', 'CRM_Report_Form_Pledge_Summary', NULL, 0, NULL, 45, {localize}'{ts escape="sql"}Groups and totals pledges by criteria including contact, time period, pledge status, location, etc.{/ts}'{/localize}, 0, 0, 1, @pledgeCompId, NULL, NULL),
  (@option_group_id_report , '{ts escape="sql"}Contribution Aggregate by Relationship{/ts}',                   'contribute/history',              'CRM_Report_Form_Contribute_History',              NULL, 0, NULL, 46,  '{ts escape="sql"}List contact's donation history, grouped by year, along with contributions attributed to any of the contact's related contacts.{/ts}', 0, 0, 1, @contributeCompId, NULL, NULL),
  (@option_group_id_report,  {localize}'{ts escape="sql"}Mail Detail Report{/ts}'{/localize},                                            'mailing/detail',     'CRM_Report_Form_Mailing_Detail',          NULL, 0, NULL, 47,  {localize}'{ts escape="sql"}Provides reporting on Intended and Successful Deliveries, Unsubscribes and Opt-outs, Replies and Forwards.{/ts}'{/localize},   0, 0, 1, @mailCompId, NULL, NULL),
  (@option_group_id_report, {localize}'{ts escape="sql"}Contribution and Membership Details{/ts}'{/localize}, 'member/contributionDetail', 'CRM_Report_Form_Member_ContributionDetail', NULL, 0, NULL, 48, {localize}'{ts escape="sql"}Contribution details for any type of contribution, plus associated membership information for contributions which are in payment for memberships.{/ts}'{/localize}, 0, 0, 1, @memberCompId, NULL, NULL),
  (@option_group_id_report, {localize}'{ts escape="sql"}Recurring Contributions Report{/ts}'{/localize}, 'contribute/recur', 'CRM_Report_Form_Contribute_Recur',               NULL, 0, NULL, 49, {localize}'{ts escape="sql"}Provides information about the status of recurring contributions{/ts}'{/localize}, 0, 0, 1, @contributeCompId, NULL, NULL),
  (@option_group_id_report, {localize}'{ts escape="sql"}Recurring Contributions Summary{/ts}'{/localize}, 'contribute/recursummary', 'CRM_Report_Form_Contribute_RecurSummary',               NULL, 0, NULL, 49, {localize}'{ts escape="sql"}Provides simple summary for each payment instrument for which there are recurring contributions (e.g. Credit Card, Standing Order, Direct Debit, etc., NULL), showing within a given date range.{/ts}'{/localize}, 0, 0, 1, @contributeCompId, NULL, NULL),
  (@option_group_id_report, {localize}'{ts escape="sql"}Deferred Revenue Details{/ts}'{/localize}, 'contribute/deferredrevenue', 'CRM_Report_Form_Contribute_DeferredRevenue', NULL, 0, NULL, 50, {localize}'{ts escape="sql"}Deferred Revenue Details Report{/ts}'{/localize}, 0, 0, 1, @contributeCompId, NULL, NULL),

  (@option_group_id_acs, '{ts escape="sql"}Scheduled{/ts}',    1, 'Scheduled',    NULL, 0, 1,    1, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_acs, '{ts escape="sql"}Completed{/ts}',    2, 'Completed',    NULL, 1, NULL, 2, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_acs, '{ts escape="sql"}Cancelled{/ts}',    3, 'Cancelled',    NULL, 2, NULL, 3, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_acs, '{ts escape="sql"}Left Message{/ts}', 4, 'Left Message', NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_acs, '{ts escape="sql"}Unreachable{/ts}',  5, 'Unreachable',  NULL, 2, NULL, 5, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_acs, '{ts escape="sql"}Not Required{/ts}', 6, 'Not Required', NULL, 2, NULL, 6, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_acs, '{ts escape="sql"}Available{/ts}',    7, 'Available',    NULL, 0, NULL, 7, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_acs, '{ts escape="sql"}No-show{/ts}',      8, 'No_show',      NULL, 2, NULL, 8, NULL, 0, 0, 1, NULL, NULL, NULL),

  (@option_group_id_cas, '{ts escape="sql"}Ongoing{/ts}' , 1, 'Open'  ,  'Opened', 0, 1,    1, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_cas, '{ts escape="sql"}Resolved{/ts}', 2, 'Closed',  'Closed', 0, NULL, 2, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_cas, '{ts escape="sql"}Urgent{/ts}'  , 3, 'Urgent',  'Opened', 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),

  (@option_group_id_pl, '{ts escape="sql"}Name Only{/ts}'     , 1, 'Name Only'      ,  NULL, 0, 0, 1, 'CRM_Event_Page_ParticipantListing_Name', 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_pl, '{ts escape="sql"}Name and Email{/ts}', 2, 'Name and Email' ,  NULL, 0, 0, 2, 'CRM_Event_Page_ParticipantListing_NameAndEmail', 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_pl, '{ts escape="sql"}Name, Status and Register Date{/ts}' , 3, 'Name, Status and Register Date',  NULL, 0, 0, 3, 'CRM_Event_Page_ParticipantListing_NameStatusAndDate', 0, 1, 1, NULL, NULL, NULL),

  (@option_group_id_sfe, 'jpg',   1, 'jpg',   NULL, 0, 0,  1, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_sfe, 'jpeg',  2, 'jpeg',  NULL, 0, 0,  2, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_sfe, 'png',   3, 'png',   NULL, 0, 0,  3, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_sfe, 'gif',   4, 'gif',   NULL, 0, 0,  4, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_sfe, 'txt',   5, 'txt',   NULL, 0, 0,  5, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_sfe, 'pdf',   6, 'pdf',   NULL, 0, 0,  6, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_sfe, 'doc',   7, 'doc',   NULL, 0, 0,  7, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_sfe, 'xls',   8, 'xls',   NULL, 0, 0,  8, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_sfe, 'rtf',   9, 'rtf',   NULL, 0, 0,  9, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_sfe, 'csv',  10, 'csv',   NULL, 0, 0, 10, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_sfe, 'ppt',  11, 'ppt',   NULL, 0, 0, 11, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_sfe, 'docx', 12, 'docx',  NULL, 0, 0, 12, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_sfe, 'xlsx', 13, 'xlsx',  NULL, 0, 0, 13, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_sfe, 'odt',  14, 'odt',   NULL, 0, 0, 14, NULL, 0, 0, 1, NULL, NULL, NULL),

  (@option_group_id_we, '{ts escape="sql"}Textarea{/ts}', 1, 'Textarea', NULL, 0, NULL, 1, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_we, 'CKEditor', 2, 'CKEditor', NULL, 0, NULL, 2, NULL, 0, 1, 1, NULL, NULL, NULL),

  (@option_group_id_mt, '{ts escape="sql"}Search Builder{/ts}',      1, 'Search Builder',      NULL, 0, 0,    1, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_mt, '{ts escape="sql"}Import Contact{/ts}',      2, 'Import Contact',      NULL, 0, 0,    2, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_mt, '{ts escape="sql"}Import Activity{/ts}',     3, 'Import Activity',     NULL, 0, 0,    3, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_mt, '{ts escape="sql"}Import Contribution{/ts}', 4, 'Import Contribution', NULL, 0, 0,    4, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_mt, '{ts escape="sql"}Import Membership{/ts}',   5, 'Import Membership',   NULL, 0, 0,    5, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_mt, '{ts escape="sql"}Import Participant{/ts}',  6, 'Import Participant',  NULL, 0, 0,    6, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_mt, '{ts escape="sql"}Export Contact{/ts}',      7, 'Export Contact',      NULL, 0, 0,    7, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_mt, '{ts escape="sql"}Export Contribution{/ts}', 8, 'Export Contribution', NULL, 0, 0,    8, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_mt, '{ts escape="sql"}Export Membership{/ts}',   9, 'Export Membership',   NULL, 0, 0,    9, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_mt, '{ts escape="sql"}Export Participant{/ts}', 10, 'Export Participant',  NULL, 0, 0,   10, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_mt, '{ts escape="sql"}Export Pledge{/ts}',      11, 'Export Pledge',       NULL, 0, 0,   11, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_mt, '{ts escape="sql"}Export Case{/ts}',        12, 'Export Case',         NULL, 0, 0,   12, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_mt, '{ts escape="sql"}Export Grant{/ts}',       13, 'Export Grant',        NULL, 0, 0,   13, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_mt, '{ts escape="sql"}Export Activity{/ts}',    14, 'Export Activity',     NULL, 0, 0,   14, NULL, 0, 1, 1, NULL, NULL, NULL),

  (@option_group_id_fu, '{ts escape="sql"}day{/ts}'    , 'day'  ,    'day',  NULL, 0, NULL, 1, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_fu, '{ts escape="sql"}week{/ts}'   , 'week' ,   'week',  NULL, 0, NULL, 2, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_fu, '{ts escape="sql"}month{/ts}'  , 'month',  'month',  NULL, 0, NULL, 3, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_fu, '{ts escape="sql"}year{/ts}'   , 'year' ,   'year',  NULL, 0, NULL, 4, NULL, 0, 1, 1, NULL, NULL, NULL),

-- phone types.
  (@option_group_id_pht, '{ts escape="sql"}Phone{/ts}' ,        1, 'Phone'      , NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_pht, '{ts escape="sql"}Mobile{/ts}',        2, 'Mobile'     , NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_pht, '{ts escape="sql"}Fax{/ts}'   ,        3, 'Fax'        , NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_pht, '{ts escape="sql"}Pager{/ts}' ,        4, 'Pager'      , NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_pht, '{ts escape="sql"}Voicemail{/ts}' ,    5, 'Voicemail'  , NULL, 0, NULL, 5, NULL, 0, 0, 1, NULL, NULL, NULL),

-- custom data types.
  (@option_group_id_cdt, 'Participant Role',       '1', 'ParticipantRole',      NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL , NULL),
  (@option_group_id_cdt, 'Participant Event Name', '2', 'ParticipantEventName', NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL , NULL),
  (@option_group_id_cdt, 'Participant Event Type', '3', 'ParticipantEventType', NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL , NULL),

-- visibility.
  (@option_group_id_vis, 'Public', 1, 'public', NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL , NULL),
  (@option_group_id_vis, 'Admin', 2, 'admin', NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL , NULL),

-- mail protocol.
  (@option_group_id_mp, 'IMAP',    1, 'IMAP',    NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL , NULL),
  (@option_group_id_mp, 'Maildir', 2, 'Maildir', NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL , NULL),
  (@option_group_id_mp, 'POP3',    3, 'POP3',    NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL , NULL),
  (@option_group_id_mp, 'Localdir', 4, 'Localdir', NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, NULL , NULL),

-- priority
  (@option_group_id_priority, '{ts escape="sql"}Urgent{/ts}', 1, 'Urgent', NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_priority, '{ts escape="sql"}Normal{/ts}', 2, 'Normal', NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_priority, '{ts escape="sql"}Low{/ts}',    3, 'Low',    NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),

-- redaction rule FIXME: should this be in sample data instead?
  (@option_group_id_rr, 'Vancouver', 'city_', 'city_', NULL, 0, NULL, 1, NULL, 0, 0, 0, NULL, NULL, NULL),
  (@option_group_id_rr, '{literal}/(19|20)(\\d{2})-(\\d{1,2})-(\\d{1,2})/{/literal}', 'date_', 'date_', NULL, 1, NULL, 2, NULL, 0, 0, 0, NULL, NULL, NULL),

-- email greeting.
  (@option_group_id_emailGreeting, '{literal}Dear {contact.first_name}{/literal}',                                                 1, '{literal}Dear {contact.first_name}{/literal}',                                                 NULL,    1, 1, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_emailGreeting, '{literal}Dear {contact.individual_prefix} {contact.first_name} {contact.last_name}{/literal}', 2, '{literal}Dear {contact.individual_prefix} {contact.first_name} {contact.last_name}{/literal}', NULL,    1, 0, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_emailGreeting, '{literal}Dear {contact.individual_prefix} {contact.last_name}{/literal}',                      3, '{literal}Dear {contact.individual_prefix} {contact.last_name}{/literal}',                      NULL,    1, 0, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_emailGreeting, '{literal}Customized{/literal}',                                                                4, '{literal}Customized{/literal}',                                                                NULL, 0, 0, 4, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_emailGreeting, '{literal}Dear {contact.household_name}{/literal}',                                             5, '{literal}Dear {contact.household_name}{/literal}',                                             NULL,    2, 1, 5, NULL, 0, 0, 1, NULL, NULL, NULL),
-- postal greeting.
  (@option_group_id_postalGreeting, '{literal}Dear {contact.first_name}{/literal}',                                                 1, '{literal}Dear {contact.first_name}{/literal}',                                                 NULL,    1, 1, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_postalGreeting, '{literal}Dear {contact.individual_prefix} {contact.first_name} {contact.last_name}{/literal}', 2, '{literal}Dear {contact.individual_prefix} {contact.first_name} {contact.last_name}{/literal}', NULL,    1, 0, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_postalGreeting, '{literal}Dear {contact.individual_prefix} {contact.last_name}{/literal}',                      3, '{literal}Dear {contact.individual_prefix} {contact.last_name}{/literal}',                      NULL,    1, 0, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_postalGreeting, '{literal}Customized{/literal}',                                                                4, '{literal}Customized{/literal}',                                                                NULL, 0, 0, 4, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_postalGreeting, '{literal}Dear {contact.household_name}{/literal}',                                             5, '{literal}Dear {contact.household_name}{/literal}',                                             NULL,    2, 1, 5, NULL, 0, 0, 1, NULL, NULL, NULL),

-- addressee
  (@option_group_id_addressee, '{literal}{contact.individual_prefix}{ } {contact.first_name}{ }{contact.middle_name}{ }{contact.last_name}{ }{contact.individual_suffix}{/literal}',          '1', '{literal}}{contact.individual_prefix}{ } {contact.first_name}{ }{contact.middle_name}{ }{contact.last_name}{ }{contact.individual_suffix}{/literal}',         NULL ,   '1', '1', '1', NULL , '0', '0', '1', NULL , NULL, NULL),
  (@option_group_id_addressee, '{literal}{contact.household_name}{/literal}',    '2', '{literal}{contact.household_name}{/literal}',    NULL ,   '2', '1', '2', NULL , '0', '0', '1', NULL , NULL, NULL),
  (@option_group_id_addressee, '{literal}{contact.organization_name}{/literal}', '3', '{literal}{contact.organization_name}{/literal}', NULL ,   '3', '1', '3', NULL , '0', '0', '1', NULL , NULL, NULL),
  (@option_group_id_addressee, '{literal}Customized{/literal}',                  '4', '{literal}Customized{/literal}',                  NULL ,    0 , '0', '4', NULL , '0', '1', '1', NULL , NULL, NULL),

-- website type
   (@option_group_id_website, 'Work',     1, 'Work',     NULL, 0, 1, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_website, 'Main',     2, 'Main',     NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_website, 'Facebook', 3, 'Facebook', NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_website, 'Google+',  4, 'Google_',  NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_website, 'Instagram',  5, 'Instagram',  NULL, 0, NULL, 5, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_website, 'LinkedIn',  6, 'LinkedIn',  NULL, 0, NULL, 6, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_website, 'MySpace',  7, 'MySpace',  NULL, 0, NULL, 7, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_website, 'Pinterest',  8, 'Pinterest',  NULL, 0, NULL, 8, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_website, 'SnapChat',  9, 'SnapChat',  NULL, 0, NULL, 9, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_website, 'Tumblr',  10, 'Tumblr',  NULL, 0, NULL, 10, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_website, 'Twitter',  11, 'Twitter',  NULL, 0, NULL, 11, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_website, 'Vine',  12, 'Vine ',  NULL, 0, NULL, 12, NULL, 0, 0, 1, NULL, NULL, NULL),

-- Tag used for
   (@option_group_id_tuf, 'Contacts',   'civicrm_contact',  'Contacts',     NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_tuf, 'Activities', 'civicrm_activity', 'Activities',   NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_tuf, 'Cases',      'civicrm_case',     'Cases',        NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_tuf, 'Attachments','civicrm_file',     'Attachements', NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, NULL, NULL),

   (@option_group_id_currency, 'USD ($)',      'USD',     'USD',       NULL, 0, 1, 1, NULL, 0, 0, 1, NULL, NULL, NULL),

-- event name badges
  (@option_group_id_eventBadge,  '{ts escape="sql"}Name Only{/ts}'     , 1, 'CRM_Event_Badge_Simple'  ,  NULL, 0, 0, 1, '{ts escape="sql"}Simple Event Name Badge{/ts}', 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_eventBadge,  '{ts escape="sql"}Name Tent{/ts}'     , 2, 'CRM_Event_Badge_NameTent',  NULL, 0, 0, 2, '{ts escape="sql"}Name Tent{/ts}', 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_eventBadge , '{ts escape="sql"}With Logo{/ts}'     , 3, 'CRM_Event_Badge_Logo'    ,  NULL, 0, 0, 3, '{ts escape="sql"}You can set your own background image{/ts}',                               0, 1, 1, NULL, NULL , NULL),
  (@option_group_id_eventBadge , '{ts escape="sql"}5395 with Logo{/ts}', 4, 'CRM_Event_Badge_Logo5395',  NULL, 0, 0, 4, '{ts escape="sql"}Avery 5395 compatible labels with logo (4 up by 2, 59.2mm x 85.7mm){/ts}', 0, 1, 1, NULL, NULL , NULL),

-- note privacy levels
  (@option_group_id_notePrivacy, '{ts escape="sql"}None{/ts}',        0, 'None',        NULL, 0, 1, 1, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_notePrivacy, '{ts escape="sql"}Author Only{/ts}', 1, 'Author Only', NULL, 0, 0, 2, NULL, 0, 1, 1, NULL, NULL, NULL),

-- Compaign Types
  (@option_group_id_campaignType, '{ts escape="sql"}Direct Mail{/ts}', 1, 'Direct Mail',  NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_campaignType, '{ts escape="sql"}Referral Program{/ts}', 2, 'Referral Program',  NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_campaignType, '{ts escape="sql"}Constituent Engagement{/ts}', 3, 'Constituent Engagement',  NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),

-- Campaign Status
  (@option_group_id_campaignStatus, '{ts escape="sql"}Planned{/ts}', 1, 'Planned',  NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_campaignStatus, '{ts escape="sql"}In Progress{/ts}', 2, 'In Progress',  NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_campaignStatus, '{ts escape="sql"}Completed{/ts}', 3, 'Completed',  NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_campaignStatus, '{ts escape="sql"}Cancelled{/ts}', 4, 'Cancelled',  NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),

-- Engagement Level
  (@option_group_id_engagement_index, '{ts escape="sql"}1{/ts}', 1, '1',  NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL , NULL),
  (@option_group_id_engagement_index, '{ts escape="sql"}2{/ts}', 2, '2',  NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL , NULL),
  (@option_group_id_engagement_index, '{ts escape="sql"}3{/ts}', 3, '3',  NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL , NULL),
  (@option_group_id_engagement_index, '{ts escape="sql"}4{/ts}', 4, '4',  NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, NULL , NULL),
  (@option_group_id_engagement_index, '{ts escape="sql"}5{/ts}', 5, '5',  NULL, 0, NULL, 5, NULL, 0, 0, 1, NULL, NULL , NULL),

-- Paper Sizes
  (@option_group_id_paperSize, '{ts escape="sql"}Letter{/ts}',          '{literal}{"metric":"in","width":8.5,"height":11}{/literal}',          'letter',      NULL, NULL, 1, 1,  NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}Legal{/ts}',           '{literal}{"metric":"in","width":8.5,"height":14}{/literal}',          'legal',       NULL, NULL, 0, 2,  NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}Ledger{/ts}',          '{literal}{"metric":"in","width":17,"height":11}{/literal}',           'ledger',      NULL, NULL, 0, 3,  NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}Tabloid{/ts}',         '{literal}{"metric":"in","width":11,"height":17}{/literal}',           'tabloid',     NULL, NULL, 0, 4,  NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}Executive{/ts}',       '{literal}{"metric":"in","width":7.25,"height":10.5}{/literal}',       'executive',   NULL, NULL, 0, 5,  NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}Folio{/ts}',           '{literal}{"metric":"in","width":8.5,"height":13}{/literal}',          'folio',       NULL, NULL, 0, 6,  NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}Envelope #9{/ts}',     '{literal}{"metric":"pt","width":638.93,"height":278.93}{/literal}',   'envelope-9',  NULL, NULL, 0, 7,  NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}Envelope #10{/ts}',    '{literal}{"metric":"pt","width":684,"height":297}{/literal}',         'envelope-10', NULL, NULL, 0, 8,  NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}Envelope #11{/ts}',    '{literal}{"metric":"pt","width":747,"height":324}{/literal}',         'envelope-11', NULL, NULL, 0, 9,  NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}Envelope #12{/ts}',    '{literal}{"metric":"pt","width":792,"height":342}{/literal}',         'envelope-12', NULL, NULL, 0, 10, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}Envelope #14{/ts}',    '{literal}{"metric":"pt","width":828,"height":360}{/literal}',         'envelope-14', NULL, NULL, 0, 11, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}Envelope ISO B4{/ts}', '{literal}{"metric":"pt","width":1000.63,"height":708.66}{/literal}',  'envelope-b4', NULL, NULL, 0, 12, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}Envelope ISO B5{/ts}', '{literal}{"metric":"pt","width":708.66,"height":498.9}{/literal}',    'envelope-b5', NULL, NULL, 0, 13, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}Envelope ISO B6{/ts}', '{literal}{"metric":"pt","width":498.9,"height":354.33}{/literal}',    'envelope-b6', NULL, NULL, 0, 14, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}Envelope ISO C3{/ts}', '{literal}{"metric":"pt","width":1298.27,"height":918.42}{/literal}',  'envelope-c3', NULL, NULL, 0, 15, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}Envelope ISO C4{/ts}', '{literal}{"metric":"pt","width":918.42,"height":649.13}{/literal}',   'envelope-c4', NULL, NULL, 0, 16, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}Envelope ISO C5{/ts}', '{literal}{"metric":"pt","width":649.13,"height":459.21}{/literal}',   'envelope-c5', NULL, NULL, 0, 17, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}Envelope ISO C6{/ts}', '{literal}{"metric":"pt","width":459.21,"height":323.15}{/literal}',   'envelope-c6', NULL, NULL, 0, 18, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}Envelope ISO DL{/ts}', '{literal}{"metric":"pt","width":623.622,"height":311.811}{/literal}', 'envelope-dl', NULL, NULL, 0, 19, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO A0{/ts}',          '{literal}{"metric":"pt","width":2383.94,"height":3370.39}{/literal}', 'a0',          NULL, NULL, 0, 20, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO A1{/ts}',          '{literal}{"metric":"pt","width":1683.78,"height":2383.94}{/literal}', 'a1',          NULL, NULL, 0, 21, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO A2{/ts}',          '{literal}{"metric":"pt","width":1190.55,"height":1683.78}{/literal}', 'a2',          NULL, NULL, 0, 22, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO A3{/ts}',          '{literal}{"metric":"pt","width":841.89,"height":1190.55}{/literal}',  'a3',          NULL, NULL, 0, 23, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO A4{/ts}',          '{literal}{"metric":"pt","width":595.28,"height":841.89}{/literal}',   'a4',          NULL, NULL, 0, 24, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO A5{/ts}',          '{literal}{"metric":"pt","width":419.53,"height":595.28}{/literal}',   'a5',          NULL, NULL, 0, 25, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO A6{/ts}',          '{literal}{"metric":"pt","width":297.64,"height":419.53}{/literal}',   'a6',          NULL, NULL, 0, 26, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO A7{/ts}',          '{literal}{"metric":"pt","width":209.76,"height":297.64}{/literal}',   'a7',          NULL, NULL, 0, 27, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO A8{/ts}',          '{literal}{"metric":"pt","width":147.4,"height":209.76}{/literal}',    'a8',          NULL, NULL, 0, 28, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO A9{/ts}',          '{literal}{"metric":"pt","width":104.88,"height":147.4}{/literal}',    'a9',          NULL, NULL, 0, 29, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO A10{/ts}',         '{literal}{"metric":"pt","width":73.7,"height":104.88}{/literal}',     'a10',         NULL, NULL, 0, 30, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO B0{/ts}',          '{literal}{"metric":"pt","width":2834.65,"height":4008.19}{/literal}', 'b0',          NULL, NULL, 0, 31, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO B1{/ts}',          '{literal}{"metric":"pt","width":2004.09,"height":2834.65}{/literal}', 'b1',          NULL, NULL, 0, 32, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO B2{/ts}',          '{literal}{"metric":"pt","width":1417.32,"height":2004.09}{/literal}', 'b2',          NULL, NULL, 0, 33, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO B3{/ts}',          '{literal}{"metric":"pt","width":1000.63,"height":1417.32}{/literal}', 'b3',          NULL, NULL, 0, 34, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO B4{/ts}',          '{literal}{"metric":"pt","width":708.66,"height":1000.63}{/literal}',  'b4',          NULL, NULL, 0, 35, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO B5{/ts}',          '{literal}{"metric":"pt","width":498.9,"height":708.66}{/literal}',    'b5',          NULL, NULL, 0, 36, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO B6{/ts}',          '{literal}{"metric":"pt","width":354.33,"height":498.9}{/literal}',    'b6',          NULL, NULL, 0, 37, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO B7{/ts}',          '{literal}{"metric":"pt","width":249.45,"height":354.33}{/literal}',   'b7',          NULL, NULL, 0, 38, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO B8{/ts}',          '{literal}{"metric":"pt","width":175.75,"height":249.45}{/literal}',   'b8',          NULL, NULL, 0, 39, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO B9{/ts}',          '{literal}{"metric":"pt","width":124.72,"height":175.75}{/literal}',   'b9',          NULL, NULL, 0, 40, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO B10{/ts}',         '{literal}{"metric":"pt","width":87.87,"height":124.72}{/literal}',    'b10',         NULL, NULL, 0, 41, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO C0{/ts}',          '{literal}{"metric":"pt","width":2599.37,"height":3676.54}{/literal}', 'c0',          NULL, NULL, 0, 42, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO C1{/ts}',          '{literal}{"metric":"pt","width":1836.85,"height":2599.37}{/literal}', 'c1',          NULL, NULL, 0, 43, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO C2{/ts}',          '{literal}{"metric":"pt","width":1298.27,"height":1836.85}{/literal}', 'c2',          NULL, NULL, 0, 44, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO C3{/ts}',          '{literal}{"metric":"pt","width":918.43,"height":1298.27}{/literal}',  'c3',          NULL, NULL, 0, 45, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO C4{/ts}',          '{literal}{"metric":"pt","width":649.13,"height":918.43}{/literal}',   'c4',          NULL, NULL, 0, 46, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO C5{/ts}',          '{literal}{"metric":"pt","width":459.21,"height":649.13}{/literal}',   'c5',          NULL, NULL, 0, 47, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO C6{/ts}',          '{literal}{"metric":"pt","width":323.15,"height":459.21}{/literal}',   'c6',          NULL, NULL, 0, 48, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO C7{/ts}',          '{literal}{"metric":"pt","width":229.61,"height":323.15}{/literal}',   'c7',          NULL, NULL, 0, 49, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO C8{/ts}',          '{literal}{"metric":"pt","width":161.57,"height":229.61}{/literal}',   'c8',          NULL, NULL, 0, 50, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO C9{/ts}',          '{literal}{"metric":"pt","width":113.39,"height":161.57}{/literal}',   'c9',          NULL, NULL, 0, 51, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO C10{/ts}',         '{literal}{"metric":"pt","width":79.37,"height":113.39}{/literal}',    'c10',         NULL, NULL, 0, 52, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO RA0{/ts}',         '{literal}{"metric":"pt","width":2437.8,"height":3458.27}{/literal}',  'ra0',         NULL, NULL, 0, 53, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO RA1{/ts}',         '{literal}{"metric":"pt","width":1729.13,"height":2437.8}{/literal}',  'ra1',         NULL, NULL, 0, 54, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO RA2{/ts}',         '{literal}{"metric":"pt","width":1218.9,"height":1729.13}{/literal}',  'ra2',         NULL, NULL, 0, 55, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO RA3{/ts}',         '{literal}{"metric":"pt","width":864.57,"height":1218.9}{/literal}',   'ra3',         NULL, NULL, 0, 56, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO RA4{/ts}',         '{literal}{"metric":"pt","width":609.45,"height":864.57}{/literal}',   'ra4',         NULL, NULL, 0, 57, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO SRA0{/ts}',        '{literal}{"metric":"pt","width":2551.18,"height":3628.35}{/literal}', 'sra0',        NULL, NULL, 0, 58, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO SRA1{/ts}',        '{literal}{"metric":"pt","width":1814.17,"height":2551.18}{/literal}', 'sra1',        NULL, NULL, 0, 59, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO SRA2{/ts}',        '{literal}{"metric":"pt","width":1275.59,"height":1814.17}{/literal}', 'sra2',        NULL, NULL, 0, 60, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO SRA3{/ts}',        '{literal}{"metric":"pt","width":907.09,"height":1275.59}{/literal}',  'sra3',        NULL, NULL, 0, 61, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_paperSize, '{ts escape="sql"}ISO SRA4{/ts}',        '{literal}{"metric":"pt","width":637.8,"height":907.09}{/literal}',    'sra4',        NULL, NULL, 0, 62, NULL, 0, 0, 1, NULL, NULL, NULL),

-- activity_contacts
   (@option_group_id_aco, '{ts escape="sql"}Activity Assignees{/ts}', 1, 'Activity Assignees', NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_aco, '{ts escape="sql"}Activity Source{/ts}', 2, 'Activity Source', NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_aco, '{ts escape="sql"}Activity Targets{/ts}', 3, 'Activity Targets', NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),

-- financial_account_type
-- grouping field is specific to Quickbooks for mapping to .iif format
   (@option_group_id_fat, '{ts escape="sql"}Asset{/ts}', 1, 'Asset', NULL, 0, 0, 1, 'Things you own', 0, 1, 1, 2, NULL, NULL),
   (@option_group_id_fat, '{ts escape="sql"}Liability{/ts}', 2, 'Liability', NULL, 0, 0, 2, 'Things you owe, like a grant still to be disbursed', 0, 1, 1, 2, NULL, NULL),
   (@option_group_id_fat, '{ts escape="sql"}Revenue{/ts}', 3, 'Revenue', NULL, 0, 1, 3, 'Income from contributions and sales of tickets and memberships', 0, 1, 1, 2, NULL, NULL),
   (@option_group_id_fat, '{ts escape="sql"}Cost of Sales{/ts}', 4, 'Cost of Sales', NULL, 0, 0, 4, 'Costs incurred to get revenue, e.g. premiums for donations, dinner for a fundraising dinner ticket', 0, 1, 1, 2, NULL, NULL),
   (@option_group_id_fat, '{ts escape="sql"}Expenses{/ts}', 5, 'Expenses', NULL, 0, 0, 5, 'Things that are paid for that are consumable, e.g. grants disbursed', 0, 1, 1, 2, NULL, NULL),

-- account_relationship
    (@option_group_id_arel, '{ts escape="sql"}Income Account is{/ts}', 1, 'Income Account is', NULL, 0, 1, 1, 'Income Account is', 0, 1, 1, 2, NULL, NULL),
    (@option_group_id_arel, '{ts escape="sql"}Credit/Contra Revenue Account is{/ts}', 2, 'Credit/Contra Revenue Account is', NULL, 0, 0, 2, 'Credit/Contra Revenue Account is', 0, 1, 1, 2, NULL, NULL),
    (@option_group_id_arel, '{ts escape="sql"}Accounts Receivable Account is{/ts}', 3, 'Accounts Receivable Account is', NULL, 0, 0, 3, 'Accounts Receivable Account is', 0, 1, 1, 2, NULL, NULL),
    (@option_group_id_arel, '{ts escape="sql"}Credit Liability Account is{/ts}', 4, 'Credit Liability Account is', NULL, 0, 0, 4, 'Credit Liability Account is', 0, 1, 0, 2, NULL, NULL),
     (@option_group_id_arel, '{ts escape="sql"}Expense Account is{/ts}', 5, 'Expense Account is', NULL, 0, 0, 5, 'Expense Account is', 0, 1, 1, 2, NULL, NULL),
     (@option_group_id_arel, '{ts escape="sql"}Asset Account is{/ts}', 6, 'Asset Account is', NULL, 0, 0, 6, 'Asset Account is', 0, 1, 1, 2, NULL, NULL),
     (@option_group_id_arel, '{ts escape="sql"}Cost of Sales Account is{/ts}', 7, 'Cost of Sales Account is', NULL, 0, 0, 7, 'Cost of Sales Account is', 0, 1, 1, 2, NULL, NULL),
     (@option_group_id_arel, '{ts escape="sql"}Premiums Inventory Account is{/ts}', 8, 'Premiums Inventory Account is', NULL, 0, 0, 8, 'Premiums Inventory Account is', 0, 1, 1, 2, NULL, NULL),
     (@option_group_id_arel, '{ts escape="sql"}Discounts Account is{/ts}', 9, 'Discounts Account is', NULL, 0, 0, 9, 'Discounts Account is', 0, 1, 1, 2, NULL, NULL),
     (@option_group_id_arel, '{ts escape="sql"}Sales Tax Account is{/ts}', 10, 'Sales Tax Account is', NULL, 0, 0, 10, 'Sales Tax Account is', 0, 1, 1, 2, NULL, NULL),
     (@option_group_id_arel, '{ts escape="sql"}Chargeback Account is{/ts}', 11, 'Chargeback Account is', NULL, 0, 0, 11, 'Chargeback Account is', 0, 1, 1, 2, NULL, NULL),
     (@option_group_id_arel, '{ts escape="sql"}Deferred Revenue Account is{/ts}', 12, 'Deferred Revenue Account is', NULL, 0, 0, 12, 'Deferred Revenue Account is', 0, 1, 1, 2, NULL, NULL),

-- event_contacts
   (@option_group_id_ere, '{ts escape="sql"}Participant Role{/ts}', 1, 'participant_role', NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),

-- default conference slots
   (@option_group_id_conference_slot, '{ts escape="sql"}Morning Sessions{/ts}', 1, 'Morning Sessions', NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_conference_slot, '{ts escape="sql"}Evening Sessions{/ts}', 2, 'Evening Sessions', NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),

-- default batch types
   (@option_group_id_batch_type, '{ts escape="sql"}Contribution{/ts}', 1, 'Contribution', NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_batch_type, '{ts escape="sql"}Membership{/ts}', 2, 'Membership', NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_batch_type, '{ts escape="sql"}Pledge Payment{/ts}', 3, 'Pledge Payment', NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),

-- default batch statuses
   (@option_group_id_batch_status, '{ts escape="sql"}Open{/ts}', 1, 'Open', NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_batch_status, '{ts escape="sql"}Closed{/ts}', 2, 'Closed', NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_batch_status, '{ts escape="sql"}Data Entry{/ts}', 3, 'Data Entry', NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_batch_status, '{ts escape="sql"}Reopened{/ts}', 4, 'Reopened', NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_batch_status, '{ts escape="sql"}Exported{/ts}', 5, 'Exported', NULL, 0, NULL, 5, NULL, 0, 0, 1, NULL, NULL, NULL),

-- default batch modes
   (@option_group_id_batch_mode, '{ts escape="sql"}Manual Batch{/ts}', 1, 'Manual Batch', NULL, 0, 0, 1, 'Manual Batch', 0, 1, 1, 2, NULL, NULL),
   (@option_group_id_batch_mode, '{ts escape="sql"}Automatic Batch{/ts}', 2, 'Automatic Batch', NULL, 0, 0, 2, 'Automatic Batch', 0, 1, 1, 2, NULL, NULL),

-- Financial Item Status
   (@option_group_id_financial_item_status, '{ts escape="sql"}Paid{/ts}', 1, 'Paid', NULL, 0, 0, 1, 'Paid', 0, 1, 1, 2, NULL, NULL),
   (@option_group_id_financial_item_status, '{ts escape="sql"}Partially paid{/ts}', 2, 'Partially paid', NULL, 0, 0, 2, 'Partially paid', 0, 1, 1, 2, NULL, NULL),
   (@option_group_id_financial_item_status, '{ts escape="sql"}Unpaid{/ts}', 3, 'Unpaid', NULL, 0, 0, 1, 'Unpaid', 0, 1, 1, 2, NULL, NULL),

-- sms_api_type
   (@option_group_id_sms_api_type, 'http', 1, 'http', NULL, NULL, 0, 1, NULL, 0, 1, 1, NULL, NULL, NULL),
   (@option_group_id_sms_api_type, 'xml',  2, 'xml',  NULL, NULL, 0, 2, NULL, 0, 1, 1, NULL, NULL, NULL),
   (@option_group_id_sms_api_type, 'smtp', 3, 'smtp', NULL, NULL, 0, 3, NULL, 0, 1, 1, NULL, NULL, NULL),

-- auto renew options
   (@option_group_id_aro, '{ts escape="sql"}Renewal Reminder (non-auto-renew memberships only){/ts}', 1, 'Renewal Reminder (non-auto-renew memberships only)', NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_aro, '{ts escape="sql"}Auto-renew Memberships Only{/ts}', 2, 'Auto-renew Memberships Only', NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_aro, '{ts escape="sql"}Reminder for Both{/ts}', 3, 'Reminder for Both', NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),

-- Label Type
   (@option_group_id_label_type, '{ts escape="sql"}Event Badge{/ts}', 1, 'Event Badge', NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),

-- Name Label format
   (@option_group_id_name_badge, '{ts escape="sql"}Avery 5395{/ts}', '{literal}{"name":"Avery 5395","paper-size":"a4","metric":"mm","lMargin":15,"tMargin":26,"NX":2,"NY":4,"SpaceX":10,"SpaceY":5,"width":83,"height":57,"font-size":12,"orientation":"portrait","font-name":"helvetica","font-style":"","lPadding":3,"tPadding":3}{/literal}', 'Avery 5395', NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_name_badge, '{ts escape="sql"}A6 Badge Portrait 150x106{/ts}', '{literal}{"paper-size":"a4","orientation":"landscape","font-name":"times","font-size":6,"font-style":"","NX":2,"NY":1,"metric":"mm","lMargin":25,"tMargin":27,"SpaceX":0,"SpaceY":35,"width":106,"height":150,"lPadding":5,"tPadding":5}{/literal}', 'A6 Badge Portrait 150x106', NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_name_badge, '{ts escape="sql"}Fattorini Name Badge 100x65{/ts}', '{literal}{"paper-size":"a4","orientation":"portrait","font-name":"times","font-size":6,"font-style":"","NX":2,"NY":4,"metric":"mm","lMargin":6,"tMargin":19,"SpaceX":0,"SpaceY":0,"width":100,"height":65,"lPadding":0,"tPadding":0}{/literal}', 'Fattorini Name Badge 100x65', NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_name_badge, '{ts escape="sql"}Hanging Badge 3-3/4" x 4-3"/4{/ts}', '{literal}{"paper-size":"a4","orientation":"portrait","font-name":"times","font-size":6,"font-style":"","NX":2,"NY":2,"metric":"mm","lMargin":10,"tMargin":28,"SpaceX":0,"SpaceY":0,"width":96,"height":121,"lPadding":5,"tPadding":5}{/literal}', 'Hanging Badge 3-3/4" x 4-3"/4', NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, NULL, NULL),

-- Mailing Label Formats
  (@option_group_id_label, '{ts escape="sql"}Avery 3475{/ts}', '{literal}{"paper-size":"a4","orientation":"portrait","font-name":"helvetica","font-size":10,"font-style":"","metric":"mm","lMargin":0,"tMargin":5,"NX":3,"NY":8,"SpaceX":0,"SpaceY":0,"width":70,"height":36,"lPadding":5.08,"tPadding":5.08}{/literal}',                   '3475',  'Avery', NULL, 0, 1,  NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_label, '{ts escape="sql"}Avery 5160{/ts}', '{literal}{"paper-size":"letter","orientation":"portrait","font-name":"helvetica","font-size":8,"font-style":"","metric":"in","lMargin":0.21975,"tMargin":0.5,"NX":3,"NY":10,"SpaceX":0.14,"SpaceY":0,"width":2.5935,"height":1,"lPadding":0.20,"tPadding":0.20}{/literal}', '5160',  'Avery', NULL, 0, 2,  NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_label, '{ts escape="sql"}Avery 5161{/ts}', '{literal}{"paper-size":"letter","orientation":"portrait","font-name":"helvetica","font-size":8,"font-style":"","metric":"in","lMargin":0.175,"tMargin":0.5,"NX":2,"NY":10,"SpaceX":0.15625,"SpaceY":0,"width":4,"height":1,"lPadding":0.20,"tPadding":0.20}{/literal}',     '5161',  'Avery', NULL, 0, 3,  NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_label, '{ts escape="sql"}Avery 5162{/ts}', '{literal}{"paper-size":"letter","orientation":"portrait","font-name":"helvetica","font-size":8,"font-style":"","metric":"in","lMargin":0.1525,"tMargin":0.88,"NX":2,"NY":7,"SpaceX":0.195,"SpaceY":0,"width":4,"height":1.33,"lPadding":0.20,"tPadding":0.20}{/literal}',   '5162',  'Avery', NULL, 0, 4,  NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_label, '{ts escape="sql"}Avery 5163{/ts}', '{literal}{"paper-size":"letter","orientation":"portrait","font-name":"helvetica","font-size":8,"font-style":"","metric":"in","lMargin":0.18,"tMargin":0.5,"NX":2,"NY":5,"SpaceX":0.14,"SpaceY":0,"width":4,"height":2,"lPadding":0.20,"tPadding":0.20}{/literal}',          '5163',  'Avery', NULL, 0, 5,  NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_label, '{ts escape="sql"}Avery 5164{/ts}', '{literal}{"paper-size":"letter","orientation":"portrait","font-name":"helvetica","font-size":12,"font-style":"","metric":"in","lMargin":0.156,"tMargin":0.5,"NX":2,"NY":3,"SpaceX":0.1875,"SpaceY":0,"width":4,"height":3.33,"lPadding":0.20,"tPadding":0.20}{/literal}',   '5164',  'Avery', NULL, 0, 6,  NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_label, '{ts escape="sql"}Avery 8600{/ts}', '{literal}{"paper-size":"letter","orientation":"portrait","font-name":"helvetica","font-size":8,"font-style":"","metric":"mm","lMargin":7.1,"tMargin":19,"NX":3,"NY":10,"SpaceX":9.5,"SpaceY":3.1,"width":66.6,"height":25.4,"lPadding":5.08,"tPadding":5.08}{/literal}',    '8600',  'Avery', NULL, 0, 7,  NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_label, '{ts escape="sql"}Avery L7160{/ts}', '{literal}{"paper-size":"a4","orientation":"portrait","font-name":"helvetica","font-size":9,"font-style":"","metric":"in","lMargin":0.28,"tMargin":0.6,"NX":3,"NY":7,"SpaceX":0.1,"SpaceY":0,"width":2.5,"height":1.5,"lPadding":0.20,"tPadding":0.20}{/literal}',          'L7160', 'Avery', NULL, 0, 8,  NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_label, '{ts escape="sql"}Avery L7161{/ts}', '{literal}{"paper-size":"a4","orientation":"portrait","font-name":"helvetica","font-size":9,"font-style":"","metric":"in","lMargin":0.28,"tMargin":0.35,"NX":3,"NY":6,"SpaceX":0.1,"SpaceY":0,"width":2.5,"height":1.83,"lPadding":0.20,"tPadding":0.20}{/literal}',        'L7161', 'Avery', NULL, 0, 9,  NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_label, '{ts escape="sql"}Avery L7162{/ts}', '{literal}{"paper-size":"a4","orientation":"portrait","font-name":"helvetica","font-size":9,"font-style":"","metric":"in","lMargin":0.18,"tMargin":0.51,"NX":2,"NY":8,"SpaceX":0.1,"SpaceY":0,"width":3.9,"height":1.33,"lPadding":0.20,"tPadding":0.20}{/literal}',        'L7162', 'Avery', NULL, 0, 10, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_label, '{ts escape="sql"}Avery L7163{/ts}', '{literal}{"paper-size":"a4","orientation":"portrait","font-name":"helvetica","font-size":9,"font-style":"","metric":"in","lMargin":0.18,"tMargin":0.6,"NX":2,"NY":7,"SpaceX":0.1,"SpaceY":0,"width":3.9,"height":1.5,"lPadding":0.20,"tPadding":0.20}{/literal}',          'L7163', 'Avery', NULL, 0, 11, NULL, 0, 1, 1, NULL, NULL, NULL),

-- Communication Styles
  (@option_group_id_communication_style, '{ts escape="sql"}Formal{/ts}'  , 1, 'formal'  , NULL, 0, 1, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_communication_style, '{ts escape="sql"}Familiar{/ts}', 2, 'familiar', NULL, 0, 0, 2, NULL, 0, 0, 1, NULL, NULL, NULL),

-- Message Mode
(@option_group_id_msg_mode, '{ts escape="sql"}Email{/ts}', 'Email', 'Email', NULL, 0, 1, 1, NULL, 0, 1, 1, NULL, NULL, NULL),
(@option_group_id_msg_mode, '{ts escape="sql"}SMS{/ts}', 'SMS', 'SMS', NULL, 0, 0, 2, NULL, 0, 1, 1, NULL, NULL, NULL),
(@option_group_id_msg_mode, '{ts escape="sql"}User Preference{/ts}', 'User_Preference', 'User Preference', NULL, 0, 0, 3, NULL, 0, 1, 1, NULL, NULL, NULL),

-- Reminder Options for Contact Date Fields
(@option_group_id_contactDateMode, '{ts escape="sql"}Actual date only{/ts}', '1', 'Actual date only', NULL, NULL, 0, 1, NULL, 0, 1, 1, NULL, NULL, NULL),
(@option_group_id_contactDateMode, '{ts escape="sql"}Each anniversary{/ts}', '2', 'Each anniversary', NULL, NULL, 0, 2, NULL, 0, 1, 1, NULL, NULL, NULL),

-- WYSIWYG Editor Presets
(@option_group_id_wysiwyg_presets, '{ts escape="sql"}Default{/ts}',   '1', 'default',   NULL, NULL, 1, 1, NULL, 0, 1, 1, NULL, NULL, NULL),
(@option_group_id_wysiwyg_presets, '{ts escape="sql"}CiviMail{/ts}',  '2', 'civimail',  NULL, NULL, 0, 2, NULL, 0, 1, 1, @mailCompId, NULL, NULL),
(@option_group_id_wysiwyg_presets, '{ts escape="sql"}CiviEvent{/ts}', '3', 'civievent', NULL, NULL, 0, 3, NULL, 0, 1, 1, @eventCompId, NULL, NULL),

-- Environment
(@option_group_id_env, '{ts escape="sql"}Production{/ts}', 'Production', 'Production', NULL, NULL, 1, 1, 'Production Environment', 0, 1, 1, NULL, NULL, NULL),
(@option_group_id_env, '{ts escape="sql"}Staging{/ts}', 'Staging', 'Staging', NULL, NULL, 0, 2, 'Staging Environment', 0, 1, 1, NULL, NULL, NULL),
(@option_group_id_env, '{ts escape="sql"}Development{/ts}', 'Development', 'Development', NULL, NULL, 0, 3, 'Development Environment', 0, 1, 1, NULL, NULL, NULL),

-- Relative Date Filters
   (@option_group_id_date_filter, '{ts escape="sql"}Today{/ts}', 'this.day', 'this.day', NULL, NULL, NULL,1, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}This week{/ts}', 'this.week', 'this.week', NULL, NULL, NULL,2, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}This calendar month{/ts}', 'this.month', 'this.month', NULL, NULL, NULL,3, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}This quarter{/ts}', 'this.quarter', 'this.quarter', NULL, NULL, NULL,4, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}This fiscal year{/ts}', 'this.fiscal_year', 'this.fiscal_year', NULL, NULL, NULL,5, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}This calendar year{/ts}', 'this.year', 'this.year', NULL, NULL, NULL,6, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Yesterday{/ts}', 'previous.day', 'previous.day', NULL, NULL, NULL,7, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Previous week{/ts}', 'previous.week', 'previous.week', NULL, NULL, NULL,8, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Previous calendar month{/ts}', 'previous.month', 'previous.month', NULL, NULL, NULL,9, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Previous quarter{/ts}', 'previous.quarter', 'previous.quarter', NULL, NULL, NULL,10, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Previous fiscal year{/ts}', 'previous.fiscal_year', 'previous.fiscal_year', NULL, NULL, NULL,11, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Previous calendar year{/ts}', 'previous.year', 'previous.year', NULL, NULL, NULL,12, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Last 7 days including today{/ts}', 'ending.week', 'ending.week', NULL, NULL, NULL,13, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Last 30 days including today{/ts}', 'ending.month', 'ending.month', NULL, NULL, NULL,14, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Last 60 days including today{/ts}', 'ending_2.month', 'ending_2.month', NULL, NULL, NULL,15, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Last 90 days including today{/ts}', 'ending.quarter', 'ending.quarter', NULL, NULL, NULL,16, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Last 12 months including today{/ts}', 'ending.year', 'ending.year', NULL, NULL, NULL,17, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Last 2 years including today{/ts}', 'ending_2.year', 'ending_2.year', NULL, NULL, NULL,18, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Last 3 years including today{/ts}', 'ending_3.year', 'ending_3.year', NULL, NULL, NULL,19, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Tomorrow{/ts}', 'starting.day', 'starting.day', NULL, NULL, NULL,20, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Next week{/ts}', 'next.week', 'next.week', NULL, NULL, NULL,21, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Next calendar month{/ts}', 'next.month', 'next.month', NULL, NULL, NULL,22, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Next quarter{/ts}', 'next.quarter', 'next.quarter', NULL, NULL, NULL,23, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Next fiscal year{/ts}', 'next.fiscal_year', 'next.fiscal_year', NULL, NULL, NULL,24, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Next calendar year{/ts}', 'next.year', 'next.year', NULL, NULL, NULL,25, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Next 7 days including today{/ts}', 'starting.week', 'starting.week', NULL, NULL, NULL,26, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Next 30 days including today{/ts}', 'starting.month', 'starting.month', NULL, NULL, NULL,27, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Next 60 days including today{/ts}', 'starting_2.month', 'starting_2.month', NULL, NULL, NULL,28, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Next 90 days including today{/ts}', 'starting.quarter', 'starting.quarter', NULL, NULL, NULL,29, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Next 12 months including today{/ts}', 'starting.year', 'starting.year', NULL, NULL, NULL,30, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Current week to-date{/ts}', 'current.week', 'current.week', NULL, NULL, NULL,31, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Current calendar month to-date{/ts}', 'current.month', 'current.month', NULL, NULL, NULL,32, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Current quarter to-date{/ts}', 'current.quarter', 'current.quarter', NULL, NULL, NULL,33, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Current calendar year to-date{/ts}', 'current.year', 'current.year', NULL, NULL, NULL,34, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}To end of yesterday{/ts}', 'earlier.day', 'earlier.day', NULL, NULL, NULL,35, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}To end of previous week{/ts}', 'earlier.week', 'earlier.week', NULL, NULL, NULL,36, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}To end of previous calendar month{/ts}', 'earlier.month', 'earlier.month', NULL, NULL, NULL,37, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}To end of previous quarter{/ts}', 'earlier.quarter', 'earlier.quarter', NULL, NULL, NULL,38, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}To end of previous calendar year{/ts}', 'earlier.year', 'earlier.year', NULL, NULL, NULL,39, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}From start of current day{/ts}', 'greater.day', 'greater.day', NULL, NULL, NULL,40, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}From start of current week{/ts}', 'greater.week', 'greater.week', NULL, NULL, NULL,41, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}From start of current calendar month{/ts}', 'greater.month', 'greater.month', NULL, NULL, NULL,42, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}From start of current quarter{/ts}', 'greater.quarter', 'greater.quarter', NULL, NULL, NULL,43, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}From start of current calendar year{/ts}', 'greater.year', 'greater.year', NULL, NULL, NULL,44, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}To end of current week{/ts}', 'less.week', 'less.week', NULL, NULL, NULL,45, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}To end of current calendar month{/ts}', 'less.month', 'less.month', NULL, NULL, NULL,46, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}To end of current quarter{/ts}', 'less.quarter', 'less.quarter', NULL, NULL, NULL,47, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}To end of current calendar year{/ts}', 'less.year', 'less.year', NULL, NULL, NULL,48, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Previous 2 days{/ts}', 'previous_2.day', 'previous_2.day', NULL, NULL, NULL,49, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Previous 2 weeks{/ts}', 'previous_2.week', 'previous_2.week', NULL, NULL, NULL,50, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Previous 2 calendar months{/ts}', 'previous_2.month', 'previous_2.month', NULL, NULL, NULL,51, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Previous 2 quarters{/ts}', 'previous_2.quarter', 'previous_2.quarter', NULL, NULL, NULL,52, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Previous 2 calendar years{/ts}', 'previous_2.year', 'previous_2.year', NULL, NULL, NULL,53, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Day prior to yesterday{/ts}', 'previous_before.day', 'previous_before.day', NULL, NULL, NULL,54, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Week prior to previous week{/ts}', 'previous_before.week', 'previous_before.week', NULL, NULL, NULL,55, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Month prior to previous calendar month{/ts}', 'previous_before.month', 'previous_before.month', NULL, NULL, NULL,56, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Quarter prior to previous quarter{/ts}', 'previous_before.quarter', 'previous_before.quarter', NULL, NULL, NULL,57, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}Year prior to previous calendar year{/ts}', 'previous_before.year', 'previous_before.year', NULL, NULL, NULL,58, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}From end of previous week{/ts}', 'greater_previous.week', 'greater_previous.week', NULL, NULL, NULL,59, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}From end of previous calendar month{/ts}', 'greater_previous.month', 'greater_previous.month', NULL, NULL, NULL,60, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}From end of previous quarter{/ts}', 'greater_previous.quarter', 'greater_previous.quarter', NULL, NULL, NULL,61, NULL, 0, 0, 1, NULL, NULL, NULL),
   (@option_group_id_date_filter, '{ts escape="sql"}From end of previous calendar year{/ts}', 'greater_previous.year', 'greater_previous.year', NULL, NULL, NULL,62, NULL, 0, 0, 1, NULL, NULL, NULL),

-- Pledge Status
  (@option_group_id_ps, '{ts escape="sql"}Completed{/ts}'  , 1, 'Completed'  , NULL, 0, NULL, 1, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_ps, '{ts escape="sql"}Pending{/ts}'    , 2, 'Pending'    , NULL, 0, NULL, 2, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_ps, '{ts escape="sql"}Cancelled{/ts}'  , 3, 'Cancelled'  , NULL, 0, NULL, 3, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_ps, '{ts escape="sql"}In Progress{/ts}', 5, 'In Progress', NULL, 0, NULL, 4, NULL, 0, 1, 1, NULL, NULL, NULL),
  (@option_group_id_ps, '{ts escape="sql"}Overdue{/ts}'    , 6, 'Overdue'    , NULL, 0, NULL, 5, NULL, 0, 1, 1, NULL, NULL, NULL),

-- CiviCase - Activity Assignee Default
--  (`option_group_id`,             `label`,                                                `value`, `name`,                    `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`, `icon`)
(@option_group_id_default_assignee, '{ts escape="sql"}None{/ts}',                           '1',     'NONE',                    NULL,       0,         1,           1,         NULL,          0,             0,             1,           NULL,            NULL,           NULL),
(@option_group_id_default_assignee, '{ts escape="sql"}By relationship to case client{/ts}', '2',     'BY_RELATIONSHIP',         NULL,       0,         0,           1,         NULL,          0,             0,             1,           NULL,            NULL,           NULL),
(@option_group_id_default_assignee, '{ts escape="sql"}Specific contact{/ts}',               '3',     'SPECIFIC_CONTACT',        NULL,       0,         0,           1,         NULL,          0,             0,             1,           NULL,            NULL,           NULL),
(@option_group_id_default_assignee, '{ts escape="sql"}User creating the case{/ts}',          '4',     'USER_CREATING_THE_CASE',  NULL,       0,         0,           1,         NULL,          0,             0,             1,           NULL,            NULL,           NULL);

-- financial accounts
SELECT @opval := value FROM civicrm_option_value WHERE name = 'Revenue' and option_group_id = @option_group_id_fat;
SELECT @opexp := value FROM civicrm_option_value WHERE name = 'Expenses' and option_group_id = @option_group_id_fat;
SELECT @opAsset := value FROM civicrm_option_value WHERE name = 'Asset' and option_group_id = @option_group_id_fat;
SELECT @opLiability := value FROM civicrm_option_value WHERE name = 'Liability' and option_group_id = @option_group_id_fat;
SELECT @opCost := value FROM civicrm_option_value WHERE name = 'Cost of Sales' and option_group_id = @option_group_id_fat;

INSERT INTO
   `civicrm_financial_account` (`name`, `contact_id`, `financial_account_type_id`, `description`, `accounting_code`, `account_type_code`, `is_reserved`, `is_active`, `is_deductible`, `is_default`)
VALUES
  ( '{ts escape="sql"}Donation{/ts}'            , @contactID, @opval, 'Default account for donations', '4200', 'INC', 0, 1, 1, 1 ),
  ( '{ts escape="sql"}Member Dues{/ts}'          , @contactID, @opval, 'Default account for membership sales', '4400', 'INC', 0, 1, 1, 0 ),
  ( '{ts escape="sql"}Campaign Contribution{/ts}', @contactID, @opval, 'Sample account for recording payments to a campaign', '4100', 'INC', 0, 1, 0, 0 ),
  ( '{ts escape="sql"}Event Fee{/ts}'            , @contactID, @opval, 'Default account for event ticket sales', '4300', 'INC', 0, 1, 0, 0 ),
  ( '{ts escape="sql"}Banking Fees{/ts}'         , @contactID, @opexp, 'Payment processor fees and manually recorded banking fees', '5200', 'EXP', 0, 1, 0, 1 ),
  ( '{ts escape="sql"}Deposit Bank Account{/ts}' , @contactID, @opAsset, 'All manually recorded cash and cheques go to this account', '1100', 'BANK', 0, 1, 0, 1 ),
  ( '{ts escape="sql"}Accounts Receivable{/ts}'  , @contactID, @opAsset, 'Amounts to be received later (eg pay later event revenues)', '1200', 'AR', 0, 1, 0, 0 ),
  ( '{ts escape="sql"}Accounts Payable{/ts}'     , @contactID, @opLiability, 'Amounts to be paid out such as grants and refunds', '2200', 'AP', 0, 1, 0, 1 ),
  ( '{ts escape="sql"}Premiums{/ts}'             , @contactID, @opCost, 'Account to record cost of premiums provided to payors', '5100', 'COGS', 0, 1, 0, 1 ),
  ( '{ts escape="sql"}Premiums inventory{/ts}'   , @contactID, @opAsset, 'Account representing value of premiums inventory', '1375', 'OCASSET', 0, 1, 0, 0 ),
  ( '{ts escape="sql"}Discounts{/ts}'            , @contactID, @opval, 'Contra-revenue account for amounts discounted from sales', '4900', 'INC', 0, 1, 0, 0 ),
  ( '{ts escape="sql"}Payment Processor Account{/ts}', @contactID, @opAsset, 'Account to record payments into a payment processor merchant account', '1150', 'BANK', 0, 1, 0, 0),
  ( '{ts escape="sql"}Deferred Revenue - Event Fee{/ts}', @contactID, @opLiability, 'Event revenue to be recognized in future months when the events occur', '2730', 'OCLIAB', 0, 1, 0, 0),
  ( '{ts escape="sql"}Deferred Revenue - Member Dues{/ts}', @contactID, @opLiability, 'Membership revenue to be recognized in future months', '2740', 'OCLIAB', 0, 1, 0, 0
);

-- Now insert option values which require domainID
--

INSERT INTO
   `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `domain_id`, `visibility_id`)
VALUES
-- from email address.
  (@option_group_id_fma, '"FIXME" <info@EXAMPLE.ORG>', '1', '"FIXME" <info@EXAMPLE.ORG>', NULL, 0, 1, 1, '{ts escape="sql"}Default domain email address and from name.{/ts}', 0, 0, 1, NULL, @domainID, NULL ),

-- grant types
  (@option_group_id_grantTyp, '{ts escape="sql"}Emergency{/ts}'          , 1, 'Emergency'         , NULL, 0, 1,    1, NULL, 0, 0, 1, NULL, @domainID, NULL),
  (@option_group_id_grantTyp, '{ts escape="sql"}Family Support{/ts}'     , 2, 'Family Support'    , NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, @domainID, NULL),
  (@option_group_id_grantTyp, '{ts escape="sql"}General Protection{/ts}' , 3, 'General Protection', NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, @domainID, NULL),
  (@option_group_id_grantTyp, '{ts escape="sql"}Impunity{/ts}'           , 4, 'Impunity'          , NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, @domainID, NULL),

-- Mail Approval Status Preferences
  (@option_group_id_mail_approval_status, '{ts escape="sql"}Approved{/ts}' , 1, 'Approved', NULL, 0, 1, 1, NULL, 0, 1, 1, @mailCompId, @domainID, NULL),
  (@option_group_id_mail_approval_status, '{ts escape="sql"}Rejected{/ts}' , 2, 'Rejected', NULL, 0, 0, 2, NULL, 0, 1, 1, @mailCompId, @domainID, NULL),
  (@option_group_id_mail_approval_status, '{ts escape="sql"}None{/ts}' , 3, 'None', NULL, 0, 0, 3, NULL, 0, 1, 1, @mailCompId, @domainID, NULL),

-- custom group objects
  (@option_group_id_cgeo, '{ts escape="sql"}Survey{/ts}', 'Survey', 'civicrm_survey', NULL, 0, NULL, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
  (@option_group_id_cgeo, '{ts escape="sql"}Cases{/ts}',  'Case', 'civicrm_case',     NULL, 0, NULL, 2, 'CRM_Case_PseudoConstant::caseType;', 0, 0, 1, NULL, NULL, NULL);

-- CRM-6138
{include file='languages.tpl'}

-- /*******************************************************
-- *
-- * Encounter Medium Option Values (for case activities)
-- *
-- *******************************************************/
INSERT INTO `civicrm_option_group` (name, title, description, is_reserved, is_active)
    VALUES  ('encounter_medium', 'Encounter Medium', 'Encounter medium for case activities (e.g. In Person, By Phone, etc.)', 1, 1);
SELECT @option_group_id_medium        := max(id) from civicrm_option_group where name = 'encounter_medium';
INSERT INTO
   `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`)
VALUES
    (@option_group_id_medium, '{ts escape="sql"}In Person{/ts}',  1, 'in_person', NULL, 0,  0, 1, NULL, 0, 1, 1),
    (@option_group_id_medium, '{ts escape="sql"}Phone{/ts}',  2, 'phone', NULL, 0,  1, 2, NULL, 0, 1, 1),
    (@option_group_id_medium, '{ts escape="sql"}Email{/ts}',  3, 'email', NULL, 0,  0, 3, NULL, 0, 1, 1),
    (@option_group_id_medium, '{ts escape="sql"}Fax{/ts}',  4, 'fax', NULL, 0,  0, 4, NULL, 0, 1, 1),
    (@option_group_id_medium, '{ts escape="sql"}Letter Mail{/ts}',  5, 'letter_mail', NULL, 0,  0, 5, NULL, 0, 1, 1);

-- sample membership status entries
INSERT INTO
    civicrm_membership_status(name, label, start_event, start_event_adjust_unit, start_event_adjust_interval, end_event, end_event_adjust_unit, end_event_adjust_interval, is_current_member, is_admin, weight, is_default, is_active, is_reserved)
VALUES
    ('New',       '{ts escape="sql"}New{/ts}', 'join_date', null, null,'join_date','month',3, 1, 0, 1, 0, 1, 0),
    ('Current',   '{ts escape="sql"}Current{/ts}', 'start_date', null, null,'end_date', null, null, 1, 0, 2, 1, 1, 0),
    ('Grace',     '{ts escape="sql"}Grace{/ts}', 'end_date', null, null,'end_date','month', 1, 1, 0, 3, 0, 1, 0),
    ('Expired',   '{ts escape="sql"}Expired{/ts}', 'end_date', 'month', 1, null, null, null, 0, 0, 4, 0, 1, 0),
    ('Pending',   '{ts escape="sql"}Pending{/ts}', 'join_date', null, null,'join_date',null,null, 0, 0, 5, 0, 1, 1),
    ('Cancelled', '{ts escape="sql"}Cancelled{/ts}', 'join_date', null, null,'join_date',null,null, 0, 0, 6, 0, 1, 1),
    ('Deceased',  '{ts escape="sql"}Deceased{/ts}', null, null, null, null, null, null, 0, 1, 7, 0, 1, 1);


INSERT INTO `civicrm_preferences_date`
  (name, start, end, date_format, time_format, description)
VALUES
  ( 'activityDate'    ,  20, 10, '',    '',  '{ts escape="sql"}Date for activities including contributions: receive, receipt, cancel. membership: join, start, renew. case: start, end.{/ts}'         ),
  ( 'activityDateTime',  20, 10, '',     1,  '{ts escape="sql"}Date and time for activity: scheduled. participant: registered.{/ts}'                                                                  ),
  ( 'birth'           , 100,  0, '',    '',  '{ts escape="sql"}Birth and deceased dates. Only year, month and day fields are supported.{/ts}'                                                         ),
  ( 'creditCard'      ,   0, 10, 'M Y', '',  '{ts escape="sql"}Month and year only for credit card expiration.{/ts}'                                                                                  ),
  ( 'custom'          ,  20, 20, '',    '',  '{ts escape="sql"}Uses date range passed in by form field. Can pass in a posix date part parameter. Start and end offsets defined here are ignored.{/ts}'),
  ( 'mailing'         ,   0,  1, '',    '',  '{ts escape="sql"}Date and time. Used for scheduling mailings.{/ts}'                                                                                     ),
  ( 'searchDate'      ,  20, 20, '',    '',  '{ts escape="sql"}Used in search forms and for relationships.{/ts}'                                                                                      );


-- various processor options
--
-- Table structure for table `civicrm_payment_processor_type`
--

INSERT INTO `civicrm_payment_processor_type`
 (name, title, description, is_active, is_default, user_name_label, password_label, signature_label, subject_label, class_name, url_site_default, url_api_default, url_recur_default, url_button_default, url_site_test_default, url_api_test_default, url_recur_test_default, url_button_test_default, billing_mode, is_recur )
VALUES
 ('PayPal_Standard',    '{ts escape="sql"}PayPal - Website Payments Standard{/ts}', NULL,1,0,'{ts escape="sql"}Merchant Account Email{/ts}',NULL,NULL,NULL,'Payment_PayPalImpl','https://www.paypal.com/',NULL,'https://www.paypal.com/',NULL,'https://www.sandbox.paypal.com/',NULL,'https://www.sandbox.paypal.com/',NULL,4,1),
 ('PayPal',             '{ts escape="sql"}PayPal - Website Payments Pro{/ts}',      NULL,1,0,'{ts escape="sql"}User Name{/ts}','{ts escape="sql"}Password{/ts}','{ts escape="sql"}Signature{/ts}',NULL,'Payment_PayPalImpl','https://www.paypal.com/','https://api-3t.paypal.com/','https://www.paypal.com/','https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif','https://www.sandbox.paypal.com/','https://api-3t.sandbox.paypal.com/','https://www.sandbox.paypal.com/','https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif',3, 1),
 ('PayPal_Express',     '{ts escape="sql"}PayPal - Express{/ts}',       NULL,1,0,'{ts escape="sql"}User Name{/ts}','{ts escape="sql"}Password{/ts}','{ts escape="sql"}Signature{/ts}',NULL,'Payment_PayPalImpl','https://www.paypal.com/','https://api-3t.paypal.com/',NULL,'https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif','https://www.sandbox.paypal.com/','https://api-3t.sandbox.paypal.com/',NULL,'https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif',2, 1),
 ('AuthNet',            '{ts escape="sql"}Authorize.Net{/ts}',          NULL,1,0,'{ts escape="sql"}API Login{/ts}','{ts escape="sql"}Payment Key{/ts}','{ts escape="sql"}MD5 Hash{/ts}',NULL,'Payment_AuthorizeNet','https://secure2.authorize.net/gateway/transact.dll',NULL,'https://api2.authorize.net/xml/v1/request.api',NULL,'https://test.authorize.net/gateway/transact.dll',NULL,'https://apitest.authorize.net/xml/v1/request.api',NULL,1,1),
 ('PayJunction',        '{ts escape="sql"}PayJunction{/ts}',            NULL,1,0,'User Name','Password',NULL,NULL,'Payment_PayJunction','https://payjunction.com/quick_link',NULL,NULL,NULL,'https://www.payjunctionlabs.com/quick_link',NULL,NULL,NULL,1,1),
 ('eWAY',               '{ts escape="sql"}eWAY (Single Currency){/ts}', NULL,1,0,'Customer ID',NULL,NULL,NULL,'Payment_eWAY','https://www.eway.com.au/gateway_cvn/xmlpayment.asp',NULL,NULL,NULL,'https://www.eway.com.au/gateway_cvn/xmltest/testpage.asp',NULL,NULL,NULL,1,0),
 ('Payment_Express',    '{ts escape="sql"}DPS Payment Express{/ts}',    NULL,1,0,'User ID','Key','Mac Key - pxaccess only',NULL,'Payment_PaymentExpress','https://www.paymentexpress.com/pleaseenteraurl',NULL,NULL,NULL,'https://www.paymentexpress.com/pleaseenteratesturl',NULL,NULL,NULL,4,0),
 ('Dummy',              '{ts escape="sql"}Dummy Payment Processor{/ts}',NULL,1,1,'{ts escape="sql"}User Name{/ts}',NULL,NULL,NULL,'Payment_Dummy',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1),
 ('Elavon',             '{ts escape="sql"}Elavon Payment Processor{/ts}','{ts escape="sql"}Elavon / Nova Virtual Merchant{/ts}',1,0,'{ts escape="sql"}SSL Merchant ID {/ts}','{ts escape="sql"}SSL User ID{/ts}','{ts escape="sql"}SSL PIN{/ts}',NULL,'Payment_Elavon','https://www.myvirtualmerchant.com/VirtualMerchant/processxml.do',NULL,NULL,NULL,'https://www.myvirtualmerchant.com/VirtualMerchant/processxml.do',NULL,NULL,NULL,1,0),
 ('Realex',             '{ts escape="sql"}Realex Payment{/ts}',         NULL,1,0,'Merchant ID', 'Password', NULL, 'Account', 'Payment_Realex', 'https://epage.payandshop.com/epage.cgi', NULL, NULL, NULL, 'https://epage.payandshop.com/epage-remote.cgi', NULL, NULL, NULL, 1, 0),
 ('PayflowPro',         '{ts escape="sql"}PayflowPro{/ts}',             NULL,1,0,'Vendor ID', 'Password', 'Partner (merchant)', 'User', 'Payment_PayflowPro', 'https://Payflowpro.paypal.com', NULL, NULL, NULL, 'https://pilot-Payflowpro.paypal.com', NULL, NULL, NULL, 1, 0),
 ('FirstData',          '{ts escape="sql"}FirstData (aka linkpoint){/ts}', '{ts escape="sql"}FirstData (aka linkpoint){/ts}', 1, 0, 'Store name', 'certificate path', NULL, NULL, 'Payment_FirstData', 'https://secure.linkpt.net', NULL, NULL, NULL, 'https://staging.linkpt.net', NULL, NULL, NULL, 1, NULL);


-- the fuzzy default dedupe rules
-- IndividualSupervised uses hard-coded optimized query (CRM_Dedupe_BAO_QueryBuilder_IndividualSupervised)
INSERT INTO civicrm_dedupe_rule_group (contact_type, threshold, used, name, title, is_reserved)
VALUES ('Individual', 20, 'Supervised', 'IndividualSupervised', '{ts escape="sql"}Name and Email (reserved){/ts}', 1);

SELECT @drgid := MAX(id) FROM civicrm_dedupe_rule_group;
INSERT INTO civicrm_dedupe_rule (dedupe_rule_group_id, rule_table, rule_field, rule_weight)
VALUES (@drgid, 'civicrm_contact', 'first_name', 5),
       (@drgid, 'civicrm_contact', 'last_name',  7),
       (@drgid, 'civicrm_email'  , 'email',     10);

INSERT INTO civicrm_dedupe_rule_group (contact_type, threshold, used, name, title, is_reserved)
VALUES ('Organization', 10, 'Supervised', 'OrganizationSupervised', '{ts escape="sql"}Name and Email{/ts}', 0);

SELECT @drgid := MAX(id) FROM civicrm_dedupe_rule_group;
INSERT INTO civicrm_dedupe_rule (dedupe_rule_group_id, rule_table, rule_field, rule_weight)
VALUES (@drgid, 'civicrm_contact', 'organization_name', 10),
       (@drgid, 'civicrm_email'  , 'email',             10);

INSERT INTO civicrm_dedupe_rule_group (contact_type, threshold, used, name, title, is_reserved)
VALUES ('Household', 10, 'Supervised', 'HouseholdSupervised', '{ts escape="sql"}Name and Email{/ts}', 0);

SELECT @drgid := MAX(id) FROM civicrm_dedupe_rule_group;
INSERT INTO civicrm_dedupe_rule (dedupe_rule_group_id, rule_table, rule_field, rule_weight)
VALUES (@drgid, 'civicrm_contact', 'household_name', 10),
       (@drgid, 'civicrm_email'  , 'email',          10);

-- the strict dedupe rules
-- IndividualUnsupervised uses hard-coded optimized query (CRM_Dedupe_BAO_QueryBuilder_IndividualUnsupervised)
INSERT INTO civicrm_dedupe_rule_group (contact_type, threshold, used, name, title, is_reserved)
VALUES ('Individual', 10, 'Unsupervised', 'IndividualUnsupervised', '{ts escape="sql"}Email (reserved){/ts}', 1);

SELECT @drgid := MAX(id) FROM civicrm_dedupe_rule_group;
INSERT INTO civicrm_dedupe_rule (dedupe_rule_group_id, rule_table, rule_field, rule_weight)
VALUES (@drgid, 'civicrm_email', 'email', 10);

INSERT INTO civicrm_dedupe_rule_group (contact_type, threshold, used, name, title, is_reserved)
VALUES ('Organization', 10,  'Unsupervised', 'OrganizationUnsupervised', '{ts escape="sql"}Name and Email{/ts}', 0);

SELECT @drgid := MAX(id) FROM civicrm_dedupe_rule_group;
INSERT INTO civicrm_dedupe_rule (dedupe_rule_group_id, rule_table, rule_field, rule_weight)
VALUES (@drgid, 'civicrm_contact', 'organization_name', 10),
       (@drgid, 'civicrm_email'  , 'email',             10);

INSERT INTO civicrm_dedupe_rule_group (contact_type, threshold, used, name, title, is_reserved)
VALUES ('Household', 10, 'Unsupervised', 'HouseholdUnsupervised', '{ts escape="sql"}Name and Email{/ts}', 0);

SELECT @drgid := MAX(id) FROM civicrm_dedupe_rule_group;
INSERT INTO civicrm_dedupe_rule (dedupe_rule_group_id, rule_table, rule_field, rule_weight)
VALUES (@drgid, 'civicrm_contact', 'household_name', 10),
       (@drgid, 'civicrm_email'  , 'email',          10);

-- IndividualGeneral uses hard-coded optimized query (CRM_Dedupe_BAO_QueryBuilder_IndividualGeneral)
INSERT INTO civicrm_dedupe_rule_group (contact_type, threshold, used, name, title, is_reserved)
VALUES ('Individual', 15, 'General', 'IndividualGeneral', '{ts escape="sql"}Name and Address (reserved){/ts}', 1);

SELECT @drgid := MAX(id) FROM civicrm_dedupe_rule_group;
INSERT INTO civicrm_dedupe_rule (dedupe_rule_group_id, rule_table, rule_field, rule_weight)
VALUES (@drgid, 'civicrm_contact', 'first_name',     '5'),
       (@drgid, 'civicrm_contact', 'last_name',      '5'),
       (@drgid, 'civicrm_address', 'street_address', '5'),
       (@drgid, 'civicrm_contact', 'middle_name',    '1'),
       (@drgid, 'civicrm_contact', 'suffix_id',      '1');

-- Sample counties (state-province and country lists defined in a separate tpl files)
INSERT INTO civicrm_county (name, state_province_id) VALUES ('Alameda', 1004);
INSERT INTO civicrm_county (name, state_province_id) VALUES ('Contra Costa', 1004);
INSERT INTO civicrm_county (name, state_province_id) VALUES ('Marin', 1004);
INSERT INTO civicrm_county (name, state_province_id) VALUES ('San Francisco', 1004);
INSERT INTO civicrm_county (name, state_province_id) VALUES ('San Mateo', 1004);
INSERT INTO civicrm_county (name, state_province_id) VALUES ('Santa Clara', 1004);

-- Bounce classification patterns
INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('AOL', '{ts escape="sql"}AOL Terms of Service complaint{/ts}', 1);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'AOL';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, 'Client TOS Notification');

INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('Away', '{ts escape="sql"}Recipient is on vacation{/ts}', 30);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Away';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, '(be|am)? (out of|away from) (the|my)? (office|computer|town)'),
    (@bounceTypeID, 'i am on vacation');

INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('Dns', '{ts escape="sql"}Unable to resolve recipient domain{/ts}', 3);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Dns';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, 'name(server entry| lookup failure)'),
    (@bounceTypeID, 'no (mail server|matches to nameserver query|dns entries)'),
    (@bounceTypeID, 'reverse dns entry'),
    (@bounceTypeID, 'Host or domain name not found'),
    (@bounceTypeID, 'Unable to resolve MX record for');

INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('Host', '{ts escape="sql"}Unable to deliver to destintation mail server{/ts}', 3);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Host';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, '(unknown|not local) host'),
    (@bounceTypeID, 'all hosts have been failing'),
    (@bounceTypeID, 'allowed rcpthosts'),
    (@bounceTypeID, 'connection (refused|timed out)'),
    (@bounceTypeID, 'not connected'),
    (@bounceTypeID, 'couldn\'t find any host named'),
    (@bounceTypeID, 'error involving remote host'),
    (@bounceTypeID, 'host unknown'),
    (@bounceTypeID, 'invalid host name'),
    (@bounceTypeID, 'isn\'t in my control/locals file'),
    (@bounceTypeID, 'local configuration error'),
    (@bounceTypeID, 'not a gateway'),
    (@bounceTypeID, 'server is (down or unreachable|not responding)'),
    (@bounceTypeID, 'too many connections'),
    (@bounceTypeID, 'unable to connect'),
    (@bounceTypeID, 'lost connection'),
    (@bounceTypeID, 'conversation with [^ ]* timed out while'),
    (@bounceTypeID, 'server requires authentication'),
    (@bounceTypeID, 'authentication (is )?required');

INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('Inactive', '{ts escape="sql"}User account is no longer active{/ts}', 1);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Inactive';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, '(my )?e-?mail( address)? has changed'),
    (@bounceTypeID, 'account (inactive|expired|deactivated)'),
    (@bounceTypeID, 'account is locked'),
    (@bounceTypeID, 'changed \w+( e-?mail)? address'),
    (@bounceTypeID, 'deactivated mailbox'),
    (@bounceTypeID, 'disabled or discontinued'),
    (@bounceTypeID, 'inactive user'),
    (@bounceTypeID, 'is inactive on this domain'),
    (@bounceTypeID, 'mail receiving disabled'),
    (@bounceTypeID, 'mail( ?)address is administrative?ly disabled'),
    (@bounceTypeID, 'mailbox (temporarily disabled|currently suspended)'),
    (@bounceTypeID, 'no longer (accepting mail|on server|in use|with|employed|on staff|works for|using this account)'),
    (@bounceTypeID, 'not accepting (mail|messages)'),
    (@bounceTypeID, 'please use my new e-?mail address'),
    (@bounceTypeID, 'this address no longer accepts mail'),
    (@bounceTypeID, 'user account suspended'),
    (@bounceTypeID, 'account that you tried to reach is disabled'),
    (@bounceTypeID, 'User banned');

INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('Invalid', '{ts escape="sql"}Email address is not valid{/ts}', 1);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Invalid';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, '(user|recipient( name)?) is not recognized'),
    (@bounceTypeID, '554 delivery error'),
    (@bounceTypeID, 'address does not exist'),
    (@bounceTypeID, 'address(es)?( you (entered|specified))? (could|was)( not|n.t)( be)? found'),
    (@bounceTypeID, 'address(ee)? (unknown|invalid)'),
    (@bounceTypeID, 'bad destination'),
    (@bounceTypeID, 'badly formatted address'),
    (@bounceTypeID, 'can\'t open mailbox for'),
    (@bounceTypeID, 'cannot deliver'),
    (@bounceTypeID, 'delivery to the following recipient(s)? failed'),
    (@bounceTypeID, 'destination addresses were unknown'),
    (@bounceTypeID, 'did not reach the following recipient'),
    (@bounceTypeID, 'does not exist'),
    (@bounceTypeID, 'does not like recipient'),
    (@bounceTypeID, 'does not specify a valid notes mail file'),
    (@bounceTypeID, 'illegal alias'),
    (@bounceTypeID, 'invalid (mailbox|(e-?mail )?address|recipient|final delivery)'),
    (@bounceTypeID, 'invalid( or unknown)?( virtual)? user'),
    (@bounceTypeID, '(mail )?delivery (to this user )?is not allowed'),
    (@bounceTypeID, 'mailbox (not found|unavailable|name not allowed)'),
    (@bounceTypeID, 'message could not be forwarded'),
    (@bounceTypeID, 'missing or malformed local(-| )part'),
    (@bounceTypeID, 'no e-?mail address registered'),
    (@bounceTypeID, 'no such (mail drop|mailbox( \\w+)?|(e-?mail )?address|recipient|(local )?user|person)( here)?'),
    (@bounceTypeID, 'no mailbox (here )?by that name'),
    (@bounceTypeID, 'not (listed in|found in directory|known at this site|our customer)'),
    (@bounceTypeID, 'not a valid( (user|mailbox))?'),
    (@bounceTypeID, 'not present in directory entry'),
    (@bounceTypeID, 'recipient (does not exist|(is )?unknown|rejected|denied|not found)'),
    (@bounceTypeID, 'this user doesn\'t have a yahoo.com address'),
    (@bounceTypeID, 'unavailable to take delivery of the message'),
    (@bounceTypeID, 'unavailable mailbox'),
    (@bounceTypeID, 'unknown (local( |-)part|recipient|address error)'),
    (@bounceTypeID, 'unknown( or illegal)? user( account)?'),
    (@bounceTypeID, 'unrecognized recipient'),
    (@bounceTypeID, 'unregistered address'),
    (@bounceTypeID, 'user (unknown|does not exist)'),
    (@bounceTypeID, 'user doesn\'t have an? \w+ account'),
    (@bounceTypeID, 'user(\'s e-?mail name is)? not found'),
    (@bounceTypeID, '^Validation failed for:'),
    (@bounceTypeID, '5.1.0 Address rejected'),
    (@bounceTypeID, 'no valid recipients?'),
    (@bounceTypeID, 'RecipNotFound'),
    (@bounceTypeID, 'no one at this address'),
    (@bounceTypeID, 'misconfigured forwarding address'),
    (@bounceTypeID, 'account is not allowed'),
    (@bounceTypeID, 'Address .<[^>]*>. not known here'),
    (@bounceTypeID, '{literal}Recipient address rejected: ([a-zA-Z0-9-]+\\.)+[a-zA-Z]{2,}{/literal}'),
    (@bounceTypeID, 'Non sono riuscito a trovare l.indirizzo e-mail'),
    (@bounceTypeID, 'nadie con esta direcci..?n'),
    (@bounceTypeID, 'ni bilo mogo..?e najti prejemnikovega e-po..?tnega naslova'),
    (@bounceTypeID, 'Elektronski naslov (je ukinjen|ne obstaja)'),
    (@bounceTypeID, 'nepravilno nastavljen predal');

INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('Loop', '{ts escape="sql"}Mail routing error{/ts}', 3);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Loop';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, '(mail( forwarding)?|routing).loop'),
    (@bounceTypeID, 'excessive recursion'),
    (@bounceTypeID, 'loop detected'),
    (@bounceTypeID, 'maximum hop count exceeded'),
    (@bounceTypeID, 'message was forwarded more than the maximum allowed times'),
    (@bounceTypeID, 'too many (hops|recursive forwards)');

INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('Quota', '{ts escape="sql"}User inbox is full{/ts}', 3);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Quota';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, '(disk(space)?|over the allowed|exceed(ed|s)?|storage) quota'),
    (@bounceTypeID, '522_mailbox_full'),
    (@bounceTypeID, 'exceeds allowed message count'),
    (@bounceTypeID, 'file too large'),
    (@bounceTypeID, 'full mailbox'),
    (@bounceTypeID, '(mail|in)(box|folder) ((for user \\w+ )?is )?full'),
    (@bounceTypeID, 'mailbox (has exceeded|is over) the limit'),
    (@bounceTypeID, 'mailbox( exceeds allowed)? size'),
    (@bounceTypeID, 'no space left for this user'),
    (@bounceTypeID, 'over\\s?quota'),
    (@bounceTypeID, 'quota (for the mailbox )?has been exceeded'),
    (@bounceTypeID, 'quota ?(usage|violation|exceeded)'),
    (@bounceTypeID, 'recipient storage full'),
    (@bounceTypeID, 'not able to receive more mail'),
    (@bounceTypeID, 'doesn.t have enough disk space left'),
    (@bounceTypeID, 'exceeded storage allocation'),
    (@bounceTypeID, 'running out of disk space');

INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('Relay', '{ts escape="sql"}Unable to reach destination mail server{/ts}', 3);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Relay';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, 'cannot find your hostname'),
    (@bounceTypeID, 'ip name lookup'),
    (@bounceTypeID, 'not configured to relay mail'),
    (@bounceTypeID, 'relay(ing)? (not permitted|(access )?denied)'),
    (@bounceTypeID, 'relayed mail to .+? not allowed'),
    (@bounceTypeID, 'sender ip must resolve'),
    (@bounceTypeID, 'unable to relay'),
    (@bounceTypeID, 'No route to host'),
    (@bounceTypeID, 'Network is unreachable'),
    (@bounceTypeID, 'unrouteable address'),
    (@bounceTypeID, 'We don.t handle mail for'),
    (@bounceTypeID, 'we do not relay'),
    (@bounceTypeID, 'Rejected by next-hop'),
    (@bounceTypeID, 'not permitted to( *550)? relay through this server');

INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('Spam', '{ts escape="sql"}Message caught by a content filter{/ts}', 1);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Spam';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, '(bulk( e-?mail)|content|attachment blocking|virus|mail system) filters?'),
    (@bounceTypeID, '(hostile|questionable|unacceptable) content'),
    (@bounceTypeID, 'address .+? has not been verified'),
    (@bounceTypeID, 'anti-?spam (polic\w+|software)'),
    (@bounceTypeID, 'anti-?virus gateway has detected'),
    (@bounceTypeID, 'blacklisted'),
    (@bounceTypeID, 'blocked message'),
    (@bounceTypeID, 'content control'),
    (@bounceTypeID, 'delivery not authorized'),
    (@bounceTypeID, 'does not conform to our e-?mail policy'),
    (@bounceTypeID, 'excessive spam content'),
    (@bounceTypeID, 'message looks suspicious'),
    (@bounceTypeID, 'open relay'),
    (@bounceTypeID, 'sender was rejected'),
    (@bounceTypeID, 'spam(check| reduction software| filters?)'),
    (@bounceTypeID, 'blocked by a user configured filter'),
    (@bounceTypeID, '(detected|rejected) (as|due to) spam'),
    (@bounceTypeID, 'X-HmXmrOriginalRecipient'),
    (@bounceTypeID, 'Client host .[^ ]*. blocked'),
    (@bounceTypeID, 'automatic(ally-generated)? messages are not accepted'),
    (@bounceTypeID, 'denied by policy'),
    (@bounceTypeID, 'has no corresponding reverse \\(PTR\\) address'),
    (@bounceTypeID, 'has a policy that( [^ ]*)? prohibited the mail that you sent'),
    (@bounceTypeID, 'is likely unsolicited mail'),
    (@bounceTypeID, 'Local Policy Violation'),
    (@bounceTypeID, 'ni bilo mogo..?e dostaviti zaradi varnostnega pravilnika'),
    (@bounceTypeID, 'abuse report');

INSERT INTO civicrm_mailing_bounce_type
        (name, description, hold_threshold)
        VALUES ('Syntax', '{ts escape="sql"}Error in SMTP transaction{/ts}', 3);

SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Syntax';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, 'nonstandard smtp line terminator'),
    (@bounceTypeID, 'syntax error in from address'),
    (@bounceTypeID, 'unknown smtp code');

-- add sample and reserved profiles

INSERT INTO civicrm_uf_group
    (id, name,                 group_type,           title,                                      is_cms_user, is_reserved, help_post) VALUES
    (1,  'name_and_address',   'Individual,Contact',  '{ts escape="sql"}Name and Address{/ts}',   0,           0,           NULL),
    (2,  'supporter_profile',  'Individual,Contact',  '{ts escape="sql"}Supporter Profile{/ts}',  2,           0,           '<p><strong>{ts escape="sql"}The information you provide will NOT be shared with any third party organisations.{/ts}</strong></p><p>{ts escape="sql"}Thank you for getting involved in our campaign!{/ts}</p>'),
    (3,  'participant_status', 'Participant',         '{ts escape="sql"}Participant Status{/ts}',             0,      1,           NULL),
    (4,  'new_individual',     'Individual,Contact',  '{ts escape="sql"}New Individual{/ts}'    ,             0,      1,           NULL),
    (5,  'new_organization',   'Organization,Contact','{ts escape="sql"}New Organization{/ts}'  ,             0,      1,           NULL),
    (6,  'new_household',      'Household,Contact',   '{ts escape="sql"}New Household{/ts}'     ,             0,      1,           NULL),
    (7,  'summary_overlay',    'Contact',             '{ts escape="sql"}Summary Overlay{/ts}'   ,             0,      1,           NULL),
    (8,  'shared_address',     'Contact',             '{ts escape="sql"}Shared Address{/ts}'                , 0,      1,           NULL),
    (9,  'on_behalf_organization', 'Contact,Organization','{ts escape="sql"}On Behalf Of Organization{/ts}',  0,      1,           NULL),
    (10, 'contribution_batch_entry', 'Contribution', '{ts escape="sql"}Contribution Bulk Entry{/ts}' ,       0,      1,           NULL),
    (11, 'membership_batch_entry', 'Membership',     '{ts escape="sql"}Membership Bulk Entry{/ts}' ,         0,      1,           NULL),
    (12, 'event_registration', 'Individual,Contact', '{ts escape="sql"}Your Registration Info{/ts}',         0,      0,           NULL),
    (13, 'honoree_individual', 'Individual,Contact', '{ts escape="sql"}Honoree Individual{/ts}',             0,      1,           NULL);


INSERT INTO civicrm_uf_join
   (is_active,module,entity_table,entity_id,weight,uf_group_id)
VALUES
   (1, 'User Registration',NULL, NULL,1,1),
   (1, 'User Account', NULL, NULL, 1, 1),
   (1, 'Profile', NULL, NULL, 1, 1),
   (1, 'Profile', NULL, NULL, 2, 2),
   (1, 'Profile', NULL, NULL, 11, 12);

INSERT INTO civicrm_uf_field
       ( uf_group_id, field_name,              is_required, is_reserved, weight, visibility,                  in_selector, is_searchable, location_type_id, label,                                          field_type,    help_post, phone_type_id ) VALUES
       (  1,           'first_name',            1,           0,           1,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}First Name{/ts}',               'Individual',   NULL,  NULL),
       (  1,           'last_name',             1,           0,           2,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Last Name{/ts}',                'Individual',   NULL,  NULL),
       (  1,           'street_address',        0,           0,           3,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}Street Address (Home){/ts}',    'Contact',      NULL,  NULL),
       (  1,           'city',                  0,           0,           4,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}City (Home){/ts}',              'Contact',      NULL,  NULL),
       (  1,           'postal_code',           0,           0,           5,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}Postal Code (Home){/ts}',       'Contact',      NULL,  NULL),
       (  1,           'country',               0,           0,           6,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}Country (Home){/ts}',           'Contact',      NULL,  NULL),
       (  1,           'state_province',        0,           0,           7,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}State (Home){/ts}',             'Contact',      NULL,  NULL),
       (  2,           'first_name',            1,           0,           1,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}First Name{/ts}',               'Individual',   NULL,  NULL),
       (  2,           'last_name',             1,           0,           2,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Last Name{/ts}',                'Individual',   NULL,  NULL),
       (  2,           'email',                 1,           0,           3,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Email Address{/ts}',            'Contact',      NULL,  NULL),
       (  3,           'participant_status',    1,           1,           1,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Participant Status{/ts}',       'Participant',  NULL,  NULL),
       (  4,           'first_name',            1,           0,           1,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}First Name{/ts}',               'Individual',   NULL,  NULL),
       (  4,           'last_name',             1,           0,           2,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Last Name{/ts}',                'Individual',   NULL,  NULL),
       (  4,           'email',                 0,           0,           3,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Email Address{/ts}',            'Contact',      NULL,  NULL),
       (  5,           'organization_name',     1,           0,           2,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Organization Name{/ts}',        'Organization', NULL,  NULL),
       (  5,           'email',                 0,           0,           3,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Email Address{/ts}',            'Contact',      NULL,  NULL),
       (  6,           'household_name',        1,           0,           2,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Household Name{/ts}',           'Household',    NULL,  NULL),
       (  6,           'email',                 0,           0,           3,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Email Address{/ts}',            'Contact',      NULL,  NULL),
       (  7,           'phone',                 1,           0,           1,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}Home Phone{/ts}',               'Contact',      NULL,  1 ),
       (  7,           'phone',                 1,           0,           2,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}Home Mobile{/ts}',              'Contact',      NULL,  2 ),
       (  7,           'street_address',        1,           0,           3,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Primary Address{/ts}',          'Contact',      NULL,  NULL),
       (  7,           'city',                  1,           0,           4,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}City{/ts}',                     'Contact',      NULL,  NULL),
       (  7,           'state_province',        1,           0,           5,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}State{/ts}',                    'Contact',      NULL,  NULL),
       (  7,           'postal_code',           1,           0,           6,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Postal Code{/ts}',              'Contact',      NULL,  NULL),
       (  7,           'email',                 1,           0,           7,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Primary Email{/ts}',            'Contact',      NULL,  NULL),
       (  7,           'group',                 1,           0,           8,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Groups{/ts}',                   'Contact',      NULL,  NULL),
       (  7,           'tag',                   1,           0,           9,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Tags{/ts}',                     'Contact',      NULL,  NULL),
       (  7,           'gender_id',             1,           0,           10,     'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Gender{/ts}',                   'Individual',   NULL,  NULL),
       (  7,           'birth_date',            1,           0,           11,     'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Date of Birth{/ts}',            'Individual',   NULL,  NULL),
       (  8,           'street_address',        1,           1,           1,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}Street Address (Home){/ts}',    'Contact',      NULL,  NULL),
       (  8,           'city',                  1,           1,           2,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}City (Home){/ts}',              'Contact',      NULL,  NULL),
       (  8,           'postal_code',           0,           0,           3,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}Postal Code (Home){/ts}',       'Contact',      NULL,  NULL),
       (  8,           'country',               0,           0,           4,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}Country (Home){/ts}',           'Contact',      NULL,  NULL),
       (  8,           'state_province',        0,           0,           5,      'User and User Admin Only',  0,           0,             1,             '{ts escape="sql"}State (Home){/ts}',             'Contact',      NULL,  NULL),
       (  9,           'organization_name',     1,           0,           1,      'User and User Admin Only',  0,           0,             NULL,          '{ts escape="sql"}Organization Name{/ts}',        'Organization', NULL,  NULL),
       (  9,           'phone',                 1,           0,           2,      'User and User Admin Only',  0,           0,             3,             '{ts escape="sql"}Phone (Main) {/ts}',            'Contact',      NULL,   1),
       (  9,           'email',                 1,           0,           3,      'User and User Admin Only',  0,           0,             3,             '{ts escape="sql"}Email (Main) {/ts}',            'Contact',      NULL,   NULL),
       (  9,           'street_address',        1,           0,           4,      'User and User Admin Only',  0,           0,             3,             '{ts escape="sql"}Street Address{/ts}',           'Contact',      NULL,   NULL),
       (  9,           'city',                  1,           0,           5,      'User and User Admin Only',  0,           0,             3,             '{ts escape="sql"}City{/ts}',                     'Contact',      NULL,   NULL),
       (  9,           'postal_code',           1,           0,           6,      'User and User Admin Only',  0,           0,             3,             '{ts escape="sql"}Postal Code{/ts}',              'Contact',      NULL,   NULL),
       (  9,           'country',               1,           0,           7,      'User and User Admin Only',  0,           0,             3,             '{ts escape="sql"}Country{/ts}',                  'Contact',      NULL,   NULL),
       (  9,           'state_province',        1,           0,           8,      'User and User Admin Only',  0,           0,             3,             '{ts escape="sql"}State/Province{/ts}',         'Contact',      NULL,   NULL),
       ( 10,     'financial_type',              0, 1, 1, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Financial Type{/ts}', 'Contribution', NULL, NULL ),
       ( 10,     'total_amount',                0, 1, 2, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Amount{/ts}', 'Contribution', NULL, NULL ),
       ( 10,     'contribution_status_id',      1, 1, 3, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Status{/ts}', 'Contribution', NULL, NULL ),
       ( 10,     'receive_date',                1, 1, 4, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Received{/ts}', 'Contribution', NULL, NULL ),
       ( 10,     'contribution_source',         0, 0, 5, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Source{/ts}', 'Contribution', NULL, NULL ),
       ( 10,     'payment_instrument',          0, 0, 6, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Payment Method{/ts}', 'Contribution', NULL, NULL ),
       ( 10,     'contribution_check_number',                0, 0, 7, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Check Number{/ts}', 'Contribution', NULL, NULL ),
       ( 10,     'send_receipt',                0, 0, 8, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Send Receipt{/ts}', 'Contribution', NULL, NULL ),
       ( 10,     'invoice_id',                  0, 0, 9, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Invoice ID{/ts}', 'Contribution', NULL, NULL ),
       ( 10,     'soft_credit',                 0, 0, 10, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Soft Credit{/ts}', 'Contribution', NULL, NULL ),
       ( 10,     'soft_credit_type',            0, 0, 11, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Soft Credit Type{/ts}', 'Contribution', NULL, NULL ),
       ( 11,     'membership_type',             1, 1, 1, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Membership Type{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'join_date',                   1, 1, 2, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Member Since{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'membership_start_date',       0, 1, 3, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Start Date{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'membership_end_date',         0, 1, 4, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}End Date{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'membership_source',           0, 0, 5, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Source{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'send_receipt',                0, 0, 6, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Send Receipt{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'financial_type',              0, 1, 7, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Financial Type{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'total_amount',                0, 1, 8, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Amount{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'receive_date',                1, 1, 9, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Received{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'payment_instrument',          0, 0, 10, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Payment Method{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'contribution_check_number',                0, 0, 11, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Check Number{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'contribution_status_id',      1, 1, 12, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Payment Status{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'soft_credit',                 0, 0, 13, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Soft Credit{/ts}', 'Membership', NULL, NULL ),
       ( 11,     'soft_credit_type',            0, 0, 14, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Soft Credit Type{/ts}', 'Membership', NULL, NULL ),
       ( 12,     'first_name',                  1, 0,  1, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}First Name{/ts}',  'Individual', NULL, NULL),
       ( 12,     'last_name',                   1, 0,  2, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Last Name{/ts}',   'Individual',  NULL,  NULL),
       ( 12,     'email',                       1, 0,  3, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Email Address{/ts}', 'Contact', NULL, NULL),
       ( 13,     'prefix_id',                   0, 1,  1, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Individual Prefix{/ts}', 'Individual', NULL, NULL),
       ( 13,     'first_name',                  1, 1,  2, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}First Name{/ts}',        'Individual', NULL, NULL),
       ( 13,     'last_name',                   1, 1,  3, 'User and User Admin Only', 0, 0, NULL, '{ts escape="sql"}Last Name{/ts}',         'Individual', NULL, NULL),
       ( 13,     'email',                       0, 1,  4, 'User and User Admin Only', 0, 0, 1,    '{ts escape="sql"}Email Address{/ts}',     'Contact', NULL, NULL);


INSERT INTO civicrm_participant_status_type
  (id, name,                                  label,                                                       class,      is_reserved, is_active, is_counted, weight, visibility_id) VALUES
  (1,  'Registered',                          '{ts escape="sql"}Registered{/ts}',                          'Positive', 1,           1,         1,          1,      1            ),
  (2,  'Attended',                            '{ts escape="sql"}Attended{/ts}',                            'Positive', 0,           1,         1,          2,      2            ),
  (3,  'No-show',                             '{ts escape="sql"}No-show{/ts}',                             'Negative', 0,           1,         0,          3,      2            ),
  (4,  'Cancelled',                           '{ts escape="sql"}Cancelled{/ts}',                           'Negative', 1,           1,         0,          4,      2            ),
  (5,  'Pending from pay later',              '{ts escape="sql"}Pending (pay later){/ts}',                 'Pending',  1,           1,         1,          5,      2            ),
  (6,  'Pending from incomplete transaction', '{ts escape="sql"}Pending (incomplete transaction){/ts}',    'Pending',  1,           1,         0,          6,      2            ),
  (7,  'On waitlist',                         '{ts escape="sql"}On waitlist{/ts}',                         'Waiting',  1,           0,         0,          7,      2            ),
  (8,  'Awaiting approval',                   '{ts escape="sql"}Awaiting approval{/ts}',                   'Waiting',  1,           0,         1,          8,      2            ),
  (9,  'Pending from waitlist',               '{ts escape="sql"}Pending from waitlist{/ts}',               'Pending',  1,           0,         1,          9,      2            ),
  (10, 'Pending from approval',               '{ts escape="sql"}Pending from approval{/ts}',               'Pending',  1,           0,         1,          10,     2            ),
  (11, 'Rejected',                            '{ts escape="sql"}Rejected{/ts}',                            'Negative', 1,           0,         0,          11,     2            ),
  (12, 'Expired',                             '{ts escape="sql"}Expired{/ts}',                             'Negative', 1,           1,         0,          12,     2            ),
  (13, 'Pending in cart',                     '{ts escape="sql"}Pending in cart{/ts}',                     'Pending',  1,           1,         0,          13,     2            ),
  (14,  'Partially paid',                      '{ts escape="sql"}Partially paid{/ts}',                      'Positive', 1,           1,         1,          14,     2           ),
  (15,  'Pending refund',                      '{ts escape="sql"}Pending refund{/ts}',                      'Positive', 1,           1,         1,          15,     2           ),
  (16,  'Transferred',                         '{ts escape="sql"}Transferred{/ts}',                         'Negative', 1, 1, 0, 16, 2);

-- CRM-8150
INSERT INTO civicrm_action_mapping
(entity, entity_value, entity_value_label, entity_status, entity_status_label, entity_date_start, entity_date_end, entity_recipient)
VALUES
( 'civicrm_activity', 'activity_type', 'Activity Type', 'activity_status', 'Activity Status', 'activity_date_time', NULL, 'activity_contacts'),
( 'civicrm_participant', 'event_type', 'Event Type', 'civicrm_participant_status_type', 'Participant Status', 'event_start_date', 'event_end_date', 'event_contacts'),
( 'civicrm_participant', 'civicrm_event', 'Event Name', 'civicrm_participant_status_type', 'Participant Status', 'event_start_date', 'event_end_date', 'event_contacts'),
( 'civicrm_membership', 'civicrm_membership_type', 'Membership Type', 'auto_renew_options', 'Auto Renew Options', 'membership_join_date', 'membership_end_date', NULL),
( 'civicrm_participant', 'event_template', 'Event Template', 'civicrm_participant_status_type', 'Participant Status', 'event_start_date', 'event_end_date', 'event_contacts'),
( 'civicrm_contact', 'civicrm_contact', 'Date Field', 'contact_date_reminder_options', 'Annual Options', 'date_field', NULL, NULL);

INSERT INTO `civicrm_contact_type`
  (`id`, `name`, `label`,`image_URL`, `parent_id`, `is_active`,`is_reserved`)
 VALUES
  ( 1, 'Individual'  , '{ts escape="sql"}Individual{/ts}'  , NULL, NULL, 1, 1),
  ( 2, 'Household'   , '{ts escape="sql"}Household{/ts}'   , NULL, NULL, 1, 1),
  ( 3, 'Organization', '{ts escape="sql"}Organization{/ts}', NULL, NULL, 1, 1);

{include file='civicrm_msg_template.tpl'}

-- CRM-8358

INSERT INTO `civicrm_job`
    ( domain_id, run_frequency, last_run, name, description, api_entity, api_action, parameters, is_active )
VALUES
    ( @domainID, 'Daily' ,  NULL, '{ts escape="sql" skip="true"}CiviCRM Update Check{/ts}', '{ts escape="sql" skip="true"}Checks for CiviCRM version updates. Important for keeping the database secure. Also sends anonymous usage statistics to civicrm.org to to assist in prioritizing ongoing development efforts.{/ts}', 'job', 'version_check', NULL, 1),
    ( @domainID, 'Always' , NULL, '{ts escape="sql" skip="true"}Send Scheduled Mailings{/ts}', '{ts escape="sql" skip="true"}Sends out scheduled CiviMail mailings{/ts}', 'job', 'process_mailing', NULL, 0),
    ( @domainID, 'Hourly' , NULL, '{ts escape="sql" skip="true"}Fetch Bounces{/ts}', '{ts escape="sql" skip="true"}Fetches bounces from mailings and writes them to mailing statistics{/ts}', 'job', 'fetch_bounces', NULL, 0),
    ( @domainID, 'Hourly' , NULL, '{ts escape="sql" skip="true"}Process Inbound Emails{/ts}', '{ts escape="sql" skip="true"}Inserts activity for a contact or a case by retrieving inbound emails from a mail directory{/ts}', 'job', 'fetch_activities', NULL, 0),
    ( @domainID, 'Daily' ,  NULL, '{ts escape="sql" skip="true"}Process Pledges{/ts}', '{ts escape="sql" skip="true"}Updates pledge records and sends out reminders{/ts}', 'job', 'process_pledge','{ts escape="sql" skip="true"}send_reminders=[1 or 0] optional- 1 to send payment reminders{/ts}', 0),
    ( @domainID, 'Daily' ,  NULL, '{ts escape="sql" skip="true"}Geocode and Parse Addresses{/ts}',  '{ts escape="sql" skip="true"}Retrieves geocodes (lat and long) and / or parses street addresses (populates street number, street name, etc.){/ts}', 'job', 'geocode', '{ts escape="sql" skip="true"}geocoding=[1 or 0] required
parse=[1 or 0] required
start=[contact ID] optional-begin with this contact ID
end=[contact ID] optional-process contacts with IDs less than this
throttle=[1 or 0] optional-1 adds five second sleep{/ts}', 0),
    ( @domainID, 'Daily' ,  NULL, '{ts escape="sql" skip="true"}Update Greetings and Addressees{/ts}','{ts escape="sql" skip="true"}Goes through contact records and updates email and postal greetings, or addressee value{/ts}', 'job', 'update_greeting','{ts escape="sql" skip="true"}ct=[Individual or Household or Organization] required
gt=[email_greeting or postal_greeting or addressee] required
force=[0 or 1] optional-0 update contacts with null value, 1 update all
limit=Number optional-Limit the number of contacts to update{/ts}', 0),
    ( @domainID, 'Daily' ,  NULL, '{ts escape="sql" skip="true"}Mail Reports{/ts}', '{ts escape="sql" skip="true"}Generates and sends out reports via email{/ts}', 'job', 'mail_report','{ts escape="sql" skip="true"}instanceId=[ID of report instance] required
format=[csv or print] optional-output CSV or print-friendly HTML, else PDF{/ts}', 0),
    ( @domainID, 'Daily' ,  NULL, '{ts escape="sql" skip="true"}Send Scheduled Reminders{/ts}', '{ts escape="sql" skip="true"}Sends out scheduled reminders via email{/ts}', 'job', 'send_reminder', NULL, 0),
    ( @domainID, 'Always' , NULL, '{ts escape="sql" skip="true"}Update Participant Statuses{/ts}', '{ts escape="sql" skip="true"}Updates pending event participant statuses based on time{/ts}', 'job', 'process_participant', NULL, 0),
    ( @domainID, 'Daily' , NULL, '{ts escape="sql" skip="true"}Update Membership Statuses{/ts}', '{ts escape="sql" skip="true"}Updates membership statuses. WARNING: Membership renewal reminders have been migrated to the Schedule Reminders functionality, which supports multiple renewal reminders.{/ts}', 'job', 'process_membership',   NULL, 0),
    ( @domainID, 'Always' , NULL, '{ts escape="sql" skip="true"}Process Survey Respondents{/ts}',   '{ts escape="sql" skip="true"}Releases reserved survey respondents when they have been reserved for longer than the Release Frequency days specified for that survey.{/ts}', 'job', 'process_respondent',NULL, 0),
    ( @domainID, 'Hourly' , NULL, '{ts escape="sql" skip="true"}Clean-up Temporary Data and Files{/ts}','{ts escape="sql" skip="true"}Removes temporary data and files, and clears old data from cache tables. Recommend running this job every hour to help prevent database and file system bloat.{/ts}', 'job', 'cleanup', NULL, 0),
    ( @domainID, 'Always' , NULL, '{ts escape="sql" skip="true"}Send Scheduled SMS{/ts}',           '{ts escape="sql" skip="true"}Sends out scheduled SMS{/ts}', 'job', 'process_sms',             NULL, 0),
    ( @domainID, 'Always' , NULL, '{ts escape="sql" skip="true"}Rebuild Smart Group Cache{/ts}', '{ts escape="sql" skip="true"}Rebuilds the smart group cache.{/ts}', 'job', 'group_rebuild', '{ts escape="sql" skip="true"}limit=Number optional-Limit the number of smart groups rebuild{/ts}', 0),
    ( @domainID, 'Daily' , NULL, '{ts escape="sql" skip="true"}Disable expired relationships{/ts}','{ts escape="sql" skip="true"}Disables relationships that have expired (ie. those relationships whose end date is in the past).{/ts}', 'job', 'disable_expired_relationships', NULL, 0),
    ( @domainID, 'Daily' , NULL, '{ts escape="sql" skip="true"}Validate Email Address from Mailings.{/ts}', '{ts escape="sql" skip="true"}Updates the reset_date on an email address to indicate that there was a valid delivery to this email address.{/ts}', 'mailing', 'update_email_resetdate', '{ts escape="sql" skip="true"}minDays, maxDays=Consider mailings that have completed between minDays and maxDays{/ts}', 0);

SELECT @option_value_rel_id  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Income Account is';
SELECT @option_value_rel_id_exp  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Expense Account is';
SELECT @option_value_rel_id_ar  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Accounts Receivable Account is';
SELECT @option_value_rel_id_as  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Asset Account is';
SELECT @option_value_rel_id_cg  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Cost of Sales Account is';
SELECT @option_value_rel_id_dr  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Deferred Revenue Account is';

SELECT @financial_type_id_dtn          := max(id) FROM civicrm_financial_type WHERE name = '{ts escape="sql"}Donation{/ts}';
SELECT @financial_type_id_md         := max(id) FROM civicrm_financial_type WHERE name = '{ts escape="sql"}Member Dues{/ts}';
SELECT @financial_type_id_cc         := max(id) FROM civicrm_financial_type WHERE name = '{ts escape="sql"}Campaign Contribution{/ts}';
SELECT @financial_type_id_ef         := max(id) FROM civicrm_financial_type WHERE name = '{ts escape="sql"}Event Fee{/ts}';

SELECT @financial_account_id_dtn       := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Donation{/ts}';
SELECT @financial_account_id_md         := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Member Dues{/ts}';
SELECT @financial_account_id_cc         := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Campaign Contribution{/ts}';
SELECT @financial_account_id_ef         := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Event Fee{/ts}';
SELECT @financial_account_id_bf         := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Banking Fees{/ts}';
SELECT @financial_account_id_ap        := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Accounts Receivable{/ts}';
SELECT @financial_account_id_ar        := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Deposit Bank Account{/ts}';
SELECT @financial_account_id_pp        := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Payment Processor Account{/ts}';
SELECT @financial_account_id_pr        := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Premiums{/ts}';
SELECT @financial_account_id_dref      := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Deferred Revenue - Event Fee{/ts}';
SELECT @financial_account_id_drmd      := max(id) FROM civicrm_financial_account WHERE name = '{ts escape="sql"}Deferred Revenue - Member Dues{/ts}';

INSERT INTO `civicrm_entity_financial_account`
     ( entity_table, entity_id, account_relationship, financial_account_id )
VALUES
     ( 'civicrm_financial_type', @financial_type_id_dtn, @option_value_rel_id, @financial_account_id_dtn ),
     ( 'civicrm_financial_type', @financial_type_id_dtn, @option_value_rel_id_exp, @financial_account_id_bf ),
     ( 'civicrm_financial_type', @financial_type_id_dtn, @option_value_rel_id_ar, @financial_account_id_ap ),
     ( 'civicrm_financial_type', @financial_type_id_dtn, @option_value_rel_id_cg, @financial_account_id_pr ),
     ( 'civicrm_financial_type', @financial_type_id_md, @option_value_rel_id, @financial_account_id_md ),
     ( 'civicrm_financial_type', @financial_type_id_md, @option_value_rel_id_exp, @financial_account_id_bf ),
     ( 'civicrm_financial_type', @financial_type_id_md, @option_value_rel_id_ar, @financial_account_id_ap ),
     ( 'civicrm_financial_type', @financial_type_id_md, @option_value_rel_id_cg, @financial_account_id_pr ),
     ( 'civicrm_financial_type', @financial_type_id_md, @option_value_rel_id_dr, @financial_account_id_drmd ),
     ( 'civicrm_financial_type', @financial_type_id_cc, @option_value_rel_id, @financial_account_id_cc ),
     ( 'civicrm_financial_type', @financial_type_id_cc, @option_value_rel_id_exp, @financial_account_id_bf ),
     ( 'civicrm_financial_type', @financial_type_id_cc, @option_value_rel_id_ar, @financial_account_id_ap ),
     ( 'civicrm_financial_type', @financial_type_id_cc, @option_value_rel_id_cg, @financial_account_id_pr ),
     ( 'civicrm_financial_type', @financial_type_id_ef, @option_value_rel_id_exp, @financial_account_id_bf ),
     ( 'civicrm_financial_type', @financial_type_id_ef, @option_value_rel_id_ar, @financial_account_id_ap ),
     ( 'civicrm_financial_type', @financial_type_id_ef, @option_value_rel_id, @financial_account_id_ef ),
     ( 'civicrm_financial_type', @financial_type_id_ef, @option_value_rel_id_dr, @financial_account_id_dref ),
     ( 'civicrm_financial_type', @financial_type_id_ef, @option_value_rel_id_cg, @financial_account_id_pr );

-- CRM-11516
INSERT INTO  civicrm_entity_financial_account (entity_table, entity_id, account_relationship, financial_account_id)
SELECT 'civicrm_option_value', cov.id, @option_value_rel_id_as, @financial_account_id_ar  FROM `civicrm_option_group` cog
LEFT JOIN civicrm_option_value cov ON cog.id = cov.option_group_id
WHERE cog.name = 'payment_instrument' AND cov.name NOT IN ('Credit Card', 'Debit Card');

SELECT @option_value_cc_id  := max(id) FROM `civicrm_option_value` WHERE `option_group_id` = @option_group_id_pi AND `name` = 'Credit Card';
SELECT @option_value_dc_id  := max(id) FROM `civicrm_option_value` WHERE `option_group_id` = @option_group_id_pi AND `name` = 'Debit Card';

INSERT INTO `civicrm_entity_financial_account`
     ( entity_table, entity_id, account_relationship, financial_account_id )
VALUES
     ( 'civicrm_option_value', @option_value_cc_id, @option_value_rel_id_as, @financial_account_id_pp),
     ( 'civicrm_option_value', @option_value_dc_id, @option_value_rel_id_as, @financial_account_id_pp);

-- CRM-9714

SELECT @financial_type_id := max(id) FROM `civicrm_financial_type` WHERE `name` = 'Member Dues';
INSERT INTO `civicrm_price_set` ( `name`, `title`, `is_active`, `extends`, `is_quick_config`, `financial_type_id`, `is_reserved` )
VALUES ( 'default_contribution_amount', 'Contribution Amount', '1', '2', '1', NULL,1),
( 'default_membership_type_amount', 'Membership Amount', '1', '3', '1', @financial_type_id,1);

SELECT @setID := max(id) FROM civicrm_price_set WHERE name = 'default_contribution_amount' AND extends = 2 AND is_quick_config = 1 ;

INSERT INTO `civicrm_price_field` (`price_set_id`, `name`, `label`, `html_type`,`weight`, `is_display_amounts`, `options_per_line`, `is_active`, `is_required`,`visibility_id` )
VALUES ( @setID, 'contribution_amount', 'Contribution Amount', 'Text', '1', '1', '1', '1', '1', '1' );

SELECT @fieldID := max(id) FROM civicrm_price_field WHERE name = 'contribution_amount' AND price_set_id = @setID;

INSERT INTO `civicrm_price_field_value` (  `price_field_id`, `name`, `label`, `amount`, `weight`, `is_default`, `is_active`, `financial_type_id`)
VALUES ( @fieldID, 'contribution_amount', 'Contribution Amount', '1', '1', '0', '1', 1);

-- CRM-13833
INSERT INTO civicrm_option_group (`name`, `title`, `is_reserved`, `is_active`) VALUES ('soft_credit_type', {localize}'{ts escape="sql"}Soft Credit Types{/ts}'{/localize}, 1, 1);

SELECT @option_group_id_soft_credit_type := max(id) from civicrm_option_group where name = 'soft_credit_type';

INSERT INTO `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `weight`, `is_default`, `is_active`, `is_reserved`)
VALUES
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}In Honor of{/ts}'{/localize}, 1, 'in_honor_of', 1, 0, 1, 1),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}In Memory of{/ts}'{/localize}, 2, 'in_memory_of', 2, 0, 1, 1),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Solicited{/ts}'{/localize}, 3, 'solicited', 3, 1, 1, 1),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Household{/ts}'{/localize}, 4, 'household', 4, 0, 1, 0),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Workplace Giving{/ts}'{/localize}, 5, 'workplace', 5, 0, 1, 0),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Foundation Affiliate{/ts}'{/localize}, 6, 'foundation_affiliate', 6, 0, 1, 0),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}3rd-party Service{/ts}'{/localize}, 7, '3rd-party_service', 7, 0, 1, 0),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Donor-advised Fund{/ts}'{/localize}, 8, 'donor-advised_fund', 8, 0, 1, 0),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Matched Gift{/ts}'{/localize}, 9, 'matched_gift', 9, 0, 1, 0),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Personal Campaign Page{/ts}'{/localize}, 10, 'pcp', 10, 0, 1, 1),
  (@option_group_id_soft_credit_type   , {localize}'{ts escape="sql"}Gift{/ts}'{/localize}, 11, 'gift', 11, 0, 1, 1);
