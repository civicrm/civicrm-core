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
 * Upgrade logic for FiveTwentyEight
 */
class CRM_Upgrade_Incremental_php_FiveTwentyEight extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed beforeupgrade.
   *
   * Note: This function is called iteratively for each upcoming
   * revision to the database.
   *
   * @param string $preUpgradeMessage
   * @param string $rev
   *   a version number, e.g. '4.4.alpha1', '4.4.beta3', '4.4.0'.
   * @param null $currentVer
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    // Example: Generate a pre-upgrade message.
    if ($rev == '5.28.alpha1') {
      $preUpgradeMessage .= CRM_Upgrade_Incremental_php_FiveTwentyEight::createWpFilesMessage();
    }
  }

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * @param string $postUpgradeMessage
   *   alterable.
   * @param string $rev
   *   an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs.
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    // Example: Generate a pre-upgrade message.
    if ($rev == '5.28.alpha1') {
      $postUpgradeMessage .= CRM_Upgrade_Incremental_php_FiveTwentyEight::createWpFilesMessage();
    }
  }

  public static function createWpFilesMessage() {
    if (!function_exists('civi_wp')) {
      return '';
    }

    if (isset($GLOBALS['civicrm_paths']['civicrm.files']['path'])) {
      // They've explicitly chosen to use a non-default path.
      return '';
    }

    $table = '<table><tbody>'
      . sprintf('<tr><th colspan="2">%s</th></tr>', ts('<b>[civicrm.files]</b> Path'))
      . sprintf('<tr><td>%s</td><td><code>%s</code></td></tr>', ts('5.29 Default value:'), wp_upload_dir()['basedir'] . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR)
      . sprintf('<tr><td>%s</td><td><code>%s</code></td></tr>', ts('5.28 Default value:'), CRM_Core_Config::singleton()->userSystem->getDefaultFileStorage()['path'])
      . sprintf('<tr><td>%s</td><td><code>%s</code></td></tr>', ts('Active Value:'), Civi::paths()->getVariable('civicrm.files', 'path'))
      . sprintf('<tr><th colspan="2">%s</th></tr>', ts('<b>[civicrm.files]</b> URL'))
      . sprintf('<tr><td>%s</td><td><code>%s</code></td></tr>', ts('5.29 Default value:'), wp_upload_dir()['baseurl'] . '/civicrm/')
      . sprintf('<tr><td>%s</td><td><code>%s</code></td></tr>', ts('5.28 Default value:'), CRM_Core_Config::singleton()->userSystem->getDefaultFileStorage()['url'])
      . sprintf('<tr><td>%s</td><td><code>%s</code></td></tr>', ts('Active Value:'), Civi::paths()->getVariable('civicrm.files', 'url'))
      . '</tbody></table>';

    return '<p>' . ts('Starting with version 5.29.0, CiviCRM on WordPress may make a subtle change in the calculation of <code>[civicrm.files]</code>. To ensure a smooth upgrade, please review the following table. All paths and URLs should appear the same. If there is <strong><em>any</em></strong> discrepancy, then consult <a %1>the upgrade documentation</a>.', [
      1 => 'href="https://docs.civicrm.org/sysadmin/en/latest/upgrade/version-specific/#civicrm-5.29" target="_blank"',
      2 => '...wp-content/uploads/civicrm',
    ]) . '</p>' . $table;
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_28_alpha1($rev) {
    $this->addTask('Populate missing Contact Type name fields', 'populateMissingContactTypeName');
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add icon column to civicrm_custom_group', 'addColumn',
      'civicrm_custom_group', 'icon', "varchar(255) COMMENT 'crm-i icon class' DEFAULT NULL");
    $this->addTask('Remove index on medium_id from civicrm_activity', 'dropIndex', 'civicrm_activity', 'index_medium_id');
  }

  public static function populateMissingContactTypeName() {
    $contactTypes = \Civi\Api4\ContactType::get(FALSE)
      ->execute();
    foreach ($contactTypes as $contactType) {
      if (empty($contactType['name'])) {
        \Civi\Api4\ContactType::update()
          ->addWhere('id', '=', $contactType['id'])
          ->addValue('name', ucfirst(CRM_Utils_String::munge($contactType['label'])))
          ->setCheckPermissions(FALSE)
          ->execute();
      }
    }
    return TRUE;
  }

}
