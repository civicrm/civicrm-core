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
 * Upgrade logic for FiveTwentyOne
 */
class CRM_Upgrade_Incremental_php_FiveTwentyOne extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * @param string $postUpgradeMessage
   *   alterable.
   * @param string $rev
   *   an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs.
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    if ($rev == '5.21.alpha1') {
      // Find any option groups that were not converted during the upgrade.
      $notConverted = [];
      $optionGroups = \Civi\Api4\OptionGroup::get(FALSE)->execute();
      foreach ($optionGroups as $optionGroup) {
        $trimmedName = trim($optionGroup['name']);
        if (strpos($trimmedName, ' ') !== FALSE) {
          $notConverted[] = $optionGroup['title'];
        }
      }
      if (count($notConverted)) {
        $postUpgradeMessage .= '<br /><br />' . ts("The Following option Groups have not been converted due to there being already another option group with the same name in the database") . "<ul><li>" . implode('</li><li>', $notConverted) . "</li></ul>";
      }
    }
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_21_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('dev/core#1405 Fix option group names that contain spaces', 'fixOptionGroupName');
  }

  public static function fixOptionGroupName() {
    $optionGroups = \Civi\Api4\OptionGroup::get(FALSE)
      ->execute();
    foreach ($optionGroups as $optionGroup) {
      $name = trim($optionGroup['name']);
      if (strpos($name, ' ') !== FALSE) {
        $fixedName = CRM_Utils_String::titleToVar(strtolower($name));
        $check = \Civi\Api4\OptionGroup::get()
          ->addWhere('name', '=', $fixedName)
          ->setCheckPermissions(FALSE)
          ->execute();
        // Fix hard fail in upgrade due to name already in database dev/core#1447
        if (!count($check)) {
          \Civi::log()->debug('5.21 Upgrade Option Group name ' . $name . ' converted to ' . $fixedName);
          \Civi\Api4\OptionGroup::update()
            ->addWhere('id', '=', $optionGroup['id'])
            ->addValue('name', $fixedName)
            ->setCheckPermissions(FALSE)
            ->execute();
        }
      }
    }
    return TRUE;
  }

}
