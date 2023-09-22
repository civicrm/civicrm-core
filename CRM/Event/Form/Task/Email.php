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
 * This class provides the functionality to email a group of
 * contacts.
 */
class CRM_Event_Form_Task_Email extends CRM_Event_Form_Task {
  use CRM_Contact_Form_Task_EmailTrait;

  /**
   * Only send one email per contact.
   *
   * This has historically been done for contributions & makes sense if
   * no entity specific tokens are in use.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  protected function isGroupByContact(): bool {
    return !empty($this->getMessageTokens()['participant']) || !empty($this->getMessageTokens()['event']);
  }

}
