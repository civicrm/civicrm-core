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
 * Upgrade logic for the 5.56.x series.
 *
 * Each minor version in the series is handled by either a `5.56.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_56_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveFiftySix extends CRM_Upgrade_Incremental_Base {

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    if ($rev === '5.56.alpha1') {
      $preUpgradeMessage .= $this->createDeepExtMessage();
    }
  }

  /**
   * In 5.56, the extension-system abides by a limit on the depth of search.
   * This improves the performance of cache-clears. The default depth
   * should catch most/all extensions on most/all sites -- but it's
   * possible that some sites still need a deep search.
   *
   * @return string
   */
  protected function createDeepExtMessage(): string {
    $infinityAndBeyond = 1000;
    $liveSystem = CRM_Extension_System::singleton();
    $deepSystem = new CRM_Extension_System(['maxDepth' => $infinityAndBeyond]);
    $problemKeys = array_diff($deepSystem->getFullContainer()->getKeys(), $liveSystem->getFullContainer()->getKeys());
    if (empty($problemKeys)) {
      return '';
    }

    $message = ts('When loading extensions, CiviCRM searches the filesystem. In v5.56+, the default search is more constrained. This improves performance, but some extensions (%1) will become invisible. To make these visible, you should either move the source-code or edit <code>civicrm.settings.php</code>. For example, you may add this line:', [
      1 => implode(', ', array_map(
        function($key) {
          return '"<code>' . htmlentities($key) . '</code>"';
        },
        $problemKeys
      )),
    ]);
    $example = htmlentities("\$civicrm_setting['domain']['ext_max_depth'] = $infinityAndBeyond;");

    return "<p><strong>" . ts('WARNING') . "</strong>: {$message}</p><pre>$example</pre>";
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_56_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
  }

}
