-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC. All rights reserved.                        |
-- |                                                                    |
-- | This work is published under the GNU AGPLv3 license with some      |
-- | permitted exceptions and without any warranty. For full license    |
-- | and copyright information, see https://civicrm.org/licensing       |
-- +--------------------------------------------------------------------+
-- Navigation Menu, Preferences and Mail Settings
--
-- Generated from {$smarty.template}
-- {$generated}

SELECT @domainID := id FROM civicrm_domain where name = 'Default Domain Name';

-- Initial default state of system preferences

-- mail settings

INSERT INTO civicrm_mail_settings (domain_id, name, is_default, domain) VALUES (@domainID, 'default', true, 'EXAMPLE.ORG');

-- activity and case dashlets

INSERT INTO `civicrm_dashboard`
    ( `domain_id`, `name`, `label`, `url`, `permission`, `permission_operator`, `is_active`, `fullscreen_url`, `is_reserved`, `cache_minutes`)
    VALUES
    ( @domainID, 'blog', '{ts escape="sql"}CiviCRM News{/ts}', 'civicrm/dashlet/blog?reset=1', 'access CiviCRM', NULL, 1, 'civicrm/dashlet/blog?reset=1&context=dashletFullscreen', 1, 1440),
    ( @domainID, 'getting-started', '{ts escape="sql"}CiviCRM Resources{/ts}', 'civicrm/dashlet/getting-started?reset=1', 'access CiviCRM', NULL, 1, 'civicrm/dashlet/getting-started?reset=1&context=dashletFullscreen', 1, 7200),

    ( @domainID, 'activity', '{ts escape="sql"}Activities{/ts}', 'civicrm/dashlet/activity?reset=1', 'access CiviCRM', NULL, 1, 'civicrm/dashlet/activity?reset=1&context=dashletFullscreen', 1, 7200),
    ( @domainID, 'myCases', '{ts escape="sql"}My Cases{/ts}', 'civicrm/dashlet/myCases?reset=1', 'access my cases and activities', NULL, 1, 'civicrm/dashlet/myCases?reset=1&context=dashletFullscreen', 1, 60),
    ( @domainID, 'allCases', '{ts escape="sql"}All Cases{/ts}', 'civicrm/dashlet/allCases?reset=1', 'access all cases and activities', NULL, 1, 'civicrm/dashlet/allCases?reset=1&context=dashletFullscreen', 1, 60),
    ( @domainID, 'casedashboard', '{ts escape="sql"}Case Dashboard Dashlet{/ts}', 'civicrm/dashlet/casedashboard?reset=1', 'access my cases and activities,access all cases and activities', 'OR', 1, 'civicrm/dashlet/casedashboard?reset=1&context=dashletFullscreen', 1, 60);

-- event badge
INSERT INTO civicrm_print_label (title, name, description, label_format_name, label_type_id, is_default, is_reserved, is_active, data)
VALUES
('Annual Conference Hanging Badge (Avery 5395)', 'Annual_Conference_Hanging_Badge', 'For our annual conference', 'Avery 5395', 1, 1, 1, 1, '{literal}{"title":"Annual Conference Hanging Badge (Avery 5395)","label_format_name":"Avery 5395","description":"For our annual conference","token":{"1":"{event.title}","2":"{contact.display_name}","3":"{contact.current_employer}","4":"{event.start_date|crmDate:\\\"%B %E%f\\\"}"},"font_name":{"1":"dejavusans","2":"dejavusans","3":"dejavusans","4":"dejavusans"},"font_size":{"1":"9","2":"20","3":"15","4":"9"},"font_style":{"1":"","2":"","3":"","4":""},"text_alignment":{"1":"L","2":"C","3":"C","4":"R"},"barcode_type":"barcode","barcode_alignment":"R","image_1":"","image_2":"","is_default":"1","is_active":"1","is_reserved":"1","_qf_default":"Layout:next","_qf_Layout_refresh":"Save and Preview"}{/literal}');

-- navigation

INSERT INTO civicrm_navigation
 ( domain_id, label, name, url, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
 ( @domainID, '{ts escape="sql" skip="true"}Home{/ts}', 'Home', 'civicrm/dashboard?reset=1', NULL, '', NULL, 1, NULL, 0);

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight, icon )
VALUES
    (  @domainID, NULL, '{ts escape="sql" skip="true"}Search{/ts}',  'Search',    NULL, '',  NULL, '1', NULL, 10, 'crm-i fa-search' );

SET @searchlastID:=LAST_INSERT_ID();

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/contact/search?reset=1',                          '{ts escape="sql" skip="true"}Find Contacts{/ts}',      'Find Contacts', NULL, '',                      @searchlastID, '1', NULL, 1 ),
    ( @domainID, 'civicrm/contact/search/advanced?reset=1',                 '{ts escape="sql" skip="true"}Advanced Search{/ts}',    'Advanced Search', NULL, '', @searchlastID, '1', NULL, 2 ),
    ( @domainID, 'civicrm/contact/search/custom?csid=15&reset=1',           '{ts escape="sql" skip="true"}Full-text Search{/ts}',   'Full-text Search', NULL, '',                   @searchlastID, '1', NULL, 3 ),
    ( @domainID, 'civicrm/case/search?reset=1',                             '{ts escape="sql" skip="true"}Find Cases{/ts}',         'Find Cases', 'access my cases and activities,access all cases and activities', 'OR',            @searchlastID, '1', NULL, 5 ),
    ( @domainID, 'civicrm/contribute/search?reset=1',                       '{ts escape="sql" skip="true"}Find Contributions{/ts}', 'Find Contributions', 'access CiviContribute', '',  @searchlastID, '1', NULL, 6 ),
    ( @domainID, 'civicrm/mailing?reset=1',                                 '{ts escape="sql" skip="true"}Find Mailings{/ts}',      'Find Mailings', 'access CiviMail', '',         @searchlastID, '1', NULL, 7 ),
    ( @domainID, 'civicrm/member/search?reset=1',                           '{ts escape="sql" skip="true"}Find Memberships{/ts}',   'Find Memberships', 'access CiviMember', '',        @searchlastID, '1', NULL, 8 ),
    ( @domainID, 'civicrm/event/search?reset=1',                            '{ts escape="sql" skip="true"}Find Participants{/ts}',  'Find Participants',  'access CiviEvent', '',   @searchlastID, '1', NULL, 9 ),
    ( @domainID, 'civicrm/pledge/search?reset=1',                           '{ts escape="sql" skip="true"}Find Pledges{/ts}',       'Find Pledges', 'access CiviPledge', '',        @searchlastID, '1', NULL, 10 ),
    ( @domainID, 'civicrm/activity/search?reset=1',                         '{ts escape="sql" skip="true"}Find Activities{/ts}',    'Find Activities', NULL,  '',                   @searchlastID, '1', '1',  11 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight, icon )
VALUES
    ( @domainID, NULL,  '{ts escape="sql" skip="true"}Contacts{/ts}', 'Contacts', NULL, '', NULL, '1', NULL, 20, 'crm-i fa-address-book-o' );

SET @contactlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/contact/add?reset=1&ct=Individual',       '{ts escape="sql" skip="true"}New Individual{/ts}',         'New Individual',       'add contacts',     '',             @contactlastID, '1', NULL,  1 ),
    ( @domainID, 'civicrm/contact/add?reset=1&ct=Household',        '{ts escape="sql" skip="true"}New Household{/ts}',          'New Household',        'add contacts',     '',             @contactlastID, '1', NULL,  2 ),
       ( @domainID, 'civicrm/contact/add?reset=1&ct=Organization',  '{ts escape="sql" skip="true"}New Organization{/ts}',       'New Organization',     'add contacts',     '',             @contactlastID, '1', 1,     3 ),
       ( @domainID, 'civicrm/report/list?compid=99&reset=1',  '{ts escape="sql" skip="true"}Contact Reports{/ts}',       'Contact Reports',     'access CiviReport',     '',             @contactlastID, '1', 1,     4 );


INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/activity?reset=1&action=add&context=standalone',  '{ts escape="sql" skip="true"}New Activity{/ts}',           'New Activity',         NULL,               '',             @contactlastID, '1', NULL,  5 ),
    ( @domainID, 'civicrm/activity/email/add?atype=3&action=add&reset=1&context=standalone', '{ts escape="sql" skip="true"}New Email{/ts}',   'New Email',      NULL,               '',             @contactlastID, '1', '1',   6 ),
    ( @domainID, 'civicrm/import/contact?reset=1',                          '{ts escape="sql" skip="true"}Import Contacts{/ts}',        'Import Contacts',      'import contacts',  '',             @contactlastID, '1', NULL,  7 ),
    ( @domainID, 'civicrm/import/activity?reset=1',                         '{ts escape="sql" skip="true"}Import Activities{/ts}',      'Import Activities',    'import contacts',  '',             @contactlastID, '1', NULL,   8 ),
    ( @domainID, 'civicrm/import/custom?reset=1',                         '{ts escape="sql" skip="true"}Import Custom Data{/ts}',  'Import MultiValued Custom', 'import contacts',  '',             @contactlastID, '1', '1',   9 ),
    ( @domainID, 'civicrm/group/add?reset=1',                               '{ts escape="sql" skip="true"}New Group{/ts}',              'New Group',            'edit groups',      '',             @contactlastID, '0', NULL,  10 ),
    ( @domainID, 'civicrm/group?reset=1',                                   '{ts escape="sql" skip="true"}Manage Groups{/ts}',          'Manage Groups',        'access CiviCRM',   '',             @contactlastID, '1', '1',   11 ),
    ( @domainID, 'civicrm/tag?reset=1',                               '{ts escape="sql" skip="true"}Manage Tags{/ts}', 'Manage Tags (Categories)',              'manage tags',      '',             @contactlastID, '1', '1',   12 ),
    ( @domainID, 'civicrm/contact/deduperules?reset=1',  '{ts escape="sql" skip="true"}Manage Duplicates{/ts}', 'Manage Duplicates', 'administer dedupe rules,merge duplicate contacts', 'OR', @contactlastID, '1', NULL, 13 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight, icon )
VALUES
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Contributions{/ts}', 'Contributions', 'access CiviContribute', '', NULL, '1', NULL,  30, 'crm-i fa-credit-card' );

SET @contributionlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/contribute?reset=1',                              '{ts escape="sql" skip="true"}Dashboard{/ts}',              'Dashboard',              'access CiviContribute', '', @contributionlastID, '1', NULL, 1 ),
    ( @domainID, 'civicrm/contribute/add?reset=1&action=add&context=standalone', '{ts escape="sql" skip="true"}New Contribution{/ts}',  'New Contribution',       'access CiviContribute,edit contributions', 'AND', @contributionlastID, '1', NULL, 2 ),
    ( @domainID, 'civicrm/contribute/search?reset=1',                       '{ts escape="sql" skip="true"}Find Contributions{/ts}',     'Find Contributions',     'access CiviContribute', '', @contributionlastID, '1', NULL, 3 ),
    ( @domainID, 'civicrm/report/list?compid=2&reset=1',                    '{ts escape="sql" skip="true"}Contribution Reports{/ts}',   'Contribution Reports',     'access CiviContribute', '', @contributionlastID, '1', 1,    4 ),
    ( @domainID, 'civicrm/import/contribution?reset=1',                       '{ts escape="sql" skip="true"}Import Contributions{/ts}',   'Import Contributions',   'access CiviContribute,edit contributions', 'AND', @contributionlastID, '1', '1',  5 ),
    ( @domainID, 'civicrm/batch?reset=1',                                   '{ts escape="sql" skip="true"}Batch Data Entry{/ts}',        'Batch Data Entry',           'access CiviContribute', '', @contributionlastID, '1', NULL, 7 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID,NULL, '{ts escape="sql" skip="true"}Pledges{/ts}',  'Pledges', 'access CiviPledge', '', @contributionlastID, '1',  1,   6 );

SET @pledgelastID:=LAST_INSERT_ID();

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID,NULL, '{ts escape="sql" skip="true"}Accounting Batches{/ts}',  'Accounting Batches', 'view own manual batches,view all manual batches', 'OR', @contributionlastID, '1',  1,   8 );

