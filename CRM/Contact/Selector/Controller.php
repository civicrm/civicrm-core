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
class CRM_Contact_Selector_Controller extends CRM_Core_Selector_Controller {

  /**
   * Default function for qill.
   *
   * If needed to be implemented, we expect the subclass to do it
   *
   * @return string
   *   the status message
   */
  public function getQill() {
    return $this->_object->getQILL();
  }

}
