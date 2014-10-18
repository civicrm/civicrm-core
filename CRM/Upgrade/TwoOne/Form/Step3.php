<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Upgrade_TwoOne_Form_Step3 extends CRM_Upgrade_Form {
  /**
   * @param $errorMessage
   *
   * @return bool
   */
  function verifyPreDBState(&$errorMessage) {
    $errorMessage = ts('Pre-condition failed for upgrade step %1.', array(1 => '3'));

    return $this->checkVersion('2.02');
  }

  function upgrade() {
    $currentDir = dirname(__FILE__);

    $sqlFile = implode(DIRECTORY_SEPARATOR,
      array($currentDir, '../sql', 'misc.mysql')
    );
    $this->source($sqlFile);

    // CRM-3052, dropping location_id from group_contact
    if (CRM_Core_DAO::checkFieldExists('civicrm_group_contact', 'location_id')) {
      $query = "ALTER TABLE `civicrm_group_contact`
                      DROP FOREIGN KEY `FK_civicrm_group_contact_location_id`,
                      DROP location_id";
      CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    }

    // CRM-3625, profile group_type upgrade
    $query = "SELECT id FROM civicrm_uf_group";
    $ufGroup = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    while ($ufGroup->fetch()) {
      $query     = "SELECT distinct `field_type` FROM `civicrm_uf_field` WHERE uf_group_id = %1";
      $params    = array(1 => array($ufGroup->id, 'Integer'));
      $fieldType = CRM_Core_DAO::executeQuery($query, $params);

      $types = array();
      while ($fieldType->fetch()) {
        $types[] = $fieldType->field_type;
      }

      if (count($types) >= 1) {
        $query = "UPDATE `civicrm_uf_group` SET group_type = %1 WHERE id = %2";
        $params = array(1 => array(implode(',', $types), 'String'),
          2 => array($ufGroup->id, 'Integer'),
        );
        CRM_Core_DAO::executeQuery($query, $params);
      }
    }

    $this->setVersion('2.03');
  }

  /**
   * @param $errorMessage
   *
   * @return bool
   */
  function verifyPostDBState(&$errorMessage) {
    if (!CRM_Core_DAO::checkTableExists('civicrm_cache') ||
      !CRM_Core_DAO::checkTableExists('civicrm_discount') ||
      !CRM_Core_DAO::checkTableExists('civicrm_group_contact_cache') ||
      !CRM_Core_DAO::checkTableExists('civicrm_menu')
    ) {
      // db is not 2.1
      $errorMessage .= ' Few 2.1 tables were found missing.';
      return FALSE;
    }
    // check fields which MUST be present if a proper 2.1 db
    if (!CRM_Core_DAO::checkFieldExists('civicrm_cache', 'group_name') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_cache', 'created_date') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_cache', 'expired_date') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_discount', 'option_group_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_discount', 'end_date') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_group_contact_cache', 'contact_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_menu', 'path_arguments') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_menu', 'is_exposed') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_menu', 'page_type')
    ) {
      // db looks to have stuck somewhere between 2.0 & 2.1
      $errorMessage .= ' Few important fields were found missing in some of the tables.';
      return FALSE;
    }

    $errorMessage = ts('Post-condition failed for upgrade step %1.', array(1 => '1'));

    return $this->checkVersion('2.03');
  }

  /**
   * @return string
   */
  function getTitle() {
    return ts('CiviCRM 2.1 Upgrade: Step Three (Miscellaneous)');
  }

  /**
   * @return string
   */
  function getTemplateMessage() {
    return '<p>' . ts('Step Three will upgrade rest of your database.') . '</p>';
  }

  /**
   * @return string
   */
  function getButtonTitle() {
    return ts('Upgrade & Continue');
  }
}