SET @financialTransactionID:=LAST_INSERT_ID();

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/pledge?reset=1',                                  '{ts escape="sql" skip="true"}Dashboard{/ts}',                  'Dashboard',                 'access CiviPledge',  '',  @pledgelastID,       '1', NULL, 1 ),
    ( @domainID, 'civicrm/pledge/add?reset=1&action=add&context=standalone', '{ts escape="sql" skip="true"}New Pledge{/ts}',                'New Pledge',                'access CiviPledge,edit pledges',  'AND',  @pledgelastID,       '1', NULL, 2 ),
    ( @domainID, 'civicrm/pledge/search?reset=1',                           '{ts escape="sql" skip="true"}Find Pledges{/ts}',               'Find Pledges',              'access CiviPledge',  '',  @pledgelastID,       '1', NULL, 3 ),
    ( @domainID, 'civicrm/report/list?compid=6&reset=1', '{ts escape="sql" skip="true"}Pledge Reports{/ts}', 'Pledge Reports', 'access CiviPledge', '', @pledgelastID, '1', 0,    4 ),
    ( @domainID, 'civicrm/admin/contribute/add?reset=1&action=add',         '{ts escape="sql" skip="true"}New Contribution Page{/ts}',      'New Contribution Page',     'access CiviContribute,administer CiviCRM', 'AND',  @contributionlastID, '0', NULL, 9 ),
    ( @domainID, 'civicrm/admin/contribute?reset=1',                        '{ts escape="sql" skip="true"}Manage Contribution Pages{/ts}',  'Manage Contribution Pages', 'access CiviContribute,administer CiviCRM', 'AND',  @contributionlastID, '1', '1',  10 ),
    ( @domainID, 'civicrm/admin/pcp?reset=1&page_type=contribute',          '{ts escape="sql" skip="true"}Personal Campaign Pages{/ts}',    'Personal Campaign Pages',   'access CiviContribute,administer CiviCRM', 'AND',  @contributionlastID, '1', NULL, 11 ),
    ( @domainID, 'civicrm/admin/contribute/managePremiums?reset=1',         '{ts escape="sql" skip="true"}Premiums (Thank-you Gifts){/ts}', 'Premiums',                  'access CiviContribute,administer CiviCRM', 'AND',  @contributionlastID, '1', 1,    12 ),
    ( @domainID, 'civicrm/admin/price/edit?reset=1&action=add',             '{ts escape="sql" skip="true"}New Price Set{/ts}',              'New Price Set',             'access CiviContribute,administer CiviCRM', 'AND',  @contributionlastID, '0', NULL, 13 ),
    ( @domainID, 'civicrm/admin/price?reset=1',                             '{ts escape="sql" skip="true"}Manage Price Sets{/ts}',          'Manage Price Sets',         'access CiviContribute,administer CiviCRM', 'AND',  @contributionlastID, '1', 1, 14 ),

    ( @domainID, 'civicrm/financial/batch?reset=1&action=add',                             '{ts escape="sql" skip="true"}New Batch{/ts}',          'New Batch',         'create manual batch', 'AND',  @financialTransactionID, '1', NULL, 1 ),
    ( @domainID, 'civicrm/financial/financialbatches?reset=1&batchStatus=1', '{ts escape="sql" skip="true"}Open Batches{/ts}',          'Open Batches',         'view own manual batches,view all manual batches', 'OR',  @financialTransactionID, '1', NULL, 2 ),
    ( @domainID, 'civicrm/financial/financialbatches?reset=1&batchStatus=2', '{ts escape="sql" skip="true"}Closed Batches{/ts}',          'Closed Batches',         'view own manual batches,view all manual batches', 'OR',  @financialTransactionID, '1', NULL, 3 ),
    ( @domainID, 'civicrm/financial/financialbatches?reset=1&batchStatus=5', '{ts escape="sql" skip="true"}Exported Batches{/ts}',          'Exported Batches',         'view own manual batches,view all manual batches', 'OR',  @financialTransactionID, '1', NULL, 4 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight, icon )
VALUES
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Events{/ts}',  'Events', 'access CiviEvent', '', NULL, '1', NULL, 40, 'crm-i fa-calendar' );

SET @eventlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/event?reset=1',                                   '{ts escape="sql" skip="true"}Dashboard{/ts}',          'CiviEvent Dashboard',  'access CiviEvent', '',    @eventlastID, '1', NULL, 1 ),
    ( @domainID, 'civicrm/participant/add?reset=1&action=add&context=standalone', '{ts escape="sql" skip="true"}Register Event Participant{/ts}', 'Register Event Participant', 'access CiviEvent,edit event participants', 'AND', @eventlastID, '1', NULL, 2 ),
    ( @domainID, 'civicrm/event/search?reset=1',                            '{ts escape="sql" skip="true"}Find Participants{/ts}',  'Find Participants',    'access CiviEvent', '',    @eventlastID, '1', NULL, 3 ),
    ( @domainID, 'civicrm/report/list?compid=1&reset=1', '{ts escape="sql" skip="true"}Event Reports{/ts}', 'Event Reports', 'access CiviEvent', '', @eventlastID, '1', 1,    4 ),
    ( @domainID, 'civicrm/import/participant?reset=1',                            '{ts escape="sql" skip="true"}Import Participants{/ts}','Import Participants',  'access CiviEvent,edit event participants', 'AND',    @eventlastID, '1', '1',  5 ),
    ( @domainID, 'civicrm/event/add?reset=1&action=add',                    '{ts escape="sql" skip="true"}New Event{/ts}',          'New Event',            'access CiviEvent,edit all events', 'AND',    @eventlastID, '0', NULL, 6 ),
    ( @domainID, 'civicrm/event/manage?reset=1',                            '{ts escape="sql" skip="true"}Manage Events{/ts}',      'Manage Events',        'access CiviEvent,edit all events', 'AND',    @eventlastID, '1', 1, 7 ),
    ( @domainID, 'civicrm/admin/pcp?reset=1&page_type=event',               '{ts escape="sql" skip="true"}Personal Campaign Pages{/ts}',    'Personal Campaign Pages',   'access CiviEvent,administer CiviCRM', 'AND', @eventlastID, '1', 1, 8 ),
    ( @domainID, 'civicrm/admin/eventTemplate?reset=1',                     '{ts escape="sql" skip="true"}Event Templates{/ts}',    'Event Templates',      'access CiviEvent,edit all events', 'AND',    @eventlastID, '1', 1, 9 ),
    ( @domainID, 'civicrm/admin/price/edit?reset=1&action=add',             '{ts escape="sql" skip="true"}New Price Set{/ts}',      'New Price Set',        'access CiviEvent,edit all events', 'AND',    @eventlastID, '0', NULL, 10 ),
    ( @domainID, 'civicrm/admin/price?reset=1',                             '{ts escape="sql" skip="true"}Manage Price Sets{/ts}',  'Manage Price Sets',    'access CiviEvent,edit all events', 'AND',    @eventlastID, '1', NULL, 11 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight, icon )
VALUES
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Mailings{/ts}', 'Mailings', 'access CiviMail,create mailings,approve mailings,schedule mailings,send SMS', 'OR', NULL, '1', NULL, 50, 'crm-i fa-envelope-o' );

SET @mailinglastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/mailing/send?reset=1',                            '{ts escape="sql" skip="true"}New Mailing{/ts}', 'New Mailing',                                          'access CiviMail,create mailings', 'OR', @mailinglastID, '1', NULL, 1 ),
    ( @domainID, 'civicrm/mailing/browse/unscheduled?reset=1&scheduled=false', '{ts escape="sql" skip="true"}Draft Mailings{/ts}', 'Draft and Unscheduled Mailings', 'access CiviMail,create mailings,schedule mailings', 'OR', @mailinglastID, '1', NULL, 2 ),
    ( @domainID, 'civicrm/mailing/browse/scheduled?reset=1&scheduled=true', '{ts escape="sql" skip="true"}Sent Mailings{/ts}', 'Scheduled and Sent Mailings',          'access CiviMail,approve mailings,create mailings,schedule mailings', 'OR', @mailinglastID, '1', NULL, 3 ),
    ( @domainID, 'civicrm/mailing/browse/archived?reset=1',                 '{ts escape="sql" skip="true"}Archived Mailings{/ts}', 'Archived Mailings',                              'access CiviMail,create mailings', 'OR', @mailinglastID, '1', NULL,    4 ),
    ( @domainID, 'civicrm/report/list?compid=4&reset=1', '{ts escape="sql" skip="true"}Mailing Reports{/ts}', 'Mailing Reports', 'access CiviMail', '', @mailinglastID, '1', 1,    5 ),
    ( @domainID, 'civicrm/admin/component?reset=1',                         '{ts escape="sql" skip="true"}Headers, Footers, and Automated Messages{/ts}', 'Headers, Footers, and Automated Messages', 'access CiviMail,administer CiviCRM', 'AND', @mailinglastID, '1', NULL, 6 ),
    ( @domainID, 'civicrm/admin/messageTemplates?reset=1',                  '{ts escape="sql" skip="true"}Message Templates{/ts}', 'Message Templates',                 'edit message templates,edit user-driven message templates,edit system workflow message templates', 'OR', @mailinglastID, '1', NULL, 7 ),
    ( @domainID, 'civicrm/admin/options/from_email_address?reset=1', '{ts escape="sql" skip="true"}From Email Addresses{/ts}', 'From Email Addresses', 'administer CiviCRM', '', @mailinglastID, '1', 1, 8 ),
    ( @domainID, 'civicrm/sms/send?reset=1',  '{ts escape="sql" skip="true"}New SMS{/ts}', 'New SMS', 'send SMS', NULL, @mailinglastID, '1', NULL, 9 ),
    ( @domainID, 'civicrm/mailing/browse?reset=1&sms=1', '{ts escape="sql" skip="true"}Find Mass SMS{/ts}', 'Find Mass SMS', 'send SMS', NULL, @mailinglastID, '1', 1, 10 ),
    ( @domainID, 'civicrm/a/#/abtest/new',                                  '{ts escape="sql" skip="true"}New A/B Test{/ts}', 'New A/B Test',                                        'access CiviMail', '', @mailinglastID, '1', NULL, 15 ),
    ( @domainID, 'civicrm/a/#/abtest',                                      '{ts escape="sql" skip="true"}Manage A/B Tests{/ts}', 'Manage A/B Tests',                                'access CiviMail', '', @mailinglastID, '1', 1, 16 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight, icon )
VALUES
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Memberships{/ts}', 'Memberships', 'access CiviMember', '', NULL, '1', NULL, 60, 'crm-i fa-id-badge' );

SET @memberlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/member?reset=1',                              '{ts escape="sql" skip="true"}Dashboard{/ts}',           'Dashboard',       'access CiviMember', '', @memberlastID, '1', NULL, 1 ),
    ( @domainID, 'civicrm/member/add?reset=1&action=add&context=standalone', '{ts escape="sql" skip="true"}New Membership{/ts}', 'New Membership',  'access CiviMember,edit memberships', 'AND', @memberlastID, '0', NULL, 2 ),
    ( @domainID, 'civicrm/member/search?reset=1',                       '{ts escape="sql" skip="true"}Find Memberships{/ts}',    'Find Memberships','access CiviMember', '', @memberlastID, '1', NULL, 3 ),
    ( @domainID, 'civicrm/report/list?compid=3&reset=1',                '{ts escape="sql" skip="true"}Membership Reports{/ts}',  'Membership Reports', 'access CiviMember', '', @memberlastID, '1', 1,    4 ),
    ( @domainID, 'civicrm/batch?reset=1',                               '{ts escape="sql" skip="true"}Batch Data Entry{/ts}',     'Batch Data Entry','access CiviContribute', '', @memberlastID, '1', NULL, 5 ),
    ( @domainID, 'civicrm/import/membership?reset=1',                       '{ts escape="sql" skip="true"}Import Memberships{/ts}',  'Import Members',  'access CiviMember,edit memberships', 'AND', @memberlastID, '1', 1, 6 ),
    ( @domainID, 'civicrm/admin/price/edit?reset=1&action=add',         '{ts escape="sql" skip="true"}New Price Set{/ts}',       'New Price Set',   'access CiviMember,administer CiviCRM', 'AND',  @memberlastID, '0', NULL, 7 ),
    ( @domainID, 'civicrm/admin/price?reset=1',                         '{ts escape="sql" skip="true"}Manage Price Sets{/ts}',   'Manage Price Sets', 'access CiviMember,administer CiviCRM', 'AND',  @memberlastID, '1', NULL, 8 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight, icon )
VALUES
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Campaigns{/ts}', 'Campaigns', 'interview campaign contacts,release campaign contacts,reserve campaign contacts,manage campaign,administer CiviCampaign,gotv campaign contacts', 'OR', NULL, '1', NULL, 70, 'crm-i fa-bullhorn' );

SET @campaignlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/campaign/add?reset=1',        '{ts escape="sql" skip="true"}New Campaign{/ts}', 'New Campaign', 'manage campaign,administer CiviCampaign', 'OR', @campaignlastID, '1', NULL, 2 ),
    ( @domainID, 'civicrm/survey/add?reset=1',        '{ts escape="sql" skip="true"}New Survey{/ts}', 'New Survey', 'manage campaign,administer CiviCampaign', 'OR', @campaignlastID, '1', NULL, 3 ),
    ( @domainID, 'civicrm/petition/add?reset=1',        '{ts escape="sql" skip="true"}New Petition{/ts}', 'New Petition', 'manage campaign,administer CiviCampaign', 'OR', @campaignlastID, '1', NULL, 4 ),
    ( @domainID, 'civicrm/survey/search?reset=1&op=reserve', '{ts escape="sql" skip="true"}Reserve Respondents{/ts}', 'Reserve Respondents', 'administer CiviCampaign,manage campaign,reserve campaign contacts', 'OR', @campaignlastID, '1', NULL, 5 ),
    ( @domainID, 'civicrm/survey/search?reset=1&op=interview', '{ts escape="sql" skip="true"}Interview Respondents{/ts}', 'Interview Respondents', 'administer CiviCampaign,manage campaign,interview campaign contacts', 'OR', @campaignlastID, '1', NULL, 6 ),
    ( @domainID, 'civicrm/survey/search?reset=1&op=release', '{ts escape="sql" skip="true"}Release Respondents{/ts}', 'Release Respondents', 'administer CiviCampaign,manage campaign,release campaign contacts', 'OR', @campaignlastID, '1', NULL, 7 ),
    ( @domainID, 'civicrm/report/list?compid=9&reset=1', '{ts escape="sql" skip="true"}Campaign Reports{/ts}', 'Campaign Reports', 'interview campaign contacts,release campaign contacts,reserve campaign contacts,manage campaign,administer CiviCampaign,gotv campaign contacts', 'OR', @campaignlastID, '1', 1,    8 ),
    ( @domainID, 'civicrm/campaign/vote?reset=1', '{ts escape="sql" skip="true"}Conduct Survey{/ts}', 'Conduct Survey', 'administer CiviCampaign,manage campaign,reserve campaign contacts,interview campaign contacts', 'OR', @campaignlastID, '1', NULL, 9 ),
    ( @domainID, 'civicrm/campaign/gotv?reset=1', '{ts escape="sql" skip="true"}GOTV (Voter Tracking){/ts}', 'Voter Listing', 'administer CiviCampaign,manage campaign,release campaign contacts,gotv campaign contacts', 'OR', @campaignlastID, '1', NULL, 10 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight, icon )
