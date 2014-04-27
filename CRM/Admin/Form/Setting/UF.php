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

/**
 * This class generates form components for Site Url
 *
 */
class CRM_Admin_Form_Setting_UF extends CRM_Admin_Form_Setting {

  protected $_settings = array();

  protected $_uf = NULL;

  /**
   * Function to build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    $config = CRM_Core_Config::singleton();
    $this->_uf = $config->userFramework;

    if ($this->_uf == 'WordPress') {
      $this->_settings = array('wpBasePage' => CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME);
    }

    CRM_Utils_System::setTitle(
      ts('Settings - %1 Integration', array(1 => $this->_uf))
    );

    $this->addElement('text', 'userFrameworkUsersTableName', ts('%1 Users Table Name', array(1 => $this->_uf)));
    // find out if drupal has its database prefixed
    global $databases;
    $drupal_prefix = '';
    if (isset($databases['default']['default']['prefix'])) {
      if (is_array($databases['default']['default']['prefix'])) {
        $drupal_prefix = $databases['default']['default']['prefix']['default'];
      }
      else {
        $drupal_prefix = $databases['default']['default']['prefix'];
      }
    }

    if (
      function_exists('module_exists') &&
      module_exists('views') &&
      (
        $config->dsn != $config->userFrameworkDSN || !empty($drupal_prefix)
      )
    ) {
      $dsnArray = DB::parseDSN($config->dsn);
      $tableNames = CRM_Core_DAO::GetStorageValues(NULL, 0, 'Name');
      $tablePrefixes = '$databases[\'default\'][\'default\'][\'prefix\']= array(';
      $tablePrefixes .= "\n  'default' => '$drupal_prefix',"; // add default prefix: the drupal database prefix
      $prefix = "";
      if ($config->dsn != $config->userFrameworkDSN) {
        $prefix = "`{$dsnArray['database']}`.";
      }
      foreach ($tableNames as $tableName => $value) {
        $tablePrefixes .= "\n  '" . str_pad($tableName . "'", 41) . " => '{$prefix}',";
      }
      $tablePrefixes .= "\n);";
      $this->assign('tablePrefixes', $tablePrefixes);
    }

    parent::buildQuickForm();
  }
}

