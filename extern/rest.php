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

require_once '../civicrm.config.php';
CRM_Core_Config::singleton();

if (defined('PANTHEON_ENVIRONMENT')) {
  ini_set('session.save_handler', 'files');
}
$rest = new CRM_Utils_REST();

// Json-appropriate header will be set by CRM_Utils_Rest
// But we need to set header here for non-json
if (empty($_GET['json'])) {
  header('Content-Type: text/xml');
}
echo $rest->bootAndRun();
