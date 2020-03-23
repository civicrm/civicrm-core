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

CRM_Utils_System::loadBootStrap(array(), FALSE);

CRM_Cxn_BAO_Cxn::createApiServer()
  ->handle(file_get_contents('php://input'))
  ->send();
