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
 * This class gets the name of the file to upload
 */
class CRM_Member_Export_Form_Select extends CRM_Export_Form_Select {

  /**
   * Get the name of the relevant form task class.
   *
   * @return string
   */
  protected function getFormTaskName(): string {
    return 'CRM_Member_Form_Task';
  }

  /**
   * Get the entity short name for a given export.
   *
   * @return string
   */
  protected function getEntityShortName(): string {
    return 'Member';
  }

  /**
   * Get the table name for the entity to export.
   *
   * @return string
   */
  protected function getTableName(): string {
    return 'civicrm_membership';
  }

  /**
   * Legacy code extracted - kinda confusing why it's not just getEntityShortName().
   *
   * Case related? -https://github.com/civicrm/civicrm-core/pull/12110
   *
   * @return string
   */
  protected function getEntityShortNameForThis() {
    return $this->getEntityShortName();
  }

  /**
   * Get the task titles.
   *
   * @return array
   */
  protected function getTaskTitles() {
    return CRM_Member_Task::taskTitles();
  }

  /**
   * Call the preprocessing function.
   */
  protected function callPreProcessing(): void {
    CRM_Member_Form_Task::preProcessCommon($this);
  }

}
