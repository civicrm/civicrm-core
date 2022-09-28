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

use Civi\Api4\Contribution;

/**
 * This class provides the functionality to email a group of contacts.
 */
class CRM_Contribute_Form_Task_Email extends CRM_Contribute_Form_Task {
  use CRM_Contact_Form_Task_EmailTrait;

  /**
   * Get selected contribution IDs.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getContributionIDs(): array {
    return $this->getIDs();
  }

  /**
   * Get the result rows to email.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getRows(): array {
    $contributionDetails = Contribution::get(FALSE)
      ->setSelect(['contact_id', 'id'])
      ->addWhere('id', 'IN', $this->getContributionIDs())
      ->execute()
      // Note that this indexing means that only the last
      // contribution per contact is resolved to tokens.
      // this is long-standing functionality, albeit possibly
      // not thought through.
      ->indexBy('contact_id');

    // format contact details array to handle multiple emails from same contact
    $formattedContactDetails = [];
    foreach ($this->getEmails() as $details) {
      $formattedContactDetails[$details['contact_id'] . '::' . $details['email']] = $details;
      if (!empty($contributionDetails[$details['contact_id']])) {
        $formattedContactDetails[$details['contact_id'] . '::' . $details['email']]['schema'] = ['contributionId' => $contributionDetails[$details['contact_id']]['id']];
      }

    }
    return $formattedContactDetails;
  }

}
