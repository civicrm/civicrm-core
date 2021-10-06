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
   */
  protected function getRows(): array {
    // format contact details array to handle multiple emails from same contact
    $rows = parent::getRows();
    foreach ($rows as $index => $row) {
      $rows[$index]['schema']['caseId'] = $this->getCaseID();
    }
    return $rows;
  }

}
