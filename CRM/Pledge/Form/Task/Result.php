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
 * Used for displaying results.
 */
class CRM_Pledge_Form_Task_Result extends CRM_Pledge_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addButtons([
        [
          'type' => 'done',
          'name' => ts('Done'),
          'isDefault' => TRUE,
        ],
    ]);
  }

}
