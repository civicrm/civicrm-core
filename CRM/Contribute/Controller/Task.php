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
 * Class CRM_Export_Controller_Standalone
 */
class CRM_Contribute_Controller_Task extends CRM_Core_Controller_Task {

  /**
   * Get the name used to construct the class.
   *
   * @return string
   */
  public function getEntity():string {
    return 'Contribution';
  }

  /**
   * Get the available tasks for the entity.
   *
   * @return array
   */
  public function getAvailableTasks():array {
    return CRM_Contribute_Task::tasks();
  }

  /**
   * Override parent to avoid e-notice if the page is 'Search'.
   *
   * There are no form values for Search when the standalone processor is used
   * - move along.
   *
   * @param string $pageName
   *
   * @return array
   */
  public function exportValues($pageName = NULL) {
    if ($pageName === 'Search') {
      return [];
    }
    return parent::exportValues($pageName);
  }

}
