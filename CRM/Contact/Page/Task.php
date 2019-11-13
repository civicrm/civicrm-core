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
 * This is a dummy class that does nothing at the moment.
 *
 * The template is used primarily for displaying result page
 * of tasks performed on contacts. Contacts are searched/selected
 * and then subjected to Tasks/Actions.
 */
class CRM_Contact_Page_Task extends CRM_Core_Page {

  /**
   * Returns the page title.
   *
   * @return string
   *   the title of the page
   */
  public function getTitle() {
    return "Task Results";
  }

}
