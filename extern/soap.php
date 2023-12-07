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

$server = new SoapServer(NULL, [
  'uri' => 'urn:civicrm',
  'soap_version' => SOAP_1_2,
]);

$server->fault('obsolete', "SOAP support is no longer included with civicrm-core.");
// It's removed because (a) the main consumer is no longer live, (b) it's awkward to maintain 'extern/' scripts,
// and (c) there's an extensionized version at https://lab.civicrm.org/extensions/civismtp/
