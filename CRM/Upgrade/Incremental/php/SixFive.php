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
 * Upgrade logic for the 6.5.x series.
 *
 * Each minor version in the series is handled by either a `6.5.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_5_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixFive extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed before upgrade.
   *
   * Downstream classes should implement this method to generate their messages.
   *
   * This method will be invoked multiple times. Implementations MUST consult the `$rev`
   * before deciding what messages to add. See the examples linked below.
   *
   * @see \CRM_Upgrade_Incremental_php_FiveTwenty::setPreUpgradeMessage()
   *
   * @param string $preUpgradeMessage
   *   Accumulated list of messages. Alterable.
   * @param string $rev
   *   The incremental version number. (Called repeatedly, once for each increment.)
   *
   *   Ex: Suppose the system upgrades from 5.7.3 to 5.10.0. The method FiveEight::setPreUpgradeMessage()
   *   will be called for each increment of '5.8.*' ('5.8.alpha1' => '5.8.beta1' =>  '5.8.0').
   * @param null $currentVer
   *   This is the penultimate version targeted by the upgrader.
   *   Equivalent to CRM_Utils_System::version().
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    $path = CRM_Utils_Constant::value('CIVICRM_SMARTY_AUTOLOAD_PATH') ?: CRM_Utils_Constant::value('CIVICRM_SMARTY3_AUTOLOAD_PATH');
    if ($rev == '6.5.alpha1' && !$path) {
      $smarty2Path = \Civi::paths()->getPath('[civicrm.packages]/Smarty/Smarty.class.php');
      $preUpgradeMessage .= '<br/>' . ts("WARNING: Your site is currently using the Smarty2 library which is being replaced by the Smarty5 library.")
        . " " . ts("If you take no action your site will now switch to using Smarty5. Some sites use extensions which have not been upgraded to work with Smarty5.")
        . " " . ts("If your extensions or other custom code will not run on Smarty5, you should log an issue with the maintainer. If the maintainer does not respond you should consider uninstalling the extension.")
        . " " . ts("In the short term you can make your site continue to use Smarty2 by editing your civicrm.settings.php file and adding the line %1",
          [1 => sprintf("<pre>  define('CIVICRM_SMARTY_AUTOLOAD_PATH',\n    %s);</pre>", htmlentities(var_export($smarty2Path, 1)))])
        . (ts('Upcoming versions will standardize on Smarty v5. CiviCRM <a %1>v6.4-ESR</a> will provide extended support for Smarty v2, v3, & v4. To learn more and discuss, see the <a %2>Smarty transition page</a>.', [
          1 => 'target="_blank" href="' . htmlentities('https://civicrm.org/esr') . '"',
          2 => 'target="_blank" href="' . htmlentities('https://civicrm.org/redirect/smarty-v3') . '"',
        ])
        );
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_5_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Update Membership mappings', 'upgradeImportMappingFields', 'Membership');
    $this->addTask('Install legacyprofiles extension', 'installLegacyProfiles');
  }

  /**
   * @param \CRM_Queue_TaskContext|null $context
   * @param string $entity
   *
   * @return true
   * @throws \CRM_Core_Exception
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function upgradeImportMappingFields($context, string $entity): bool {
    CRM_Upgrade_Incremental_php_SixTwo::upgradeImportMappingFields($context, $entity);
    return TRUE;
  }

  /**
   * Install legacyprofiles extension.
   *
   * This feature is restructured as a core extension - which is primarily a code cleanup step.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function installLegacyProfiles(): bool {
    // Based on the instructions for the FiveThirty financialacls upgrade step
    // Install via direct SQL manipulation. Note that:
    // (1) This extension has no activation logic as of 5.76 (the DB tables are still in core)
    // (2) This extension is not enabled on new installs.
    // (3) Caches are flushed at the end of the upgrade.
    // ($) Over long term, upgrade steps are more reliable in SQL. API/BAO sometimes don't work mid-upgrade.
    $active = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_uf_field WHERE visibility != "User and User Admin Only" LIMIT 1');
    if ($active) {
      $insert = CRM_Utils_SQL_Insert::into('civicrm_extension')->row([
        'type' => 'module',
        'full_name' => 'legacyprofiles',
        'name' => 'legacyprofiles',
        'label' => 'Legacy Profiles',
        'file' => 'legacyprofiles',
        'schema_version' => NULL,
        'is_active' => 1,
      ]);
      CRM_Core_DAO::executeQuery($insert->usingReplace()->toSQL());
    }
    return TRUE;
  }

}
