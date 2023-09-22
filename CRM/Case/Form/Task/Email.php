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
 * This class provides the functionality to email a group of contacts.
 */
class CRM_Case_Form_Task_Email extends CRM_Case_Form_Task {
  use CRM_Contact_Form_Task_EmailTrait;

  /**
   * Getter for isSearchContext.
   *
   * @return bool
   */
  public function isSearchContext(): bool {
    return FALSE;
  }

  /**
   * List available tokens for this form.
   *
   * @todo - this is not standard behaviour. We should either stop filtering
   * case tokens by type and just remove this function (which would allow
   * domain tokens to show up too) or
   * resolve https://lab.civicrm.org/dev/core/-/issues/2788
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function listTokens() {
    $tokens = CRM_Core_SelectValues::contactTokens();

    if ($this->getCaseID()) {
      // For a single case, list tokens relevant for only that case type
      $caseTypeId = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case', $this->getCaseID(), 'case_type_id');
      $tokens += CRM_Core_SelectValues::caseTokens($caseTypeId);
    }

    return $tokens;
  }

  /**
   * Get the subject for the message.
   *
   * The case handling should possibly be on the case form.....
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  protected function getSubject():string {
    $subject = $this->getSubmittedValue('subject');
    // CRM-5916: prepend case id hash to CiviCase-originating emails’ subjects
    if ($this->getCaseID()) {
      $hash = substr(sha1(CIVICRM_SITE_KEY . $this->getCaseID()), 0, 7);
      $subject = "[case #$hash] $subject";
    }
    return $subject;
  }

  /**
   * Get the result rows to email.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getRowsForEmails(): array {
    $formattedContactDetails = [];
    foreach ($this->getEmails() as $details) {
      $contactID = $details['contact_id'];
      $index = $contactID . '::' . $details['email'];
      $formattedContactDetails[$index] = $details;
      $formattedContactDetails[$index]['schema'] = ['contactId' => $contactID, 'caseId' => $this->getCaseID()];
    }
    return $formattedContactDetails;
  }

}