VALUES
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Cases{/ts}', 'Cases', 'access my cases and activities,access all cases and activities', 'OR', NULL, '1', NULL, 80, 'crm-i fa-folder-open-o' );

SET @caselastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/case?reset=1',        '{ts escape="sql" skip="true"}Dashboard{/ts}', 'Dashboard', 'access my cases and activities,access all cases and activities', 'OR',       @caselastID, '1', NULL, 1 ),
    ( @domainID, 'civicrm/case/add?reset=1&action=add&atype=13&context=standalone', '{ts escape="sql" skip="true"}New Case{/ts}', 'New Case', 'add cases,access all cases and activities', 'OR', @caselastID, '1', NULL, 2 ),
    ( @domainID, 'civicrm/case/search?reset=1', '{ts escape="sql" skip="true"}Find Cases{/ts}', 'Find Cases', 'access my cases and activities,access all cases and activities', 'OR',     @caselastID, '1', 1, 3 ),
    ( @domainID, 'civicrm/report/list?compid=7&reset=1', '{ts escape="sql" skip="true"}Case Reports{/ts}', 'Case Reports', 'access my cases and activities,access all cases and activities,administer CiviCase', 'OR', @caselastID, '1', 0,    4 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight, icon )
VALUES
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Administer{/ts}', 'Administer', 'administer CiviCRM', '', NULL, '1', NULL, 100, 'crm-i fa-gears' );

SET @adminlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/admin?reset=1', '{ts escape="sql" skip="true"}Administration Console{/ts}', 'Administration Console', 'administer CiviCRM', '', @adminlastID, '1', NULL, 1 );

