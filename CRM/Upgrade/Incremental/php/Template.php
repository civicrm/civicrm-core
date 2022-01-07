<?php
if (PHP_SAPI !== 'cli') {
  die("This template is only valid on CLI.");
}
echo "<?php\n";
?>
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
 * Upgrade logic for the <?php echo $versionX = str_replace('alpha1', 'x', $versionNumber); ?> series.
 *
 * Each minor version in the series is handled by either a `<?php echo $versionX; ?>.mysql.tpl` file,
 * or a function in this class named `upgrade_<?php echo str_replace('.', '_', $versionX); ?>`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_<?php echo $camelNumber; ?> extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_<?php echo str_replace('.', '_', $versionNumber); ?>($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
  }

}
