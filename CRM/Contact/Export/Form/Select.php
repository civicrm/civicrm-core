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
class CRM_Contact_Export_Form_Select extends CRM_Export_Form_Select {

  /**
   * Call the pre-processing function.
   */
  protected function callPreProcessing(): void {
    CRM_Contact_Form_Task::preProcessCommon($this);
  }

  /**
   * Does this export offer contact merging.
   *
   * @return bool
   */
  protected function isShowContactMergeOptions() {
    return TRUE;
  }

  /**
   * Get the name of the table for the relevant entity.
   *
   * @return string
   */
  public function getTableName() {
    return 'civicrm_contact';
  }

  /**
   * Get the group by clause for the component.
   *
   * @return string
   */
  public function getEntityAliasField() {
    return 'contact_id';
  }

}