SET @adminConsolelastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/a/#/status',               '{ts escape="sql" skip="true"}System Status{/ts}',           'System Status',           'administer CiviCRM', '', @adminConsolelastID, '1', NULL, 0 ),
    ( @domainID, 'civicrm/admin/configtask?reset=1', '{ts escape="sql" skip="true"}Configuration Checklist{/ts}', 'Configuration Checklist', 'administer CiviCRM', '', @adminConsolelastID, '1', NULL, 1 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Customize Data and Screens{/ts}', 'Customize Data and Screens', 'administer CiviCRM', '', @adminlastID, '1', NULL, 3 );

SET @CustomizelastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/admin/custom/group?reset=1',      '{ts escape="sql" skip="true"}Custom Fields{/ts}', 'Custom Fields',                             'administer CiviCRM', '',   @CustomizelastID, '1', NULL, 1 ),
    ( @domainID, 'civicrm/admin/uf/group?reset=1',          '{ts escape="sql" skip="true"}Profiles{/ts}', 'Profiles',                                       'administer CiviCRM', '',   @CustomizelastID, '1', NULL, 2 ),
    ( @domainID, 'civicrm/tag?reset=1',               '{ts escape="sql" skip="true"}Tags{/ts}', 'Tags (Categories)',                     'administer CiviCRM', '',   @CustomizelastID, '1', NULL, 3 ),
    ( @domainID, 'civicrm/admin/options/activity_type?reset=1', '{ts escape="sql" skip="true"}Activity Types{/ts}', 'Activity Types',   'administer CiviCRM', '',   @CustomizelastID, '1', NULL, 4 ),
    ( @domainID, 'civicrm/admin/reltype?reset=1',           '{ts escape="sql" skip="true"}Relationship Types{/ts}', 'Relationship Types',                   'administer CiviCRM', '',   @CustomizelastID, '1', NULL, 5 ),
    ( @domainID, 'civicrm/admin/options/subtype?reset=1',   '{ts escape="sql" skip="true"}Contact Types{/ts}','Contact Types',                              'administer CiviCRM', '',   @CustomizelastID, '1', NULL, 6 ),
    ( @domainID, 'civicrm/admin/setting/preferences/display?reset=1',   '{ts escape="sql" skip="true"}Display Preferences{/ts}', 'Display Preferences',     'administer CiviCRM', '',   @CustomizelastID, '1', NULL, 9 ),
    ( @domainID, 'civicrm/admin/setting/search?reset=1',    '{ts escape="sql" skip="true"}Search Preferences{/ts}',    'Search Preferences',                'administer CiviCRM', '',   @CustomizelastID, '1', NULL, 10 ),
    ( @domainID, 'civicrm/admin/setting/preferences/date?reset=1', '{ts escape="sql" skip="true"}Date Preferences{/ts}', 'Date Preferences', 'administer CiviCRM', '', @CustomizelastID, '1', NULL, 11 ),
    ( @domainID, 'civicrm/admin/menu?reset=1',              '{ts escape="sql" skip="true"}Navigation Menu{/ts}', 'Navigation Menu',                         'administer CiviCRM', '',   @CustomizelastID, '1', NULL, 12 ),
    ( @domainID, 'civicrm/admin/options/wordreplacements?reset=1','{ts escape="sql" skip="true"}Word Replacements{/ts}','Word Replacements',                'administer CiviCRM', '',   @CustomizelastID, '1', NULL, 13 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/admin/options?action=browse&reset=1', '{ts escape="sql" skip="true"}Dropdown Options{/ts}', 'Dropdown Options', 'administer CiviCRM', '', @CustomizelastID, '1', NULL, 8 );

SET @optionListlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/admin/options/gender?reset=1',                                                  '{ts escape="sql" skip="true"}Gender Options{/ts}',         'Gender Options',                           'administer CiviCRM', '',   @optionListlastID, '1', NULL,  1 ),
    ( @domainID, 'civicrm/admin/options/individual_prefix?reset=1',                            '{ts escape="sql" skip="true"}Individual Prefixes (Ms, Mr...){/ts}', 'Individual Prefixes (Ms, Mr...)', 'administer CiviCRM', '',   @optionListlastID, '1', NULL,  2 ),
    ( @domainID, 'civicrm/admin/options/individual_suffix?reset=1',                            '{ts escape="sql" skip="true"}Individual Suffixes (Jr, Sr...){/ts}', 'Individual Suffixes (Jr, Sr...)', 'administer CiviCRM', '',   @optionListlastID, '1', NULL,  3 ),
    ( @domainID, 'civicrm/admin/options/instant_messenger_service?reset=1',            '{ts escape="sql" skip="true"}Instant Messenger Services{/ts}',     'Instant Messenger Services',       'administer CiviCRM', '',   @optionListlastID, '1', NULL,  4 ),
    ( @domainID, 'civicrm/admin/locationType?reset=1',                                                                 '{ts escape="sql" skip="true"}Location Types (Home, Work...){/ts}', 'Location Types (Home, Work...)',   'administer CiviCRM', '',   @optionListlastID, '1', NULL,  5 ),
    ( @domainID, 'civicrm/admin/options/mobile_provider?reset=1',                                '{ts escape="sql" skip="true"}Mobile Phone Providers{/ts}', 'Mobile Phone Providers',                   'administer CiviCRM', '',   @optionListlastID, '1', NULL,  6 ),
    ( @domainID, 'civicrm/admin/options/phone_type?reset=1',                                          '{ts escape="sql" skip="true"}Phone Types{/ts}',            'Phone Types',                              'administer CiviCRM', '',   @optionListlastID, '1', NULL,  7 ),
    ( @domainID, 'civicrm/admin/options/website_type?reset=1',                                      '{ts escape="sql" skip="true"}Website Types{/ts}',          'Website Types',                            'administer CiviCRM', '',   @optionListlastID, '1', NULL,  8 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Communications{/ts}', 'Communications', 'administer CiviCRM', '', @adminlastID, '1', NULL, 4 );

SET @communicationslastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/admin/domain?action=update&reset=1',         '{ts escape="sql" skip="true"}Organization Address and Contact Info{/ts}', 'Organization Address and Contact Info',                                      'administer CiviCRM', '', @communicationslastID, '1', NULL, 1 ),
    ( @domainID, 'civicrm/admin/options/from_email_address?reset=1', '{ts escape="sql" skip="true"}FROM Email Addresses{/ts}', 'FROM Email Addresses',                                                 'administer CiviCRM', '', @communicationslastID, '1', NULL, 2 ),
    ( @domainID, 'civicrm/admin/messageTemplates?reset=1',             '{ts escape="sql" skip="true"}Message Templates{/ts}',      'Message Templates',                                                                         'administer CiviCRM', '', @communicationslastID, '1', NULL, 3 ),
    ( @domainID, 'civicrm/admin/scheduleReminders?reset=1',              '{ts escape="sql" skip="true"}Schedule Reminders{/ts}',    'Schedule Reminders',                                                                       'administer CiviCRM', '', @communicationslastID, '1', NULL, 4 ),
    ( @domainID, 'civicrm/admin/options/preferred_communication_method?reset=1',  '{ts escape="sql" skip="true"}Preferred Communication Methods{/ts}', 'Preferred Communication Methods',  'administer CiviCRM', '', @communicationslastID, '1', NULL, 5 ),
    ( @domainID, 'civicrm/admin/labelFormats?reset=1',                                  '{ts escape="sql" skip="true"}Label Formats{/ts}',                 'Label Formats',                                                     'administer CiviCRM', '', @communicationslastID, '1', NULL, 6 ),
    ( @domainID, 'civicrm/admin/pdfFormats?reset=1',                                    '{ts escape="sql" skip="true"}Print Page (PDF) Formats{/ts}',      'Print Page (PDF) Formats',                                          'administer CiviCRM', '', @communicationslastID, '1', NULL, 7 ),
    ( @domainID, 'civicrm/admin/options/communication_style?reset=1',   '{ts escape="sql" skip="true"}Communication Style Options{/ts}', 'Communication Style Options',                               'administer CiviCRM', '', @communicationslastID, '1', NULL, 8 ),
    ( @domainID, 'civicrm/admin/options/email_greeting?reset=1',   '{ts escape="sql" skip="true"}Email Greeting Formats{/ts}',        'Email Greeting Formats',                                            'administer CiviCRM', '', @communicationslastID, '1', NULL, 9 ),
    ( @domainID, 'civicrm/admin/options/postal_greeting?reset=1', '{ts escape="sql" skip="true"}Postal Greeting Formats{/ts}',       'Postal Greeting Formats',                                           'administer CiviCRM', '', @communicationslastID, '1', NULL, 10 ),
    ( @domainID, 'civicrm/admin/options/addressee?reset=1',             '{ts escape="sql" skip="true"}Addressee Formats{/ts}',             'Addressee Formats',                                                 'administer CiviCRM', '', @communicationslastID, '1', NULL, 11 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Localization{/ts}', 'Localization', 'administer CiviCRM', '', @adminlastID, '1', NULL, 6 );

SET @locallastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/admin/setting/localization?reset=1',          '{ts escape="sql" skip="true"}Languages, Currency, Locations{/ts}', 'Languages, Currency, Locations',   'administer CiviCRM', '', @locallastID, '1', NULL, 1 ),
    ( @domainID, 'civicrm/admin/setting/preferences/address?reset=1',   '{ts escape="sql" skip="true"}Address Settings{/ts}',               'Address Settings',                 'administer CiviCRM', '', @locallastID, '1', NULL, 2 ),
    ( @domainID, 'civicrm/admin/setting/date?reset=1',                  '{ts escape="sql" skip="true"}Date Formats{/ts}',                   'Date Formats',                     'administer CiviCRM', '', @locallastID, '1', NULL, 3 ),
    ( @domainID, 'civicrm/admin/options/languages?reset=1', '{ts escape="sql" skip="true"}Preferred Language Options{/ts}', 'Preferred Language Options',       'administer CiviCRM', '', @locallastID, '1', NULL, 4 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, NULL,                                                  '{ts escape="sql" skip="true"}Users and Permissions{/ts}',          'Users and Permissions',            'administer CiviCRM', '', @adminlastID, '1', NULL, 7 );

SET @usersPermslastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/admin/access?reset=1',       '{ts escape="sql" skip="true"}Access Control Lists{/ts}',    'Permissions (Access Control)',     'administer CiviCRM', '', @usersPermslastID, '1', NULL, 5 ),
    ( @domainID, 'civicrm/admin/synchUser?reset=1',    '{ts escape="sql" skip="true"}Synchronize Users to Contacts{/ts}',   'Synchronize Users to Contacts',    'administer CiviCRM', '', @usersPermslastID, '1', NULL, 10 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, NULL,                                 '{ts escape="sql" skip="true"}System Settings{/ts}',                 'System Settings',                      'administer CiviCRM', '', @adminlastID, '1', NULL, 8 );

SET @systemSettingslastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES

    ( @domainID, 'civicrm/admin/setting/component?reset=1',             '{ts escape="sql" skip="true"}Components{/ts}', 'Enable Components',                                    'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 1 ),
    ( @domainID, 'civicrm/admin/extensions?reset=1',                    '{ts escape="sql" skip="true"}Extensions{/ts}', 'Manage Extensions',                                    'administer CiviCRM', '', @systemSettingslastID, '1', 1,    3 ),

    ( @domainID, 'civicrm/admin/setting/updateConfigBackend?reset=1',   '{ts escape="sql" skip="true"}Cleanup Caches and Update Paths{/ts}', 'Cleanup Caches and Update Paths', 'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 4 ),
    ( @domainID, 'civicrm/admin/setting/uf?reset=1',                    '{ts escape="sql" skip="true"}CMS Database Integration{/ts}',    'CMS Integration',                     'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 5 ),
    ( @domainID, 'civicrm/admin/setting/debug?reset=1',                 '{ts escape="sql" skip="true"}Debugging and Error Handling{/ts}','Debugging and Error Handling',        'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 6 ),
    ( @domainID, 'civicrm/admin/setting/path?reset=1',                  '{ts escape="sql" skip="true"}Directories{/ts}',        'Directories',                                  'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 7 ),
    ( @domainID, 'civicrm/admin/mapping?reset=1',                       '{ts escape="sql" skip="true"}Import/Export Mappings{/ts}', 'Import/Export Mappings',                   'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 8 ),
    ( @domainID, 'civicrm/admin/setting/mapping?reset=1',               '{ts escape="sql" skip="true"}Mapping and Geocoding{/ts}', 'Mapping and Geocoding',                     'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 9 ),
    ( @domainID, 'civicrm/admin/setting/misc?reset=1',                  '{ts escape="sql" skip="true"}Misc (Undelete, PDFs, Limits, Logging, etc.){/ts}', 'misc_admin_settings', 'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 10 ),
    ( @domainID, 'civicrm/admin/setting/preferences/multisite?reset=1', '{ts escape="sql" skip="true"}Multi Site Settings{/ts}', 'Multi Site Settings',                         'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 11 ),
    ( @domainID, 'civicrm/admin/options?reset=1',                       '{ts escape="sql" skip="true"}Option Groups{/ts}', 'Option Groups',                                     'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 12 ),
    ( @domainID, 'civicrm/admin/setting/smtp?reset=1',                  '{ts escape="sql" skip="true"}Outbound Email (SMTP/Sendmail){/ts}', 'Outbound Email',                   'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 13 ),
    ( @domainID, 'civicrm/admin/paymentProcessor?reset=1',              '{ts escape="sql" skip="true"}Payment Processors{/ts}', 'Payment Processors',                           'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 14 ),
    ( @domainID, 'civicrm/admin/setting/url?reset=1',                   '{ts escape="sql" skip="true"}Resource URLs{/ts}',      'Resource URLs',                                'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 15 ),
    ( @domainID, 'civicrm/admin/options/safe_file_extension?reset=1', '{ts escape="sql" skip="true"}Safe File Extensions{/ts}', 'Safe File Extensions','administer CiviCRM', '',@systemSettingslastID, '1', NULL, 16 ),
    ( @domainID, 'civicrm/admin/job?reset=1',                           '{ts escape="sql" skip="true"}Scheduled Jobs{/ts}', 'Scheduled Jobs',                                   'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 17 ),
    ( @domainID, 'civicrm/admin/sms/provider?reset=1', '{ts escape="sql" skip="true"}SMS Providers{/ts}', 'SMS Providers',                                  'administer CiviCRM', '', @systemSettingslastID, '1', NULL, 18 );


-- begin component admin menus
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, NULL,                                             '{ts escape="sql" skip="true"}CiviCampaign{/ts}',              'CiviCampaign',              'administer CiviCampaign,administer CiviCRM', 'AND', @adminlastID, '1', NULL, 9 );

SET @adminCampaignlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/admin/campaign/surveyType?reset=1',                            '{ts escape="sql" skip="true"}Survey Types{/ts}',  'Survey Types', 'administer CiviCampaign',    '', @adminCampaignlastID, '1', NULL, 1 ),
    ( @domainID, 'civicrm/admin/options/campaign_type?reset=1',      '{ts escape="sql" skip="true"}Campaign Types{/ts}',  'Campaign Types', 'administer CiviCampaign',    '', @adminCampaignlastID, '1', NULL, 2 ),
    ( @domainID, 'civicrm/admin/options/campaign_status?reset=1',  '{ts escape="sql" skip="true"}Campaign Status{/ts}',  'Campaign Status', 'administer CiviCampaign',    '', @adminCampaignlastID, '1', NULL, 3 ),
    ( @domainID, 'civicrm/admin/options/engagement_index?reset=1', '{ts escape="sql" skip="true"}Engagement Index{/ts}',  'Engagement Index', 'administer CiviCampaign', '', @adminCampaignlastID, '1', NULL, 4 ),
    ( @domainID, 'civicrm/admin/setting/preferences/campaign?reset=1',                   '{ts escape="sql" skip="true"}CiviCampaign Component Settings{/ts}', 'CiviCampaign Component Settings','administer CiviCampaign', '', @adminCampaignlastID, '1', NULL, 5 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, NULL,  '{ts escape="sql" skip="true"}CiviCase{/ts}', 'CiviCase', 'administer CiviCase', NULL, @adminlastID, '1', NULL, 10 );

SET @adminCaselastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/admin/setting/case?reset=1',   '{ts escape="sql" skip="true"}CiviCase Settings{/ts}',      'CiviCase Settings',      'administer CiviCase', NULL, @adminCaselastID, '1', NULL, 1 ),
    ( @domainID, 'civicrm/a/#/caseType',            '{ts escape="sql" skip="true"}Case Types{/ts}',      'Case Types',      'administer CiviCase', NULL, @adminCaselastID, '1', NULL, 2 ),
    ( @domainID, 'civicrm/admin/options/redaction_rule?reset=1',  '{ts escape="sql" skip="true"}Redaction Rules{/ts}', 'Redaction Rules', 'administer CiviCase', NULL, @adminCaselastID, '1', NULL, 3 ),
    ( @domainID, 'civicrm/admin/options/case_status?reset=1',  '{ts escape="sql" skip="true"}Case Statuses{/ts}', 'Case Statuses', 'administer CiviCase', NULL, @adminCaselastID, '1', NULL, 4 ),
    ( @domainID, 'civicrm/admin/options/encounter_medium?reset=1',  '{ts escape="sql" skip="true"}Encounter Medium{/ts}', 'Encounter Medium', 'administer CiviCase', NULL, @adminCaselastID, '1', NULL, 5 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, NULL,  '{ts escape="sql" skip="true"}CiviContribute{/ts}', 'CiviContribute', 'access CiviContribute,administer CiviCRM', 'AND', @adminlastID, '1', NULL, 11 );

SET @adminContributelastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/admin/contribute?reset=1&action=add',            '{ts escape="sql" skip="true"}New Contribution Page{/ts}',      'New Contribution Page',     'access CiviContribute,administer CiviCRM', 'AND', @adminContributelastID, '1', NULL, 6 ),
    ( @domainID, 'civicrm/admin/contribute?reset=1',                       '{ts escape="sql" skip="true"}Manage Contribution Pages{/ts}',  'Manage Contribution Pages', 'access CiviContribute,administer CiviCRM', 'AND', @adminContributelastID, '1', '1',  7 ),
    ( @domainID, 'civicrm/admin/pcp?reset=1&page_type=contribute',                              '{ts escape="sql" skip="true"}Personal Campaign Pages{/ts}',    'Personal Campaign Pages',   'access CiviContribute,administer CiviCRM', 'AND', @adminContributelastID, '1', NULL, 8 ),
    ( @domainID, 'civicrm/admin/contribute/managePremiums?reset=1',        '{ts escape="sql" skip="true"}Premiums (Thank-you Gifts){/ts}', 'Premiums',                  'access CiviContribute,administer CiviCRM', 'AND', @adminContributelastID, '1', 1,    9 ),
    ( @domainID, 'civicrm/admin/financial/financialType?reset=1',      '{ts escape="sql" skip="true"}Financial Types{/ts}',         'Financial Types',        'access CiviContribute,administer CiviCRM', 'AND', @adminContributelastID, '1', NULL, 10),
    ( @domainID, 'civicrm/admin/financial/financialAccount?reset=1',      '{ts escape="sql" skip="true"}Financial Accounts{/ts}',         'Financial Accounts',        'access CiviContribute,administer CiviCRM', 'AND', @adminContributelastID, '1', NULL, 11),
    ( @domainID, 'civicrm/admin/options/payment_instrument?reset=1',  '{ts escape="sql" skip="true"}Payment Methods{/ts}',    'Payment Instruments',   'access CiviContribute,administer CiviCRM', 'AND', @adminContributelastID, '1', NULL, 12 ),
    ( @domainID, 'civicrm/admin/options/accept_creditcard?reset=1',    '{ts escape="sql" skip="true"}Accepted Credit Cards{/ts}',  'Accepted Credit Cards', 'access CiviContribute,administer CiviCRM', 'AND', @adminContributelastID, '1', NULL, 13 ),
    ( @domainID, 'civicrm/admin/options/soft_credit_type?reset=1', '{ts escape="sql" skip="true"}Soft Credit Types{/ts}', 'Soft Credit Types', 'access CiviContribute,administer CiviCRM', 'AND', @adminContributelastID, '1', 1, 14  ),
    ( @domainID, 'civicrm/admin/price/edit?reset=1&action=add',             '{ts escape="sql" skip="true"}New Price Set{/ts}',              'New Price Set',             'access CiviContribute,administer CiviCRM', 'AND', @adminContributelastID, '0', NULL, 15 ),
    ( @domainID, 'civicrm/admin/price?reset=1',                             '{ts escape="sql" skip="true"}Manage Price Sets{/ts}',          'Manage Price Sets',         'access CiviContribute,administer CiviCRM', 'AND', @adminContributelastID, '1', NULL, 16 ),
    ( @domainID, 'civicrm/admin/paymentProcessor?reset=1',                  '{ts escape="sql" skip="true"}Payment Processors{/ts}',         'Payment Processors',        'administer CiviCRM', '',                          @adminContributelastID, '1', NULL, 17  ),
    ( @domainID, 'civicrm/admin/setting/preferences/contribute?reset=1',                  '{ts escape="sql" skip="true"}CiviContribute Component Settings{/ts}',         'CiviContribute Component Settings',        'administer CiviCRM', '',                          @adminContributelastID, '1', NULL, 18  ) ;

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, NULL, '{ts escape="sql" skip="true"}CiviEvent{/ts}', 'CiviEvent', 'access CiviEvent,administer CiviCRM', 'AND', @adminlastID, '1', NULL, 12 );

SET @adminEventlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/event/add?reset=1&action=add',                   '{ts escape="sql" skip="true"}New Event{/ts}',          'New Event',                        'access CiviEvent,administer CiviCRM', 'AND', @adminEventlastID, '1', NULL, 1 ),
    ( @domainID, 'civicrm/event/manage?reset=1',                           '{ts escape="sql" skip="true"}Manage Events{/ts}',      'Manage Events',                    'access CiviEvent,administer CiviCRM', 'AND', @adminEventlastID, '1', 1,    2 ),
    ( @domainID, 'civicrm/admin/pcp?reset=1&page_type=event',                              '{ts escape="sql" skip="true"}Personal Campaign Pages{/ts}',    'Personal Campaign Pages',   'access CiviEvent,administer CiviCRM', 'AND', @adminEventlastID, '1', 1, 3 ),
    ( @domainID, 'civicrm/admin/eventTemplate?reset=1',                    '{ts escape="sql" skip="true"}Event Templates{/ts}',    'Event Templates',                  'access CiviEvent,administer CiviCRM', 'AND', @adminEventlastID, '1', 1,    4 ),
    ( @domainID, 'civicrm/admin/price/edit?reset=1&action=add',            '{ts escape="sql" skip="true"}New Price Set{/ts}',      'New Price Set',                    'access CiviEvent,administer CiviCRM', 'AND', @adminEventlastID, '0', NULL, 5 ),
    ( @domainID, 'civicrm/admin/price?reset=1',                            '{ts escape="sql" skip="true"}Manage Price Sets{/ts}',  'Manage Price Sets',                'access CiviEvent,administer CiviCRM', 'AND', @adminEventlastID, '1', 1,    6 ),
    ( @domainID, 'civicrm/admin/options/event_type?reset=1',  '{ts escape="sql" skip="true"}Event Types{/ts}',    'Event Types',                      'access CiviEvent,administer CiviCRM', 'AND', @adminEventlastID, '1', NULL, 7 ),
    ( @domainID, 'civicrm/admin/participant_status?reset=1',                   '{ts escape="sql" skip="true"}Participant Statuses{/ts}', 'Participant Statuses',       'access CiviEvent,administer CiviCRM', 'AND', @adminEventlastID, '1', NULL, 8 ),
    ( @domainID, 'civicrm/admin/options/participant_role?reset=1', '{ts escape="sql" skip="true"}Participant Roles{/ts}', 'Participant Roles',  'access CiviEvent,administer CiviCRM', 'AND', @adminEventlastID, '1', NULL, 9 ),
    ( @domainID, 'civicrm/admin/options/participant_listing?reset=1', '{ts escape="sql" skip="true"}Participant Listing Options{/ts}', 'Participant Listing Options', 'access CiviEvent,administer CiviCRM', 'AND', @adminEventlastID, '1', NULL, 10 ),
    ( @domainID, 'civicrm/admin/badgelayout?reset=1',                       '{ts escape="sql" skip="true"}Event Name Badge Layouts{/ts}', 'Event Name Badge Layouts', 'access CiviEvent,administer CiviCRM', 'AND', @adminEventlastID, '1', NULL, 11 ),
    ( @domainID, 'civicrm/admin/paymentProcessor?reset=1',                  '{ts escape="sql" skip="true"}Payment Processors{/ts}', 'Payment Processors',              'administer CiviCRM', '',                     @adminEventlastID, '1', NULL, 12),
    ( @domainID, 'civicrm/admin/setting/preferences/event?reset=1',         '{ts escape="sql" skip="true"}CiviEvent Component Settings{/ts}', 'CiviEvent Component Settings','access CiviEvent,administer CiviCRM', 'AND', @adminEventlastID, '1', NULL, 13 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, NULL, '{ts escape="sql" skip="true"}CiviMail{/ts}', 'CiviMail', 'access CiviMail,administer CiviCRM', 'AND', @adminlastID, '1', NULL, 14 );

SET @adminMailinglastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/admin/component?reset=1',            '{ts escape="sql" skip="true"}Headers, Footers, and Automated Messages{/ts}', 'Headers, Footers, and Automated Messages', 'access CiviMail,administer CiviCRM', 'AND', @adminMailinglastID, '1', NULL, 1 ),
    ( @domainID, 'civicrm/admin/messageTemplates?reset=1',     '{ts escape="sql" skip="true"}Message Templates{/ts}', 'Message Templates', 'administer CiviCRM', '',   @adminMailinglastID, '1', NULL, 2 ),
    ( @domainID, 'civicrm/admin/options/from_email_address?reset=1', '{ts escape="sql" skip="true"}From Email Addresses{/ts}', 'From Email Addresses', 'administer CiviCRM', '', @adminMailinglastID, '1', NULL, 3 ),
    ( @domainID, 'civicrm/admin/mailSettings?reset=1',         '{ts escape="sql" skip="true"}Mail Accounts{/ts}', 'Mail Accounts', 'access CiviMail,administer CiviCRM', 'AND',           @adminMailinglastID, '1', NULL, 4 ),
    ( @domainID, 'civicrm/admin/mail?reset=1',                 '{ts escape="sql" skip="true"}Mailer Settings{/ts}', 'Mailer Settings', 'access CiviMail,administer CiviCRM', 'AND',           @adminMailinglastID, '1', NULL, 5 ),
    ( @domainID, 'civicrm/admin/setting/preferences/mailing?reset=1', '{ts escape="sql" skip="true"}CiviMail Component Settings{/ts}', 'CiviMail Component Settings','access CiviMail,administer CiviCRM', 'AND', @adminMailinglastID, '1', NULL, 6 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, NULL, '{ts escape="sql" skip="true"}CiviMember{/ts}', 'CiviMember', 'access CiviMember,administer CiviCRM', 'AND', @adminlastID, '1', NULL, 15 );

SET @adminMemberlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/admin/member/membershipType?reset=1',    '{ts escape="sql" skip="true"}Membership Types{/ts}',        'Membership Types',        'access CiviMember,administer CiviCRM', 'AND', @adminMemberlastID, '1', NULL, 1 ),
    ( @domainID, 'civicrm/admin/member/membershipStatus?reset=1',  '{ts escape="sql" skip="true"}Membership Status Rules{/ts}', 'Membership Status Rules', 'access CiviMember,administer CiviCRM', 'AND', @adminMemberlastID, '1', 1, 2 ),
    ( @domainID, 'civicrm/admin/price/edit?reset=1&action=add',    '{ts escape="sql" skip="true"}New Price Set{/ts}',           'New Price Set',           'access CiviMember,administer CiviCRM', 'AND', @adminMemberlastID, '1', NULL, 3 ),
    ( @domainID, 'civicrm/admin/price?reset=1',                    '{ts escape="sql" skip="true"}Manage Price Sets{/ts}',       'Manage Price Sets',       'access CiviMember,administer CiviCRM', 'AND', @adminMemberlastID, '1', NULL, 4 ),
    ( @domainID, 'civicrm/admin/setting/preferences/member?reset=1', '{ts escape="sql" skip="true"}CiviMember Component Settings{/ts}', 'CiviMember Component Settings','access CiviMember,administer CiviCRM', 'AND', @adminMemberlastID, '1', NULL, 5 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, NULL,                                             '{ts escape="sql" skip="true"}CiviReport{/ts}',              'CiviReport',              'access CiviReport,administer CiviCRM', 'AND', @adminlastID, '1', NULL, 16 );

SET @adminReportlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/report/list?reset=1',                            '{ts escape="sql" skip="true"}All Reports{/ts}',  'All Reports', 'access CiviReport',    '', @adminReportlastID, '1', NULL, 1 ),
    ( @domainID, 'civicrm/admin/report/template/list?reset=1',             '{ts escape="sql" skip="true"}Create New Report from Template{/ts}', 'Create New Report from Template', 'administer Reports', '', @adminReportlastID, '1', NULL, 2 ),
    ( @domainID, 'civicrm/admin/report/options/report_template?reset=1',   '{ts escape="sql" skip="true"}Manage Templates{/ts}', 'Manage Templates', 'administer Reports',  '', @adminReportlastID, '1', NULL, 3 ),
    ( @domainID, 'civicrm/admin/report/register?reset=1',                  '{ts escape="sql" skip="true"}Register Report{/ts}',  'Register Report',  'administer Reports',  '', @adminReportlastID, '1', NULL, 4 );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight, icon )
VALUES
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Support{/ts}', 'Support', NULL, '',  NULL, '1', NULL, 110, 'crm-i fa-life-ring');

SET @adminHelplastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'https://docs.civicrm.org/user/?src=iam',                                 '{ts escape="sql" skip="true"}User Guide{/ts}',         'User Guide',         NULL, 'AND', @adminHelplastID, '1', NULL, 1 ),
    ( @domainID, 'https://civicrm.org/help?src=iam',                                       '{ts escape="sql" skip="true"}Get Help{/ts}',           'Get Help',           NULL, 'AND', @adminHelplastID, '1', NULL, 2 ),
    ( @domainID, 'https://civicrm.org/about?src=iam', 		                                 '{ts escape="sql" skip="true"}About CiviCRM{/ts}',      'About CiviCRM',      NULL, 'AND', @adminHelplastID, '1', 1,    3 ),
    ( @domainID, 'https://civicrm.org/register-your-site?src=iam&sid={ldelim}sid{rdelim}', '{ts escape="sql" skip="true"}Register Your Site{/ts}', 'Register Your Site', NULL, 'AND', @adminHelplastID, '1', NULL, 4 ),
    ( @domainID, 'https://civicrm.org/become-a-member?src=iam&sid={ldelim}sid{rdelim}',    '{ts escape="sql" skip="true"}Join CiviCRM{/ts}',       'Join CiviCRM',       NULL, 'AND', @adminHelplastID, '1', NULL, 5 );

INSERT INTO civicrm_navigation
( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Developer{/ts}', 'Developer', 'administer CiviCRM', '', @adminHelplastID, '1', 1, 6 );

SET @devellastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
( @domainID, 'civicrm/api3', '{ts escape="sql" skip="true"}Api Explorer v3{/ts}', 'API Explorer', 'administer CiviCRM', '', @devellastID, '1', NULL, 1 ),
( @domainID, 'civicrm/api4#/explorer', '{ts escape="sql" skip="true"}Api Explorer v4{/ts}', 'Api Explorer v4', 'administer CiviCRM', '', @devellastID, '1', NULL, 2 ),
( @domainID, 'https://civicrm.org/developer-documentation?src=iam', '{ts escape="sql" skip="true"}Developer Docs{/ts}', 'Developer Docs', 'administer CiviCRM', '', @devellastID, '1', NULL, 3 );

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight, icon )
VALUES
    ( @domainID, NULL, '{ts escape="sql" skip="true"}Reports{/ts}', 'Reports', 'access CiviReport', '',  NULL, '1', NULL, 95, 'crm-i fa-bar-chart' );

SET @reportlastID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/report/list?compid=99&reset=1', '{ts escape="sql" skip="true"}Contact Reports{/ts}', 'Contact Reports', 'access CiviReport', '', @reportlastID, '1', 0,    1 );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/report/list?compid=2&reset=1', '{ts escape="sql" skip="true"}Contribution Reports{/ts}', 'Contribution Reports', 'access CiviContribute', '', @reportlastID, '1', 0,    2 );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/report/list?compid=6&reset=1', '{ts escape="sql" skip="true"}Pledge Reports{/ts}', 'Pledge Reports', 'access CiviPledge', '', @reportlastID, '1', 0,    3 );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/report/list?compid=1&reset=1', '{ts escape="sql" skip="true"}Event Reports{/ts}', 'Event Reports', 'access CiviEvent', '', @reportlastID, '1', 0,    4 );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/report/list?compid=4&reset=1', '{ts escape="sql" skip="true"}Mailing Reports{/ts}', 'Mailing Reports', 'access CiviMail', '', @reportlastID, '1', 0,    5 );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/report/list?compid=3&reset=1', '{ts escape="sql" skip="true"}Membership Reports{/ts}', 'Membership Reports', 'access CiviMember', '', @reportlastID, '1', 0,    6 );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/report/list?compid=9&reset=1', '{ts escape="sql" skip="true"}Campaign Reports{/ts}', 'Campaign Reports', 'interview campaign contacts,release campaign contacts,reserve campaign contacts,manage campaign,administer CiviCampaign,gotv campaign contacts', 'OR', @reportlastID, '1', 0,    7 );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/report/list?compid=7&reset=1', '{ts escape="sql" skip="true"}Case Reports{/ts}', 'Case Reports', 'access my cases and activities,access all cases and activities,administer CiviCase', 'OR', @reportlastID, '1', 0,    8 );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domainID, 'civicrm/report/list?reset=1', '{ts escape="sql" skip="true"}All Reports{/ts}', 'All Reports', 'access CiviReport', '', @reportlastID, '1', 1,    10 ),
( @domainID, 'civicrm/report/list?myreports=1&reset=1',                '{ts escape="sql" skip="true"}My Reports{/ts}', 'My Reports', 'access CiviReport', '', @reportlastID, '1', 1, 11);

-- sample report instances

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    (  @domainID, '{ts escape="sql" skip="true"}Constituent Summary{/ts}', 'contact/summary', '{ts escape="sql" skip="true"}Provides a list of address and telephone information for constituent records in your system.{/ts}', 'view all contacts', '{literal}a:31:{s:6:"fields";a:4:{s:9:"sort_name";s:1:"1";s:14:"street_address";s:1:"1";s:4:"city";s:1:"1";s:10:"country_id";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:9:"source_op";s:3:"has";s:12:"source_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"custom_1_op";s:2:"in";s:14:"custom_1_value";a:0:{}s:11:"custom_2_op";s:2:"in";s:14:"custom_2_value";a:0:{}s:17:"custom_3_relative";s:1:"0";s:13:"custom_3_from";s:0:"";s:11:"custom_3_to";s:0:"";s:11:"description";s:92:"Provides a list of address and telephone information for constituent records in your system.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"view all contacts";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Constituent Detail{/ts}', 'contact/detail', '{ts escape="sql" skip="true"}Provides contact-related information on contributions, memberships, events and activities.{/ts}', 'view all contacts', '{literal}a:25:{s:6:"fields";a:30:{s:9:"sort_name";s:1:"1";s:10:"country_id";s:1:"1";s:15:"contribution_id";s:1:"1";s:12:"total_amount";s:1:"1";s:17:"financial_type_id";s:1:"1";s:12:"receive_date";s:1:"1";s:22:"contribution_status_id";s:1:"1";s:13:"membership_id";s:1:"1";s:18:"membership_type_id";s:1:"1";s:21:"membership_start_date";s:1:"1";s:19:"membership_end_date";s:1:"1";s:20:"membership_status_id";s:1:"1";s:14:"participant_id";s:1:"1";s:8:"event_id";s:1:"1";s:21:"participant_status_id";s:1:"1";s:7:"role_id";s:1:"1";s:25:"participant_register_date";s:1:"1";s:9:"fee_level";s:1:"1";s:10:"fee_amount";s:1:"1";s:15:"relationship_id";s:1:"1";s:20:"relationship_type_id";s:1:"1";s:12:"contact_id_b";s:1:"1";s:2:"id";s:1:"1";s:16:"activity_type_id";s:1:"1";s:7:"subject";s:1:"1";s:17:"source_contact_id";s:1:"1";s:18:"activity_date_time";s:1:"1";s:18:"activity_status_id";s:1:"1";s:17:"target_contact_id";s:1:"1";s:19:"assignee_contact_id";s:1:"1";}s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"custom_1_op";s:2:"in";s:14:"custom_1_value";a:0:{}s:11:"custom_2_op";s:2:"in";s:14:"custom_2_value";a:0:{}s:17:"custom_3_relative";s:1:"0";s:13:"custom_3_from";s:0:"";s:11:"custom_3_to";s:0:"";s:11:"description";s:90:"Provides contact-related information on contributions, memberships, events and activities.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"view all contacts";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Activity Details{/ts}', 'activity', '{ts escape="sql" skip="true"}Provides a list of constituent activity including activity statistics for one/all contacts during a given date range(required){/ts}', 'view all contacts', '{literal}a:26:{s:6:"fields";a:6:{s:16:"contact_assignee";s:1:"1";s:14:"contact_target";s:1:"1";s:16:"activity_type_id";s:1:"1";s:16:"activity_subject";s:1:"1";s:18:"activity_date_time";s:1:"1";s:9:"status_id";s:1:"1";}s:17:"contact_source_op";s:3:"has";s:20:"contact_source_value";s:0:"";s:19:"contact_assignee_op";s:3:"has";s:22:"contact_assignee_value";s:0:"";s:17:"contact_target_op";s:3:"has";s:20:"contact_target_value";s:0:"";s:15:"current_user_op";s:2:"eq";s:18:"current_user_value";s:1:"0";s:27:"activity_date_time_relative";s:10:"this.month";s:23:"activity_date_time_from";s:0:"";s:21:"activity_date_time_to";s:0:"";s:19:"activity_subject_op";s:3:"has";s:22:"activity_subject_value";s:0:"";s:19:"activity_type_id_op";s:2:"in";s:22:"activity_type_id_value";a:0:{}s:12:"status_id_op";s:2:"in";s:15:"status_id_value";a:0:{}s:11:"description";s:126:"Provides a list of constituent activity including activity statistics for one/all contacts during a given date range(required)";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"view all contacts";s:6:"groups";s:0:"";s:9:"group_bys";N;s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    (`domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Current Employers{/ts}', 'contact/currentEmployer', '{ts escape="sql" skip="true"}Provides detail list of employer employee relationships along with employment details.{/ts}', 'view all contacts', '{literal}a:33:{s:6:"fields";a:5:{s:17:"organization_name";s:1:"1";s:9:"sort_name";s:1:"1";s:9:"job_title";s:1:"1";s:10:"start_date";s:1:"1";s:5:"email";s:1:"1";}s:20:"organization_name_op";s:3:"has";s:23:"organization_name_value";s:0:"";s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:19:"start_date_relative";s:1:"0";s:15:"start_date_from";s:0:"";s:13:"start_date_to";s:0:"";s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"custom_1_op";s:2:"in";s:14:"custom_1_value";a:0:{}s:11:"custom_2_op";s:2:"in";s:14:"custom_2_value";a:0:{}s:17:"custom_3_relative";s:1:"0";s:13:"custom_3_from";s:0:"";s:11:"custom_3_to";s:0:"";s:11:"description";s:98:"Provides detail list of employer employee relationships along with employment details Ex Join Date";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"view all contacts";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Relationships{/ts}', 'contact/relationship', '{ts escape="sql" skip="true"}Gives relationship details between two contacts{/ts}', 'view all contacts', '{literal}a:28:{s:6:"fields";a:4:{s:11:"sort_name_a";s:1:"1";s:11:"sort_name_b";s:1:"1";s:9:"label_a_b";s:1:"1";s:9:"label_b_a";s:1:"1";}s:14:"sort_name_a_op";s:3:"has";s:17:"sort_name_a_value";s:0:"";s:14:"sort_name_b_op";s:3:"has";s:17:"sort_name_b_value";s:0:"";s:17:"contact_type_a_op";s:2:"in";s:20:"contact_type_a_value";a:0:{}s:17:"contact_type_b_op";s:2:"in";s:20:"contact_type_b_value";a:0:{}s:12:"is_active_op";s:2:"eq";s:15:"is_active_value";s:0:"";s:23:"relationship_type_id_op";s:2:"eq";s:26:"relationship_type_id_value";s:0:"";s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:19:"Relationship Report";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"view all contacts";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Activity Summary{/ts}', 'activitySummary', '{ts escape="sql" skip="true"}Shows activity statistics by type / date{/ts}', 'view all contacts', '{literal}a:26:{s:6:"fields";a:4:{s:16:"activity_type_id";s:1:"1";s:9:"status_id";s:1:"1";s:8:"duration";s:1:"1";s:2:"id";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:27:"activity_date_time_relative";s:0:"";s:23:"activity_date_time_from";s:0:"";s:21:"activity_date_time_to";s:0:"";s:19:"activity_type_id_op";s:2:"in";s:22:"activity_type_id_value";a:0:{}s:12:"status_id_op";s:2:"in";s:15:"status_id_value";a:0:{}s:14:"priority_id_op";s:2:"in";s:17:"priority_id_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:9:"group_bys";a:2:{s:16:"activity_type_id";s:1:"1";s:9:"status_id";s:1:"1";}s:14:"group_bys_freq";a:1:{s:18:"activity_date_time";s:5:"MONTH";}s:9:"order_bys";a:1:{i:1;a:2:{s:6:"column";s:16:"activity_type_id";s:5:"order";s:3:"ASC";}}s:11:"description";s:40:"Shows activity statistics by type / date";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:9:"row_count";s:0:"";s:10:"permission";s:17:"view all contacts";s:9:"parent_id";s:0:"";s:6:"groups";s:0:"";s:11:"instance_id";N;}{/literal}');

-- Contribution reports
INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Contribution Summary{/ts}', 'contribute/summary', '{ts escape="sql" skip="true"}Groups and totals contributions by criteria including contact, time period, contribution type, contributor location, etc.{/ts}', 'access CiviContribute', '{literal}a:42:{s:6:"fields";a:1:{s:12:"total_amount";s:1:"1";}s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:20:"financial_type_id_op";s:2:"in";s:23:"financial_type_id_value";a:0:{}s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:13:"total_sum_min";s:0:"";s:13:"total_sum_max";s:0:"";s:12:"total_sum_op";s:3:"lte";s:15:"total_sum_value";s:0:"";s:15:"total_count_min";s:0:"";s:15:"total_count_max";s:0:"";s:14:"total_count_op";s:3:"lte";s:17:"total_count_value";s:0:"";s:13:"total_avg_min";s:0:"";s:13:"total_avg_max";s:0:"";s:12:"total_avg_op";s:3:"lte";s:15:"total_avg_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:9:"group_bys";a:1:{s:12:"receive_date";s:1:"1";}s:14:"group_bys_freq";a:1:{s:12:"receive_date";s:5:"MONTH";}s:11:"description";s:80:"Shows contribution statistics by month / week / year .. country / state .. type.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:6:"groups";s:0:"";s:6:"charts";s:0:"";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Contribution Details{/ts}', 'contribute/detail', '{ts escape="sql" skip="true"}Lists specific contributions by criteria including contact, time period, contribution type, contributor location, etc. Contribution summary report points to this report for contribution details.{/ts}', 'access CiviContribute', '{literal}a:56:{s:6:"fields";a:7:{s:9:"sort_name";s:1:"1";s:5:"email";s:1:"1";s:5:"phone";s:1:"1";s:17:"financial_type_id";s:1:"1";s:12:"receive_date";s:1:"1";s:12:"total_amount";s:1:"1";s:10:"country_id";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:21:"receive_date_relative";s:0:"";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:20:"financial_type_id_op";s:2:"in";s:23:"financial_type_id_value";a:0:{}s:24:"payment_instrument_id_op";s:2:"in";s:27:"payment_instrument_id_value";a:0:{}s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:13:"ordinality_op";s:2:"in";s:16:"ordinality_value";a:0:{}s:7:"note_op";s:3:"has";s:10:"note_value";s:0:"";s:17:"street_number_min";s:0:"";s:17:"street_number_max";s:0:"";s:16:"street_number_op";s:3:"lte";s:19:"street_number_value";s:0:"";s:14:"street_name_op";s:3:"has";s:17:"street_name_value";s:0:"";s:15:"postal_code_min";s:0:"";s:15:"postal_code_max";s:0:"";s:14:"postal_code_op";s:3:"lte";s:17:"postal_code_value";s:0:"";s:7:"city_op";s:3:"has";s:10:"city_value";s:0:"";s:12:"county_id_op";s:2:"in";s:15:"county_id_value";a:0:{}s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:9:"order_bys";a:1:{i:1;a:2:{s:6:"column";s:9:"sort_name";s:5:"order";s:3:"ASC";}}s:11:"description";s:194:"Lists specific contributions by criteria including contact, time period, contribution type, contributor location, etc. Contribution summary report points to this report for contribution details.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:9:"parent_id";s:0:"";s:6:"groups";s:0:"";s:9:"domain_id";i:1;s:11:"is_reserved";b:0;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Repeat Contributions{/ts}', 'contribute/repeat', '{ts escape="sql" skip="true"}Given two date ranges, shows contacts who contributed in both the date ranges with the amount contributed in each and the percentage increase / decrease.{/ts}', 'access CiviContribute', '{literal}a:29:{s:6:"fields";a:3:{s:9:"sort_name";s:1:"1";s:13:"total_amount1";s:1:"1";s:13:"total_amount2";s:1:"1";}s:22:"receive_date1_relative";s:13:"previous.year";s:18:"receive_date1_from";s:0:"";s:16:"receive_date1_to";s:0:"";s:22:"receive_date2_relative";s:9:"this.year";s:18:"receive_date2_from";s:0:"";s:16:"receive_date2_to";s:0:"";s:17:"total_amount1_min";s:0:"";s:17:"total_amount1_max";s:0:"";s:16:"total_amount1_op";s:3:"lte";s:19:"total_amount1_value";s:0:"";s:17:"total_amount2_min";s:0:"";s:17:"total_amount2_max";s:0:"";s:16:"total_amount2_op";s:3:"lte";s:19:"total_amount2_value";s:0:"";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:9:"group_bys";a:1:{s:2:"id";s:1:"1";}s:11:"description";s:140:"Given two date ranges, shows contacts (and their contributions) who contributed in both the date ranges with percentage increase / decrease.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}SYBUNT (some year but not this year){/ts}', 'contribute/sybunt', '{ts escape="sql" skip="true"}Some year(s) but not this year. Provides a list of constituents who donated at some time in the history of your organization but did not donate during the time period you specify.{/ts}', 'access CiviContribute', '{literal}a:16:{s:6:"fields";a:3:{s:9:"sort_name";s:1:"1";s:5:"email";s:1:"1";s:5:"phone";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:179:"Some year(s) but not this year. Provides a list of constituents who donated at some time in the history of your organization but did not donate during the time period you specify.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:6:"charts";s:0:"";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}LYBUNT (last year but not this year){/ts}', 'contribute/lybunt', '{ts escape="sql" skip="true"}Last year but not this year. Provides a list of constituents who donated last year but did not donate during the time period you specify as the current year.{/ts}', 'access CiviContribute', '{literal}a:17:{s:6:"fields";a:5:{s:9:"sort_name";s:1:"1";s:5:"email";s:1:"1";s:5:"phone";s:1:"1";s:22:"last_year_total_amount";s:1:"1";s:23:"civicrm_life_time_total";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:157:"Last year but not this year. Provides a list of constituents who donated last year but did not donate during the time period you specify as the current year.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:6:"groups";s:0:"";s:6:"charts";s:0:"";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Contributions by Organization{/ts}', 'contribute/organizationSummary', '{ts escape="sql" skip="true"}Displays a detailed list of contributions grouped by organization, which includes contributions made by employees for the organisation.{/ts}', 'access CiviContribute', '{literal}a:20:{s:6:"fields";a:5:{s:17:"organization_name";s:1:"1";s:9:"sort_name";s:1:"1";s:12:"total_amount";s:1:"1";s:22:"contribution_status_id";s:1:"1";s:12:"receive_date";s:1:"1";}s:20:"organization_name_op";s:3:"has";s:23:"organization_name_value";s:0:"";s:23:"relationship_type_id_op";s:2:"eq";s:26:"relationship_type_id_value";s:5:"4_b_a";s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:11:"description";s:193:"Displays a detailed contribution report for Organization relationships with contributors, as to if contribution done was  from an employee of some organization or from that Organization itself.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Contributions by Household{/ts}', 'contribute/householdSummary', '{ts escape="sql" skip="true"}Displays a detailed list of contributions grouped by household which includes contributions made by members of the household.{/ts}', 'access CiviContribute', '{literal}a:21:{s:6:"fields";a:5:{s:14:"household_name";s:1:"1";s:9:"sort_name";s:1:"1";s:12:"total_amount";s:1:"1";s:22:"contribution_status_id";s:1:"1";s:12:"receive_date";s:1:"1";}s:17:"household_name_op";s:3:"has";s:20:"household_name_value";s:0:"";s:23:"relationship_type_id_op";s:2:"eq";s:26:"relationship_type_id_value";s:5:"6_b_a";s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:11:"description";s:213:"Provides a detailed report for Contributions made by contributors(Or Household itself) who are having a relationship with household (For ex a Contributor is Head of Household for some household or is a member of.)";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Top Donors{/ts}', 'contribute/topDonor', '{ts escape="sql" skip="true"}Provides a list of the top donors during a time period you define. You can include as many donors as you want (for example, top 100 of your donors).{/ts}', 'access CiviContribute', '{literal}a:20:{s:6:"fields";a:2:{s:12:"display_name";s:1:"1";s:12:"total_amount";s:1:"1";}s:21:"receive_date_relative";s:9:"this.year";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:15:"total_range_min";s:0:"";s:15:"total_range_max";s:0:"";s:14:"total_range_op";s:2:"eq";s:17:"total_range_value";s:0:"";s:20:"financial_type_id_op";s:2:"in";s:23:"financial_type_id_value";a:0:{}s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:11:"description";s:148:"Provides a list of the top donors during a time period you define. You can include as many donors as you want (for example, top 100 of your donors).";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:6:"groups";s:0:"";}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Soft Credits{/ts}', 'contribute/softcredit', '{ts escape="sql" skip="true"}Shows contributions made by contacts that have been soft-credited to other contacts.{/ts}', 'access CiviContribute', '{literal}a:23:{s:6:"fields";a:5:{s:21:"display_name_creditor";s:1:"1";s:24:"display_name_constituent";s:1:"1";s:14:"email_creditor";s:1:"1";s:14:"phone_creditor";s:1:"1";s:6:"amount";s:1:"1";}s:5:"id_op";s:2:"in";s:8:"id_value";a:0:{}s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:10:"amount_min";s:0:"";s:10:"amount_max";s:0:"";s:9:"amount_op";s:3:"lte";s:12:"amount_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:20:"Soft Credit details.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    (@domainID,'{ts escape="sql" skip="true"}Contribution Aggregate by Relationship{/ts}', 'contribute/history','{ts escape="sql" skip="true"}List contact's donation history, grouped by year, along with contributions attributed to any of the contact's related contacts.{/ts}', 'access CiviContribute','{literal}a:41:{s:6:"fields";a:7:{s:9:"sort_name";s:1:"1";s:20:"relationship_type_id";s:1:"1";s:17:"civicrm_upto_2009";s:1:"1";i:2010;s:1:"1";i:2011;s:1:"1";i:2012;s:1:"1";i:2013;s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:23:"relationship_type_id_op";s:2:"in";s:26:"relationship_type_id_value";a:0:{}s:12:"this_year_op";s:2:"eq";s:15:"this_year_value";s:0:"";s:13:"other_year_op";s:2:"eq";s:16:"other_year_value";s:0:"";s:21:"receive_date_relative";s:0:"";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:20:"financial_type_id_op";s:2:"in";s:23:"financial_type_id_value";a:0:{}s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:13:"total_sum_min";s:0:"";s:13:"total_sum_max";s:0:"";s:12:"total_sum_op";s:3:"lte";s:15:"total_sum_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:127:"List contact''s donation history, grouped by year, along with contributions attributed to any of the contact''s related contacts.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:9:"parent_id";s:0:"";s:6:"groups";s:0:"";s:9:"domain_id";i:1;s:11:"is_reserved";b:0;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Personal Campaign Page Summary{/ts}', 'contribute/pcp', '{ts escape="sql" skip="true"}Summarizes amount raised and number of contributors for each Personal Campaign Page.{/ts}', 'access CiviContribute', '{literal}a:22:{s:6:"fields";a:8:{s:9:"sort_name";s:1:"1";s:10:"page_title";s:1:"1";s:5:"title";s:1:"1";s:11:"goal_amount";s:1:"1";s:8:"amount_1";s:1:"1";s:8:"amount_2";s:1:"1";s:7:"soft_id";s:1:"1";s:12:"receive_date";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:13:"page_title_op";s:3:"has";s:16:"page_title_value";s:0:"";s:8:"title_op";s:3:"has";s:11:"title_value";s:0:"";s:12:"amount_2_min";s:0:"";s:12:"amount_2_max";s:0:"";s:11:"amount_2_op";s:3:"lte";s:14:"amount_2_value";s:0:"";s:11:"description";s:35:"Shows Personal Campaign Page Report";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Pledge Detail{/ts}', 'pledge/detail', '{ts escape="sql" skip="true"}List of pledges including amount pledged, pledge status, next payment date, balance due, total amount paid etc.{/ts}', 'access CiviPledge', '{literal}a:27:{s:6:"fields";a:4:{s:9:"sort_name";s:1:"1";s:10:"country_id";s:1:"1";s:6:"amount";s:1:"1";s:9:"status_id";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:27:"pledge_create_date_relative";s:1:"0";s:23:"pledge_create_date_from";s:0:"";s:21:"pledge_create_date_to";s:0:"";s:17:"pledge_amount_min";s:0:"";s:17:"pledge_amount_max";s:0:"";s:16:"pledge_amount_op";s:3:"lte";s:19:"pledge_amount_value";s:0:"";s:6:"sid_op";s:2:"in";s:9:"sid_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:13:"Pledge Report";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"access CiviPledge";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Pledged But not Paid{/ts}', 'pledge/pbnp', '{ts escape="sql" skip="true"}Pledged but not Paid Report{/ts}', 'access CiviPledge', '{literal}a:17:{s:6:"fields";a:5:{s:9:"sort_name";s:1:"1";s:18:"pledge_create_date";s:1:"1";s:6:"amount";s:1:"1";s:14:"scheduled_date";s:1:"1";s:10:"country_id";s:1:"1";}s:27:"pledge_create_date_relative";s:1:"0";s:23:"pledge_create_date_from";s:0:"";s:21:"pledge_create_date_to";s:0:"";s:20:"financial_type_id_op";s:2:"in";s:23:"financial_type_id_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:27:"Pledged but not Paid Report";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"access CiviPledge";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Bookkeeping Transactions{/ts}', 'contribute/bookkeeping', '{ts escape="sql" skip="true"}Provides transaction details for all contributions and payments, including Transaction #, Invoice ID, Payment Instrument and Check #.{/ts}', 'access CiviContribute', '{literal}a:40:{s:6:"fields";a:12:{s:9:"sort_name";s:1:"1";s:21:"debit_accounting_code";s:1:"1";s:22:"credit_accounting_code";s:1:"1";s:17:"financial_type_id";s:1:"1";s:12:"receive_date";s:1:"1";s:22:"contribution_status_id";s:1:"1";s:2:"id";s:1:"1";s:12:"check_number";s:1:"1";s:21:"payment_instrument_id";s:1:"1";s:9:"trxn_date";s:1:"1";s:7:"trxn_id";s:1:"1";s:6:"amount";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:24:"debit_accounting_code_op";s:2:"in";s:27:"debit_accounting_code_value";a:0:{}s:25:"credit_accounting_code_op";s:2:"in";s:28:"credit_accounting_code_value";a:0:{}s:13:"debit_name_op";s:2:"in";s:16:"debit_name_value";a:0:{}s:14:"credit_name_op";s:2:"in";s:17:"credit_name_value";a:0:{}s:20:"financial_type_id_op";s:2:"in";s:23:"financial_type_id_value";a:0:{}s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"1";}s:24:"payment_instrument_id_op";s:2:"in";s:27:"payment_instrument_id_value";a:0:{}s:18:"trxn_date_relative";s:1:"0";s:14:"trxn_date_from";s:0:"";s:12:"trxn_date_to";s:0:"";s:10:"amount_min";s:0:"";s:10:"amount_max";s:0:"";s:9:"amount_op";s:3:"lte";s:12:"amount_value";s:0:"";s:11:"description";s:133:"Provides transaction details for all contributions and payments, including Transaction #, Invoice ID, Payment Instrument and Check #.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:21:"access CiviContribute";s:9:"parent_id";s:0:"";s:6:"groups";s:0:"";s:9:"domain_id";i:1;s:11:"is_reserved";b:0;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Recurring Contributions{/ts}', 'contribute/recur', '{ts escape="sql" skip="true"}Provides information about the status of recurring contributions{/ts}', 'access CiviContribute', '{literal}a:39:{s:6:"fields";a:7:{s:9:"sort_name";s:1:"1";s:6:"amount";s:1:"1";s:22:"contribution_status_id";s:1:"1";s:18:"frequency_interval";s:1:"1";s:14:"frequency_unit";s:1:"1";s:12:"installments";s:1:"1";s:8:"end_date";s:1:"1";}s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:1:{i:0;s:1:"5";}s:11:"currency_op";s:2:"in";s:14:"currency_value";a:0:{}s:20:"financial_type_id_op";s:2:"in";s:23:"financial_type_id_value";a:0:{}s:17:"frequency_unit_op";s:2:"in";s:20:"frequency_unit_value";a:0:{}s:22:"frequency_interval_min";s:0:"";s:22:"frequency_interval_max";s:0:"";s:21:"frequency_interval_op";s:3:"lte";s:24:"frequency_interval_value";s:0:"";s:16:"installments_min";s:0:"";s:16:"installments_max";s:0:"";s:15:"installments_op";s:3:"lte";s:18:"installments_value";s:0:"";s:19:"start_date_relative";s:0:"";s:15:"start_date_from";s:0:"";s:13:"start_date_to";s:0:"";s:37:"next_sched_contribution_date_relative";s:0:"";s:33:"next_sched_contribution_date_from";s:0:"";s:31:"next_sched_contribution_date_to";s:0:"";s:17:"end_date_relative";s:0:"";s:13:"end_date_from";s:0:"";s:11:"end_date_to";s:0:"";s:28:"calculated_end_date_relative";s:0:"";s:24:"calculated_end_date_from";s:0:"";s:22:"calculated_end_date_to";s:0:"";s:9:"order_bys";a:1:{i:1;a:1:{s:6:"column";s:1:"-";}}s:11:"description";s:41:"Shows all pending recurring contributions";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:9:"row_count";s:0:"";s:14:"addToDashboard";s:1:"1";s:10:"permission";s:21:"access CiviContribute";s:9:"parent_id";s:0:"";s:11:"instance_id";N;}{/literal}');

-- Membership reports
INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Membership Summary{/ts}', 'member/summary', '{ts escape="sql" skip="true"}Provides a summary of memberships by Type and Member Since.{/ts}', 'access CiviMember', '{literal}a:18:{s:6:"fields";a:2:{s:18:"membership_type_id";s:1:"1";s:12:"total_amount";s:1:"1";}s:29:"membership_join_date_relative";s:1:"0";s:25:"membership_join_date_from";s:0:"";s:23:"membership_join_date_to";s:0:"";s:21:"membership_type_id_op";s:2:"in";s:24:"membership_type_id_value";a:0:{}s:12:"status_id_op";s:2:"in";s:15:"status_id_value";a:0:{}s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:0:{}s:9:"group_bys";a:2:{s:9:"join_date";s:1:"1";s:18:"membership_type_id";s:1:"1";}s:14:"group_bys_freq";a:1:{s:9:"join_date";s:5:"MONTH";}s:11:"description";s:59:"Provides a summary of memberships by Type and Member Since.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"access CiviMember";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Membership Details{/ts}', 'member/detail', '{ts escape="sql" skip="true"}Provides a list of members along with their Membership Status and membership details (Member Since, Membership Start Date, Membership Expiration Date). Can also display contributions (payments) associated with each membership.{/ts}', 'access CiviMember', '{literal}a:28:{s:6:"fields";a:5:{s:9:"sort_name";s:1:"1";s:18:"membership_type_id";s:1:"1";s:21:"membership_start_date";s:1:"1";s:19:"membership_end_date";s:1:"1";s:4:"name";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:29:"membership_join_date_relative";s:1:"0";s:25:"membership_join_date_from";s:0:"";s:23:"membership_join_date_to";s:0:"";s:23:"owner_membership_id_min";s:0:"";s:23:"owner_membership_id_max";s:0:"";s:22:"owner_membership_id_op";s:3:"lte";s:25:"owner_membership_id_value";s:0:"";s:6:"tid_op";s:2:"in";s:9:"tid_value";a:0:{}s:6:"sid_op";s:2:"in";s:9:"sid_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:151:"Provides a list of members along with their Membership Status and membership details (Member Since, Membership Start Date, Membership Expiration Date).";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"access CiviMember";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Contribution and Membership Details{/ts}', 'member/contributionDetail', '{ts escape="sql" skip="true"}Contribution details for any type of contribution, plus associated membership information for contributions which are in payment for memberships.{/ts}', 'access CiviMember', '{literal}a:67:{s:6:"fields";a:12:{s:9:"sort_name";s:1:"1";s:5:"email";s:1:"1";s:5:"phone";s:1:"1";s:17:"financial_type_id";s:1:"1";s:12:"receive_date";s:1:"1";s:12:"total_amount";s:1:"1";s:18:"membership_type_id";s:1:"1";s:21:"membership_start_date";s:1:"1";s:19:"membership_end_date";s:1:"1";s:9:"join_date";s:1:"1";s:22:"membership_status_name";s:1:"1";s:10:"country_id";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:20:"financial_type_id_op";s:2:"in";s:23:"financial_type_id_value";a:0:{}s:24:"payment_instrument_id_op";s:2:"in";s:27:"payment_instrument_id_value";a:0:{}s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:0:{}s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:13:"ordinality_op";s:2:"in";s:16:"ordinality_value";a:0:{}s:29:"membership_join_date_relative";s:1:"0";s:25:"membership_join_date_from";s:0:"";s:23:"membership_join_date_to";s:0:"";s:30:"membership_start_date_relative";s:1:"0";s:26:"membership_start_date_from";s:0:"";s:24:"membership_start_date_to";s:0:"";s:28:"membership_end_date_relative";s:1:"0";s:24:"membership_end_date_from";s:0:"";s:22:"membership_end_date_to";s:0:"";s:23:"owner_membership_id_min";s:0:"";s:23:"owner_membership_id_max";s:0:"";s:22:"owner_membership_id_op";s:3:"lte";s:25:"owner_membership_id_value";s:0:"";s:6:"tid_op";s:2:"in";s:9:"tid_value";a:0:{}s:6:"sid_op";s:2:"in";s:9:"sid_value";a:0:{}s:17:"street_number_min";s:0:"";s:17:"street_number_max";s:0:"";s:16:"street_number_op";s:3:"lte";s:19:"street_number_value";s:0:"";s:14:"street_name_op";s:3:"has";s:17:"street_name_value";s:0:"";s:15:"postal_code_min";s:0:"";s:15:"postal_code_max";s:0:"";s:14:"postal_code_op";s:3:"lte";s:17:"postal_code_value";s:0:"";s:7:"city_op";s:3:"has";s:10:"city_value";s:0:"";s:12:"county_id_op";s:2:"in";s:15:"county_id_value";a:0:{}s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:35:"Contribution and Membership Details";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:1:"0";s:9:"domain_id";i:1;}{s:6:"fields";a:12:{s:9:"sort_name";s:1:"1";s:5:"email";s:1:"1";s:5:"phone";s:1:"1";s:17:"financial_type_id";s:1:"1";s:12:"receive_date";s:1:"1";s:12:"total_amount";s:1:"1";s:18:"membership_type_id";s:1:"1";s:21:"membership_start_date";s:1:"1";s:19:"membership_end_date";s:1:"1";s:9:"join_date";s:1:"1";s:22:"membership_status_name";s:1:"1";s:10:"country_id";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:20:"financial_type_id_op";s:2:"in";s:23:"financial_type_id_value";a:0:{}s:24:"payment_instrument_id_op";s:2:"in";s:27:"payment_instrument_id_value";a:0:{}s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:0:{}s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:13:"ordinality_op";s:2:"in";s:16:"ordinality_value";a:0:{}s:29:"membership_join_date_relative";s:1:"0";s:25:"membership_join_date_from";s:0:"";s:23:"membership_join_date_to";s:0:"";s:30:"membership_start_date_relative";s:1:"0";s:26:"membership_start_date_from";s:0:"";s:24:"membership_start_date_to";s:0:"";s:28:"membership_end_date_relative";s:1:"0";s:24:"membership_end_date_from";s:0:"";s:22:"membership_end_date_to";s:0:"";s:23:"owner_membership_id_min";s:0:"";s:23:"owner_membership_id_max";s:0:"";s:22:"owner_membership_id_op";s:3:"lte";s:25:"owner_membership_id_value";s:0:"";s:6:"tid_op";s:2:"in";s:9:"tid_value";a:0:{}s:6:"sid_op";s:2:"in";s:9:"sid_value";a:0:{}s:17:"street_number_min";s:0:"";s:17:"street_number_max";s:0:"";s:16:"street_number_op";s:3:"lte";s:19:"street_number_value";s:0:"";s:14:"street_name_op";s:3:"has";s:17:"street_name_value";s:0:"";s:15:"postal_code_min";s:0:"";s:15:"postal_code_max";s:0:"";s:14:"postal_code_op";s:3:"lte";s:17:"postal_code_value";s:0:"";s:7:"city_op";s:3:"has";s:10:"city_value";s:0:"";s:12:"county_id_op";s:2:"in";s:15:"county_id_value";a:0:{}s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:35:"Contribution and Membership Details";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"access CiviMember";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Lapsed Memberships{/ts}', 'member/lapse', '{ts escape="sql" skip="true"}Provides a list of memberships that have lapsed or will lapse by the date you specify.{/ts}', 'access CiviMember', '{literal}a:16:{s:6:"fields";a:5:{s:9:"sort_name";s:1:"1";s:18:"membership_type_id";s:1:"1";s:19:"membership_end_date";s:1:"1";s:4:"name";s:1:"1";s:10:"country_id";s:1:"1";}s:6:"tid_op";s:2:"in";s:9:"tid_value";a:0:{}s:28:"membership_end_date_relative";s:1:"0";s:24:"membership_end_date_from";s:0:"";s:22:"membership_end_date_to";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:85:"Provides a list of memberships that lapsed or will lapse before the date you specify.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"access CiviMember";s:9:"domain_id";i:1;}{/literal}');

-- Event reports
INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Event Participants List{/ts}', 'event/participantListing', '{ts escape="sql" skip="true"}Provides lists of participants for an event.{/ts}', 'access CiviEvent', '{literal}a:27:{s:6:"fields";a:4:{s:9:"sort_name";s:1:"1";s:8:"event_id";s:1:"1";s:9:"status_id";s:1:"1";s:7:"role_id";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:8:"email_op";s:3:"has";s:11:"email_value";s:0:"";s:11:"event_id_op";s:2:"in";s:14:"event_id_value";a:0:{}s:6:"sid_op";s:2:"in";s:9:"sid_value";a:0:{}s:6:"rid_op";s:2:"in";s:9:"rid_value";a:0:{}s:34:"participant_register_date_relative";s:1:"0";s:30:"participant_register_date_from";s:0:"";s:28:"participant_register_date_to";s:0:"";s:6:"eid_op";s:2:"in";s:9:"eid_value";a:0:{}s:11:"custom_4_op";s:2:"in";s:14:"custom_4_value";a:0:{}s:16:"blank_column_end";s:0:"";s:11:"description";s:44:"Provides lists of participants for an event.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:16:"access CiviEvent";s:6:"groups";s:0:"";s:7:"options";N;s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Event Income Summary{/ts}', 'event/summary', '{ts escape="sql" skip="true"}Provides an overview of event income. You can include key information such as event ID, registration, attendance, and income generated to help you determine the success of an event.{/ts}', 'access CiviEvent', '{literal}a:18:{s:6:"fields";a:2:{s:5:"title";s:1:"1";s:13:"event_type_id";s:1:"1";}s:5:"id_op";s:2:"in";s:8:"id_value";a:0:{}s:16:"event_type_id_op";s:2:"in";s:19:"event_type_id_value";a:0:{}s:25:"event_start_date_relative";s:1:"0";s:21:"event_start_date_from";s:0:"";s:19:"event_start_date_to";s:0:"";s:23:"event_end_date_relative";s:1:"0";s:19:"event_end_date_from";s:0:"";s:17:"event_end_date_to";s:0:"";s:11:"description";s:181:"Provides an overview of event income. You can include key information such as event ID, registration, attendance, and income generated to help you determine the success of an event.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:16:"access CiviEvent";s:6:"charts";s:0:"";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Event Income Details{/ts}', 'event/income', '{ts escape="sql" skip="true"}Helps you to analyze the income generated by an event. The report can include details by participant type, status and payment method.{/ts}', 'access CiviEvent', '{literal}a:7:{s:5:"id_op";s:2:"in";s:8:"id_value";N;s:11:"description";s:133:"Helps you to analyze the income generated by an event. The report can include details by participant type, status and payment method.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:16:"access CiviEvent";}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Attendee List{/ts}', 'event/participantListing', '{ts escape="sql" skip="true"}Provides lists of event attendees.{/ts}', 'access CiviEvent', '{literal}a:27:{s:6:"fields";a:4:{s:9:"sort_name";s:1:"1";s:8:"event_id";s:1:"1";s:9:"status_id";s:1:"1";s:7:"role_id";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:8:"email_op";s:3:"has";s:11:"email_value";s:0:"";s:11:"event_id_op";s:2:"in";s:14:"event_id_value";a:0:{}s:6:"sid_op";s:2:"in";s:9:"sid_value";a:0:{}s:6:"rid_op";s:2:"in";s:9:"rid_value";a:0:{}s:34:"participant_register_date_relative";s:1:"0";s:30:"participant_register_date_from";s:0:"";s:28:"participant_register_date_to";s:0:"";s:6:"eid_op";s:2:"in";s:9:"eid_value";a:0:{}s:11:"custom_4_op";s:2:"in";s:14:"custom_4_value";a:0:{}s:16:"blank_column_end";s:0:"";s:11:"description";s:44:"Provides lists of participants for an event.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:16:"access CiviEvent";s:6:"groups";s:0:"";s:7:"options";N;s:9:"domain_id";i:1;}{/literal}');

-- Mailing Reports
INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Mail Bounces{/ts}', 'Mailing/bounce', '{ts escape="sql" skip="true"}Bounce Report for mailings{/ts}', 'access CiviMail', '{literal}a:33:{s:6:"fields";a:5:{s:2:"id";s:1:"1";s:10:"first_name";s:1:"1";s:9:"last_name";s:1:"1";s:11:"bounce_name";s:1:"1";s:5:"email";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:9:"source_op";s:3:"has";s:12:"source_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:15:"mailing_name_op";s:2:"in";s:18:"mailing_name_value";a:0:{}s:19:"bounce_type_name_op";s:2:"eq";s:22:"bounce_type_name_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"custom_1_op";s:2:"in";s:14:"custom_1_value";a:0:{}s:11:"custom_2_op";s:2:"in";s:14:"custom_2_value";a:0:{}s:17:"custom_3_relative";s:1:"0";s:13:"custom_3_from";s:0:"";s:11:"custom_3_to";s:0:"";s:9:"order_bys";a:1:{i:1;a:1:{s:6:"column";s:1:"-";}}s:11:"description";s:26:"Bounce Report for mailings";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:6:"groups";s:0:"";s:6:"charts";s:0:"";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Mailing Summary{/ts}', 'Mailing/summary','{ts escape="sql" skip="true"}Summary statistics for mailings{/ts}', 'access CiviMail','{literal}a:25:{s:6:"fields";a:5:{s:4:"name";s:1:"1";s:11:"queue_count";s:1:"1";s:15:"delivered_count";s:1:"1";s:12:"bounce_count";s:1:"1";s:17:"unique_open_count";s:1:"1";}s:15:"is_completed_op";s:2:"eq";s:18:"is_completed_value";s:1:"1";s:15:"mailing_name_op";s:2:"in";s:18:"mailing_name_value";a:0:{}s:9:"status_op";s:3:"has";s:12:"status_value";s:8:"Complete";s:11:"is_test_min";s:0:"";s:11:"is_test_max";s:0:"";s:10:"is_test_op";s:3:"lte";s:13:"is_test_value";s:1:"0";s:19:"start_date_relative";s:9:"this.year";s:15:"start_date_from";s:0:"";s:13:"start_date_to";s:0:"";s:17:"end_date_relative";s:9:"this.year";s:13:"end_date_from";s:0:"";s:11:"end_date_to";s:0:"";s:11:"description";s:31:"Summary statistics for mailings";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:6:"groups";s:0:"";s:6:"charts";s:0:"";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Mail Opened{/ts}', 'Mailing/opened', '{ts escape="sql" skip="true"}Display contacts who opened emails from a mailing{/ts}', 'access CiviMail', '{literal}a:31:{s:6:"fields";a:5:{s:2:"id";s:1:"1";s:10:"first_name";s:1:"1";s:9:"last_name";s:1:"1";s:12:"mailing_name";s:1:"1";s:5:"email";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:9:"source_op";s:3:"has";s:12:"source_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:15:"mailing_name_op";s:2:"in";s:18:"mailing_name_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"custom_1_op";s:2:"in";s:14:"custom_1_value";a:0:{}s:11:"custom_2_op";s:2:"in";s:14:"custom_2_value";a:0:{}s:17:"custom_3_relative";s:1:"0";s:13:"custom_3_from";s:0:"";s:11:"custom_3_to";s:0:"";s:9:"order_bys";a:1:{i:1;a:1:{s:6:"column";s:1:"-";}}s:11:"description";s:49:"Display contacts who opened emails from a mailing";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:6:"groups";s:0:"";s:6:"charts";s:0:"";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Mail Clickthroughs{/ts}', 'Mailing/clicks', '{ts escape="sql" skip="true"}Display clicks from each mailing{/ts}', 'access CiviMail', '{literal}a:31:{s:6:"fields";a:6:{s:2:"id";s:1:"1";s:10:"first_name";s:1:"1";s:9:"last_name";s:1:"1";s:12:"mailing_name";s:1:"1";s:5:"email";s:1:"1";s:3:"url";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:9:"source_op";s:3:"has";s:12:"source_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:15:"mailing_name_op";s:2:"in";s:18:"mailing_name_value";a:0:{}s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"custom_1_op";s:2:"in";s:14:"custom_1_value";a:0:{}s:11:"custom_2_op";s:2:"in";s:14:"custom_2_value";a:0:{}s:17:"custom_3_relative";s:1:"0";s:13:"custom_3_from";s:0:"";s:11:"custom_3_to";s:0:"";s:9:"order_bys";a:1:{i:1;a:1:{s:6:"column";s:1:"-";}}s:11:"description";s:32:"Display clicks from each mailing";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:6:"groups";s:0:"";s:6:"charts";s:0:"";s:9:"domain_id";i:1;}{/literal}');

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( @domainID, '{ts escape="sql" skip="true"}Mailing Details{/ts}', 'mailing/detail', '{ts escape="sql" skip="true"}Provides reporting on Intended and Successful Deliveries, Unsubscribes and Opt-outs, Replies and Forwards.{/ts}', 'access CiviMail', '{literal}a:30:{s:6:"fields";a:6:{s:9:"sort_name";s:1:"1";s:12:"mailing_name";s:1:"1";s:11:"delivery_id";s:1:"1";s:14:"unsubscribe_id";s:1:"1";s:9:"optout_id";s:1:"1";s:5:"email";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:13:"mailing_id_op";s:2:"in";s:16:"mailing_id_value";a:0:{}s:18:"delivery_status_op";s:2:"eq";s:21:"delivery_status_value";s:0:"";s:18:"is_unsubscribed_op";s:2:"eq";s:21:"is_unsubscribed_value";s:0:"";s:12:"is_optout_op";s:2:"eq";s:15:"is_optout_value";s:0:"";s:13:"is_replied_op";s:2:"eq";s:16:"is_replied_value";s:0:"";s:15:"is_forwarded_op";s:2:"eq";s:18:"is_forwarded_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:9:"order_bys";a:1:{i:1;a:2:{s:6:"column";s:9:"sort_name";s:5:"order";s:3:"ASC";}}s:11:"description";s:21:"Mailing Detail Report";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:9:"parent_id";s:0:"";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}{/literal}');

-- walk list survey report.
INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    (  @domainID, '{ts escape="sql" skip="true"}Survey Details{/ts}', 'survey/detail', '{ts escape="sql" skip="true"}Detailed report for canvassing, phone-banking, walk lists or other surveys.{/ts}', 'access CiviReport', '{literal}a:39:{s:6:"fields";a:2:{s:9:"sort_name";s:1:"1";s:6:"result";s:1:"1";}s:22:"assignee_contact_id_op";s:2:"eq";s:25:"assignee_contact_id_value";s:0:"";s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:17:"street_number_min";s:0:"";s:17:"street_number_max";s:0:"";s:16:"street_number_op";s:3:"lte";s:19:"street_number_value";s:0:"";s:14:"street_name_op";s:3:"has";s:17:"street_name_value";s:0:"";s:15:"postal_code_min";s:0:"";s:15:"postal_code_max";s:0:"";s:14:"postal_code_op";s:3:"lte";s:17:"postal_code_value";s:0:"";s:7:"city_op";s:3:"has";s:10:"city_value";s:0:"";s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:12:"survey_id_op";s:2:"in";s:15:"survey_id_value";a:0:{}s:12:"status_id_op";s:2:"eq";s:15:"status_id_value";s:1:"1";s:11:"custom_1_op";s:2:"in";s:14:"custom_1_value";a:0:{}s:11:"custom_2_op";s:2:"in";s:14:"custom_2_value";a:0:{}s:17:"custom_3_relative";s:1:"0";s:13:"custom_3_from";s:0:"";s:11:"custom_3_to";s:0:"";s:11:"description";s:75:"Detailed report for canvassing, phone-banking, walk lists or other surveys.";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"access CiviReport";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}{/literal}');
