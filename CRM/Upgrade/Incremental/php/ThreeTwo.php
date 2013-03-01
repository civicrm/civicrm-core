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
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
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
class CRM_Upgrade_Incremental_php_ThreeTwo {
  function verifyPreDBstate(&$errors) {
    return TRUE;
  }

  function upgrade_3_2_alpha1($rev) {
    //CRM-5666 -if user already have 'access CiviCase'
    //give all new permissions and drop access CiviCase.
    $config = CRM_Core_Config::singleton();
    if ($config->userSystem->is_drupal) {

      $config->userSystem->replacePermission('access CiviCase', array('access my cases and activities', 'access all cases and activities', 'administer CiviCase'));

      //insert core acls.
      $casePermissions = array(
        'delete in CiviCase',
        'administer CiviCase',
        'access my cases and activities',
        'access all cases and activities',
      );
      $aclParams = array(
        'name' => 'Core ACL',
        'deny' => 0,
        'acl_id' => NULL,
        'object_id' => NULL,
        'acl_table' => NULL,
        'entity_id' => 1,
        'operation' => 'All',
        'is_active' => 1,
        'entity_table' => 'civicrm_acl_role',
      );
      foreach ($casePermissions as $per) {
        $aclParams['object_table'] = $per;
        $acl = new CRM_ACL_DAO_ACL();
        $acl->object_table = $per;
        if (!$acl->find(TRUE)) {
          $acl->copyValues($aclParams);
          $acl->save();
        }
      }
      //drop 'access CiviCase' acl
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_acl WHERE object_table = 'access CiviCase'");
    }

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);
  }

  function upgrade_3_2_beta4($rev) {
    $upgrade = new CRM_Upgrade_Form;

    $config = CRM_Core_Config::singleton();
    $seedLocale = $config->lcMessages;

    //handle missing civicrm_uf_field.help_pre
    $hasLocalizedPreHelpCols = FALSE;

    // CRM-6451: for multilingual sites we need to find the optimal
    // locale to use as the final civicrm_membership_status.name column
    $domain = new CRM_Core_DAO_Domain;
    $domain->find(TRUE);
    $locales = array();
    if ($domain->locales) {
      $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
      // optimal: an English locale
      foreach (array(
        'en_US', 'en_GB', 'en_AU') as $loc) {
        if (in_array($loc, $locales)) {
          $seedLocale = $loc;
          break;
        }
      }

      // if no English and no $config->lcMessages: use the first available
      if (!$seedLocale) {
        $seedLocale = $locales[0];
      }

      $upgrade->assign('seedLocale', $seedLocale);
      $upgrade->assign('locales', $locales);

      $localizedColNames = array();
      foreach ($locales as $loc) {
        $localizedName = "help_pre_{$loc}";
        $localizedColNames[$localizedName] = $localizedName;
      }
      $columns = CRM_Core_DAO::executeQuery('SHOW COLUMNS FROM civicrm_uf_field');
      while ($columns->fetch()) {
        if (strpos($columns->Field, 'help_pre') !== FALSE &&
          in_array($columns->Field, $localizedColNames)
        ) {
          $hasLocalizedPreHelpCols = TRUE;
          break;
        }
      }
    }
    $upgrade->assign('hasLocalizedPreHelpCols', $hasLocalizedPreHelpCols);

    $upgrade->processSQL($rev);

    // now civicrm_membership_status.name has possibly localised strings, so fix them
    $i18n = new CRM_Core_I18n($seedLocale);
    $statuses = array(
      array(
        'name' => 'New',
        'start_event' => 'join_date',
        'end_event' => 'join_date',
        'end_event_adjust_unit' => 'month',
        'end_event_adjust_interval' => '3',
        'is_current_member' => '1',
        'is_admin' => '0',
        'is_default' => '0',
        'is_reserved' => '0',
      ),
      array(
        'name' => 'Current',
        'start_event' => 'start_date',
        'end_event' => 'end_date',
        'is_current_member' => '1',
        'is_admin' => '0',
        'is_default' => '1',
        'is_reserved' => '0',
      ),
      array(
        'name' => 'Grace',
        'start_event' => 'end_date',
        'end_event' => 'end_date',
        'end_event_adjust_unit' => 'month',
        'end_event_adjust_interval' => '1',
        'is_current_member' => '1',
        'is_admin' => '0',
        'is_default' => '0',
        'is_reserved' => '0',
      ),
      array(
        'name' => 'Expired',
        'start_event' => 'end_date',
        'start_event_adjust_unit' => 'month',
        'start_event_adjust_interval' => '1',
        'is_current_member' => '0',
        'is_admin' => '0',
        'is_default' => '0',
        'is_reserved' => '0',
      ),
      array(
        'name' => 'Pending',
        'start_event' => 'join_date',
        'end_event' => 'join_date',
        'is_current_member' => '0',
        'is_admin' => '0',
        'is_default' => '0',
        'is_reserved' => '1',
      ),
      array(
        'name' => 'Cancelled',
        'start_event' => 'join_date',
        'end_event' => 'join_date',
        'is_current_member' => '0',
        'is_admin' => '0',
        'is_default' => '0',
        'is_reserved' => '0',
      ),
      array(
        'name' => 'Deceased',
        'is_current_member' => '0',
        'is_admin' => '1',
        'is_default' => '0',
        'is_reserved' => '1',
      ),
    );

    $statusIds = array();
    $insertedNewRecord = FALSE;
    foreach ($statuses as $status) {
      $dao = new CRM_Member_DAO_MembershipStatus;

      // try to find an existing English status
      $dao->name = $status['name'];

      //             // if not found, look for translated status name
      //             if (!$dao->find(true)) {
      //                 $found     = false;
      //                 $dao->name = $i18n->translate($status['name']);
      //             }

      // if found, update name and is_reserved
      if ($dao->find(TRUE)) {
        $dao->name = $status['name'];
        $dao->is_reserved = $status['is_reserved'];
        if ($status['is_reserved']) {
          $dao->is_active = 1;
        }
        // if not found, prepare a new row for insertion
      }
      else {
        $insertedNewRecord = TRUE;
        foreach ($status as $property => $value) {
          $dao->$property = $value;
        }
        $dao->weight = CRM_Utils_Weight::getDefaultWeight('CRM_Member_DAO_MembershipStatus');
      }

      // add label (translated name) and save (UPDATE or INSERT)
      $dao->label = $i18n->translate($status['name']);
      $dao->save();

      $statusIds[$dao->id] = $dao->id;
    }

    //disable all status those are customs.
    if ($insertedNewRecord) {
      $sql = '
UPDATE  civicrm_membership_status 
   SET  is_active = 0 
 WHERE  id NOT IN ( ' . implode(',', $statusIds) . ' )';
      CRM_Core_DAO::executeQuery($sql);
    }
  }

  function upgrade_3_2_1($rev) {
    //CRM-6565 check if Activity Index is already exists or not.
    $addActivityTypeIndex = TRUE;
    $indexes = CRM_Core_DAO::executeQuery('SHOW INDEXES FROM civicrm_activity');
    while ($indexes->fetch()) {
      if ($indexes->Key_name == 'UI_activity_type_id') {
        $addActivityTypeIndex = FALSE;
      }
    }
    // CRM-6563: restrict access to the upload dir, tighten access to the config-and-log dir
    $config = CRM_Core_Config::singleton();
    CRM_Utils_File::restrictAccess($config->uploadDir);
    CRM_Utils_File::restrictAccess($config->configAndLogDir);
    $upgrade = new CRM_Upgrade_Form;
    $upgrade->assign('addActivityTypeIndex', $addActivityTypeIndex);
    $upgrade->processSQL($rev);
  }
}

