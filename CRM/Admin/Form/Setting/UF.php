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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class generates form components for Site Url.
 */
class CRM_Admin_Form_Setting_UF extends CRM_Admin_Form_Setting {

  protected $_settings = [];

  protected $_uf = NULL;

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $config = CRM_Core_Config::singleton();
    $this->_uf = $config->userFramework;
    $this->_settings['syncCMSEmail'] = CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME;

    if ($this->_uf == 'WordPress') {
      $this->_settings['wpBasePage'] = CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME;
    }

    CRM_Utils_System::setTitle(
      ts('Settings - %1 Integration', [1 => $this->_uf])
    );

    if ($config->userSystem->is_drupal) {
      $this->_settings['userFrameworkUsersTableName'] = CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME;
    }

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
      $tableNames = CRM_Core_DAO::getTableNames();
      asort($tableNames);
      $tablePrefixes = '$databases[\'default\'][\'default\'][\'prefix\']= array(';
      if ($config->userFramework === 'Backdrop') {
        $tablePrefixes = '$database_prefix = array(';
      }
      // add default prefix: the drupal database prefix
      $tablePrefixes .= "\n  'default' => '$drupal_prefix',";
      $prefix = "";
      if ($config->dsn != $config->userFrameworkDSN) {
        $prefix = "`{$dsnArray['database']}`.";
      }
      foreach ($tableNames as $tableName) {
        $tablePrefixes .= "\n  '" . str_pad($tableName . "'", 41) . " => '{$prefix}',";
      }
      $tablePrefixes .= "\n);";
      $this->assign('tablePrefixes', $tablePrefixes);
    }

    parent::buildQuickForm();
  }

}
