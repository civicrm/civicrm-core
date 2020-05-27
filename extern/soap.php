<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

if (defined('PANTHEON_ENVIRONMENT')) {
  ini_set('session.save_handler', 'files');
}
session_start();

require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';

$server = new SoapServer(NULL,
  array(
    'uri' => 'urn:civicrm',
    'soap_version' => SOAP_1_2,
  )
);


require_once 'CRM/Utils/SoapServer.php';
$crm_soap = new CRM_Utils_SoapServer();

/* Cache the real UF, override it with the SOAP environment */

$civicrmConfig = CRM_Core_Config::singleton();

$server->setClass('CRM_Utils_SoapServer', $civicrmConfig->userFrameworkClass);

$server->setPersistence(SOAP_PERSISTENCE_SESSION);

$server->handle();
