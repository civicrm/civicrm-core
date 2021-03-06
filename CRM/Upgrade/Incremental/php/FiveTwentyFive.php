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
 * Upgrade logic for FiveTwentyFive */
class CRM_Upgrade_Incremental_php_FiveTwentyFive extends CRM_Upgrade_Incremental_Base {

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
    // if ($rev == '5.12.34') {
    //   $preUpgradeMessage .= '<p>' . ts('A new permission, "%1", has been added. This permission is now used to control access to the Manage Tags screen.', array(1 => ts('manage tags'))) . '</p>';
    // }
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
    // Example: Generate a post-upgrade message.
    // if ($rev == '5.12.34') {
    //   $postUpgradeMessage .= '<br /><br />' . ts("By default, CiviCRM now disables the ability to import directly from SQL. To use this feature, you must explicitly grant permission 'import SQL datasource'.");
    // }
  }

  /*
   * Important! All upgrade functions MUST add a 'runSql' task.
   * Uncomment and use the following template for a new upgrade version
   * (change the x in the function name):
   */

  //  /**
  //   * Upgrade function.
  //   *
  //   * @param string $rev
  //   */
  //  public function upgrade_5_0_x($rev) {
  //    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
  //    $this->addTask('Do the foo change', 'taskFoo', ...);
  //    // Additional tasks here...
  //    // Note: do not use ts() in the addTask description because it adds unnecessary strings to transifex.
  //    // The above is an exception because 'Upgrade DB to %1: SQL' is generic & reusable.
  //  }

  // public static function taskFoo(CRM_Queue_TaskContext $ctx, ...) {
  //   return TRUE;
  // }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_25_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Convert Report Form dates from jcalander to datepicker', 'convertReportsJcalendarToDatePicker');
  }

  public function upgrade_5_25_beta3($rev) {
    // Not used // $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Convert CiviContribute settings', 'updateContributeSettings');
  }

  /**
   * Convert date fields stored in civicrm_report_instance to that format for datepicker
   */
  public static function convertReportsJcalendarToDatePicker() {
    $date_fields = [];
    $reports = CRM_Core_DAO::executeQuery("SELECT id FROM civicrm_report_instance WHERE form_values like '%relative%'");
    while ($reports->fetch()) {
      $report = civicrm_api3('ReportInstance', 'getsingle', ['id' => $reports->id]);
      $reportFormValues = unserialize($report['form_values']);
      foreach ($reportFormValues as $index => $value) {
        if (strpos($index, '_relative') !== FALSE) {
          $date_fields[] = str_replace('_relative', '', $index);
        }
      }
      foreach ($date_fields as $date_field) {
        foreach ($reportFormValues as $index => $value) {
          if ($index === $date_field . '_to' || $index === $date_field . '_from') {
            $isEndOfDay = strpos($index, '_to') !== FALSE ? TRUE : FALSE;
            // If We have stored in the database hours minutes seconds use them
            if (!empty($reportFormValues[$index . '_time'])) {
              $time = $reportFormValues[$index . '_time'];
            }
            else {
              $time = NULL;
            }
            $dateValue = $value;
            if (date('Y-m-d', strtotime($dateValue)) !== $dateValue
              && date('Y-m-d H:i:s', strtotime($dateValue)) !== $dateValue
              && !empty($dateValue)
            ) {
              $dateValue = date('Y-m-d H:i:s', strtotime(CRM_Utils_Date::processDate($value, $time)));
              if ($isEndOfDay) {
                $dateValue = str_replace('00:00:00', '23:59:59', $dateValue);
              }
            }
            $reportFormValues[$index] = $dateValue;
            // Now remove the time keys as no longer needed.
            if (!empty($reportFormValues[$index . '_time'])) {
              unset($reportFormValues[$index . '_time']);
            }
          }
        }
        if (serialize($reportFormValues) !== $report['form_values']) {
          civicrm_api3('ReportInstance', 'create', ['id' => $report['id'], 'form_values' => serialize($reportFormValues)]);
        }
        $date_fields = [];
      }
    }
    return TRUE;
  }

}
