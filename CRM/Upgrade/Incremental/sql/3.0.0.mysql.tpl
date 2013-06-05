-- CRM-5119
    UPDATE civicrm_navigation SET permission ='add contacts'
        WHERE civicrm_navigation.name IN('New Individual','New Household','New Organization');

    UPDATE civicrm_navigation SET permission ='import contacts'
        WHERE civicrm_navigation.name IN( 'Import Contacts','Import Activities');

    UPDATE civicrm_navigation SET permission ='edit groups' WHERE civicrm_navigation.name= 'New Group';
    UPDATE civicrm_navigation SET permission ='edit groups,administer CiviCRM', permission_operator ='AND'
        WHERE civicrm_navigation.name= 'Manage Groups';

    UPDATE civicrm_navigation SET permission ='administer CiviCRM' WHERE civicrm_navigation.name= 'New Tag';
    UPDATE civicrm_navigation SET permission ='administer CiviCRM' WHERE civicrm_navigation.name= 'Manage Tags (Categories)';

    UPDATE civicrm_navigation SET permission ='access CiviContribute' WHERE civicrm_navigation.name= 'Dashboard' AND url='civicrm/contribute&reset=1';
    UPDATE civicrm_navigation SET permission ='access CiviContribute' WHERE civicrm_navigation.name= 'Find Contributions';

    UPDATE civicrm_navigation SET permission ='access CiviPledge'  WHERE civicrm_navigation.name= 'Dashboard' AND url='civicrm/pledge&reset=1';
    UPDATE civicrm_navigation SET permission ='access CiviPledge'  WHERE civicrm_navigation.name= 'Find Pledges';
    UPDATE civicrm_navigation SET permission ='access CiviPledge,edit pledges', permission_operator ='AND'
        WHERE civicrm_navigation.name= 'New Pledge';

    UPDATE civicrm_navigation SET permission ='access CiviContribute,administer CiviCRM',  permission_operator ='AND'
        WHERE civicrm_navigation.name IN ( 'New Contribution Page','Manage Contribution Pages','Personal Campaign Pages','Premiums',
                                           'Contribution Types', 'Payment Instruments','Accepted Credit Cards' );
    UPDATE civicrm_navigation SET permission ='access CiviContribute,edit contributions',  permission_operator ='AND'
        WHERE civicrm_navigation.name IN ( 'New Contribution','Import Contributions');
    UPDATE civicrm_navigation SET permission ='access CiviEvent'
        WHERE civicrm_navigation.name IN( 'CiviEvent Dashboard','Find Participants');

    UPDATE civicrm_navigation SET permission ='access CiviEvent,edit event participants', permission_operator ='AND'
        WHERE civicrm_navigation.name IN ( 'Register Event Participant','Import Participants');

    UPDATE civicrm_navigation SET permission ='access CiviEvent,administer CiviCRM', permission_operator ='AND'
        WHERE civicrm_navigation.name IN( 'New Event','Manage Events','Event Templates','New Price Set', 'Manage Price Sets',
                                          'Participant Listing Templates','Event Types','Participant Statuses','Participant Roles');

    UPDATE civicrm_navigation SET permission ='access CiviMail'
        WHERE civicrm_navigation.name IN ( 'New Mailing','Draft and Unscheduled Mailings','Scheduled and Sent Mailings','Archived Mailings');

    UPDATE civicrm_navigation SET permission ='access CiviMail,administer CiviCRM',  permission_operator ='AND'
            WHERE civicrm_navigation.name IN ( 'Headers, Footers, and Automated Messages','Mail Accounts');

    UPDATE civicrm_navigation SET permission ='access CiviMember' WHERE civicrm_navigation.name= 'Dashboard' AND url='civicrm/member&reset=1';
    UPDATE civicrm_navigation SET permission ='access CiviMember' WHERE civicrm_navigation.name= 'Find Members';
    UPDATE civicrm_navigation SET permission ='access CiviMember,edit memberships', permission_operator ='AND'
        WHERE civicrm_navigation.name IN ('New Membership','Import Members');

    UPDATE civicrm_navigation SET permission ='access CiviCase' WHERE civicrm_navigation.name= 'Dashboard' AND url='civicrm/case&reset=1';
    UPDATE civicrm_navigation SET permission ='access CiviCase' WHERE civicrm_navigation.name= 'Find Cases';
    UPDATE civicrm_navigation SET permission ='access CiviCase,add contacts', permission_operator ='AND'
        WHERE civicrm_navigation.name= 'New Case';

    UPDATE civicrm_navigation SET permission ='access CiviGrant' WHERE civicrm_navigation.name= 'Dashboard' AND url='civicrm/grant&reset=1';
    UPDATE civicrm_navigation SET permission ='access CiviGrant' WHERE civicrm_navigation.name= 'Find Grants';
    UPDATE civicrm_navigation SET permission ='access CiviGrant,edit grants', permission_operator ='AND'
        WHERE civicrm_navigation.name= 'New Grant';

    UPDATE civicrm_navigation SET permission ='administer CiviCRM'
        WHERE civicrm_navigation.name IN ( 'Administration Console', 'Customize','Custom Data','CiviCRM Profile','Navigation Menu',
                                           'Manage Custom Searches','Configure','Configuration Checklist','Global Settings','Enable Components',
                                           'Site Preferences','Directories','Resource URLs','Outbound Email','Mapping and Geocoding',
                                           'Payment Processors','Localization','Address Settings','Search Settings', 'Date Formats', 'CMS Integration',
                                           'Miscellaneous','Safe File Extensions','Debugging','Import/Export Mappings', 'Message Templates',
                                           'Domain Information','FROM Email Addresses', 'Update Directory Path and URL','Manage','Find and Merge Duplicate Contacts',
                                           'Access Control','Synchronize Users to Contacts','Option Lists','Activity Types','Relationship Types','Gender Options',
                                           'Addressee Formats','Email Greetings','Postal Greetings','Instant Messenger Services','Mobile Phone Providers',
                                           'Phone Types','Preferred Communication Methods');

    UPDATE civicrm_navigation SET permission ='administer CiviCRM' WHERE civicrm_navigation.name= 'Location Types (Home, Work...)';
    UPDATE civicrm_navigation SET permission ='administer CiviCRM' WHERE civicrm_navigation.name= 'Tags (Categories)';
    UPDATE civicrm_navigation SET permission ='administer CiviCRM' WHERE civicrm_navigation.name= 'Individual Prefixes (Ms, Mr...)';
    UPDATE civicrm_navigation SET permission ='administer CiviCRM' WHERE civicrm_navigation.name= 'Individual Suffixes (Jr, Sr...)';

    UPDATE civicrm_navigation SET permission ='access CiviCase,administer CiviCRM',  permission_operator ='AND'
        WHERE civicrm_navigation.name IN ( 'Case Types','Redaction Rules');

    UPDATE civicrm_navigation SET permission ='access CiviGrant,administer CiviCRM',  permission_operator ='AND' WHERE civicrm_navigation.name= 'Grant Types';

    UPDATE civicrm_navigation SET permission ='access CiviMember,administer CiviCRM',  permission_operator ='AND'
        WHERE civicrm_navigation.name IN ( 'Membership Types','Membership Status Rules');

    UPDATE civicrm_navigation SET permission ='access CiviReport' WHERE civicrm_navigation.name = 'Reports Listing';
    UPDATE civicrm_navigation SET permission ='administer Reports'
        WHERE civicrm_navigation.name IN ( 'Create Reports from Templates','Manage Templates');
