<?php
require_once 'VAN/Person.php'; $details = VAN_Person::getPersonDetails('5402891');

//require_once '/Users/lobo/svn/crm_v3.0/civicrm.settings.php';
require_once '/Users/kurund/svn/civicrm30/civicrm.settings.php';
require_once 'CRM/Core/Config.php';
$config = CRM_Core_Config::singleton();

require_once 'VAN/Contact.php';
VAN_Contact::createOrUpdateContact($details);

