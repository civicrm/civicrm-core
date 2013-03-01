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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Upgrade_TwoOne_Form_Step2 extends CRM_Upgrade_Form {
  function verifyPreDBState(&$errorMessage) {
    $errorMessage = ts('Pre-condition failed for upgrade step %1.', array(1 => '2'));

    return $this->checkVersion('2.01');
  }

  function upgrade() {
    $currentDir = dirname(__FILE__);

    // 1. remove domain_ids from the entire db
    $sqlFile = implode(DIRECTORY_SEPARATOR,
      array($currentDir, '../sql', 'group_values.mysql')
    );
    $this->source($sqlFile);

    // 2. Add option group "safe_file_extension" and its option
    // values  to db, if not already present. CRM-3238
    $query = "
SELECT id FROM civicrm_option_group WHERE name = 'safe_file_extension'";
    $sfeGroup = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    $sfeGroup->fetch();
    if (!isset($sfeGroup->id)) {
      $query = "
INSERT INTO civicrm_option_group (name, description, is_reserved, is_active)
VALUES ('safe_file_extension', 'Safe File Extension', 0, 1)";
      $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);

      $query = "
SELECT id FROM civicrm_option_group WHERE name = 'safe_file_extension'";
      $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
      $dao->fetch();
      if ($dao->id) {
        $query = "
INSERT INTO `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`) 
VALUES 
( %1, 'jpg',  1, NULL, NULL, 0, 0,  1, NULL, 0, 0, 1, NULL),
( %1, 'jpeg', 2, NULL, NULL, 0, 0,  2, NULL, 0, 0, 1, NULL),
( %1, 'png',  3, NULL, NULL, 0, 0,  3, NULL, 0, 0, 1, NULL),
( %1, 'gif',  4, NULL, NULL, 0, 0,  4, NULL, 0, 0, 1, NULL),
( %1, 'txt',  5, NULL, NULL, 0, 0,  5, NULL, 0, 0, 1, NULL),
( %1, 'pdf',  6, NULL, NULL, 0, 0,  6, NULL, 0, 0, 1, NULL),
( %1, 'doc',  7, NULL, NULL, 0, 0,  7, NULL, 0, 0, 1, NULL),
( %1, 'xls',  8, NULL, NULL, 0, 0,  8, NULL, 0, 0, 1, NULL),
( %1, 'rtf',  9, NULL, NULL, 0, 0,  9, NULL, 0, 0, 1, NULL),
( %1, 'csv', 10, NULL, NULL, 0, 0, 10, NULL, 0, 0, 1, NULL),
( %1, 'ppt', 11, NULL, NULL, 0, 0, 11, NULL, 0, 0, 1, NULL)
";
        $params = array(1 => array($dao->id, 'Integer'));
        $dao = CRM_Core_DAO::executeQuery($query, $params);
      }
    }
    else {
      //fix for CRM-3252
      //delete records from db.

      $query = "
DELETE 
FROM   `civicrm_option_value` 
WHERE  `option_group_id` = %1
AND    `label` IN ('html', 'htm')
";
      $params = array(1 => array($sfeGroup->id, 'Integer'));
      $dao = CRM_Core_DAO::executeQuery($query, $params);
    }

    $this->setVersion('2.02');
  }

  function verifyPostDBState(&$errorMessage) {
    // check if Option Group & Option Values tables exists
    if (!CRM_Core_DAO::checkTableExists('civicrm_option_group') ||
      !CRM_Core_DAO::checkTableExists('civicrm_option_value')
    ) {
      $errorMessage .= '  option group or option value table is missing.';
      return FALSE;
    }
    // check fields which MUST be present civicrm_option_group & civicrm_option_value
    if (!CRM_Core_DAO::checkFieldExists('civicrm_option_group', 'id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_group', 'name') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_group', 'label') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_group', 'description') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_group', 'is_reserved') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_group', 'is_active')
    ) {
      $errorMessage .= ' Few important fields were found missing in civicrm_option_group table.';
      return FALSE;
    }
    if (!CRM_Core_DAO::checkFieldExists('civicrm_option_value', 'id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_value', 'option_group_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_value', 'name') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_value', 'label') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_value', 'description') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_value', 'component_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_value', 'is_active')
    ) {
      $errorMessage .= ' Few important fields were found missing in civicrm_option_value table.';
      return FALSE;
    }
    $errorMessage = ts('Post-condition failed for upgrade step %1.', array(1 => '1'));

    return $this->checkVersion('2.02');
  }

  function getTitle() {
    return ts('CiviCRM 2.1 Upgrade: Step Two (Option Group And Values)');
  }

  function getTemplateMessage() {
    return '<p>' . ts('Step Two will upgrade the Option Group And Values in your database.') . '</p>';
  }

  function getButtonTitle() {
    return ts('Upgrade & Continue');
  }
}

