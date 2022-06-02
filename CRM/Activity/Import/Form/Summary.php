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
 * This class summarizes the import results.
 */
class CRM_Activity_Import_Form_Summary extends CRM_Import_Form_Summary {

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    // set the error message path to display
    $this->assign('errorFile', $this->get('errorFile'));

    $totalRowCount = $this->get('totalRowCount');
    $this->set('totalRowCount', $totalRowCount);

    $invalidRowCount = $this->get('invalidRowCount');
    $onDuplicate = $this->get('onDuplicate');

    $this->assign('dupeError', FALSE);

    if ($onDuplicate == CRM_Import_Parser::DUPLICATE_UPDATE) {
      $dupeActionString = ts('These records have been updated with the imported data.');
    }
    elseif ($onDuplicate == CRM_Import_Parser::DUPLICATE_FILL) {
      $dupeActionString = ts('These records have been filled in with the imported data.');
    }
    else {
      // Skip by default.

      $dupeActionString = ts('These records have not been imported.');

      $this->assign('dupeError', TRUE);

      // Only subtract dupes from successful import if we're skipping.

      $this->set('validRowCount', $totalRowCount - $invalidRowCount);
    }
    $this->assign('dupeActionString', $dupeActionString);

    $properties = [
      'totalRowCount',
      'validRowCount',
      'invalidRowCount',
      'downloadErrorRecordsUrl',
      'groupAdditions',
    ];
    foreach ($properties as $property) {
      $this->assign($property, $this->get($property));
    }
  }

}
