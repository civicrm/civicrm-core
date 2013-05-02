<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/


// Patch for CRM-3154
if (phpversion() == "5.2.2" &&
  !isset($GLOBALS['HTTP_RAW_POST_DATA'])
) {
  $GLOBALS['HTTP_RAW_POST_DATA'] = file_get_contents('php://input');
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

$config = CRM_Core_Config::singleton();

$server->setClass('CRM_Utils_SoapServer', $config->userFrameworkClass);

$server->setPersistence(SOAP_PERSISTENCE_SESSION);

$server->handle();
