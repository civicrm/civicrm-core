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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class generates form task actions for CiviCase.
 */
class CRM_Case_Form_Task extends CRM_Core_Form_Task {

  /**
   * Case ID.
   *
   * Even though there may be more than one case ID only one can be used
   * in tokens - this is it!
   *
   * Case id comes in from caseid in the url.
   *
   * @var int caseID.
   */
  protected $caseID;

  /**
   * Case IDs.
   *
   * In some cases an activity might be filed on multiple cases.
   *
   * Case ids come in from caseid in the url (it can be comma separated)
   * @var []int caseID.
   */
  protected $caseIDs;

  /**
   * Must be set to entity table name (eg. civicrm_participant) by child class
   * @var string
   */
  public static $tableName = 'civicrm_case';
  /**
   * Must be set to entity shortname (eg. event)
   * @var string
   */
  public static $entityShortname = 'case';

  /**
   * @inheritDoc
   */
  public function setContactIDs() {
    // @todo Parameters shouldn't be needed and should be class member
    // variables instead, set appropriately by each subclass.
    $this->_contactIds = $this->getContactIDsFromComponent($this->_entityIds,
      'civicrm_case_contact', 'case_id'
    );
  }

  /**
   * Get the query mode (eg. CRM_Core_BAO_Query::MODE_CASE)
   *
   * @return int
   */
  public function getQueryMode() {
    return CRM_Contact_BAO_Query::MODE_CASE;
  }

  /**
   * Override of CRM_Core_Form_Task::orderBy()
   *
   * @return string
   */
  public function orderBy() {
    if (empty($this->_entityIds)) {
      return '';
    }
    $order_array = [];
    foreach ($this->_entityIds as $item) {
      // Ordering by conditional in mysql. This evaluates to 0 or 1, so we
      // need to order DESC to get the '1'.
      $order_array[] = 'case_id = ' . CRM_Core_DAO::escapeString($item) . ' DESC';
    }
    return 'ORDER BY ' . implode(',', $order_array);
  }

  /**
   * Get the name of the table for the relevant entity.
   *
   * @return string
   */
  public function getTableName() {
    return 'civicrm_case';
  }

  /**
   * Get the entity alias field.
   *
   * @return string
   */
  public function getEntityAliasField() {
    return 'case_id';
  }

  /**
   * Get the (first) case id. This is
   *
   * @return int
   */
  protected function getCaseID(): ?int {
    if (!isset($this->caseID)) {
      $this->caseID = $this->getCaseIDS()[0];
    }
    return $this->caseID;
  }

  /**
   * Get case IDs.
   *
   * These are used for filing on a case.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getCaseIDS(): array {
    if (!isset($this->caseIDS)) {
      $this->caseIDS = explode(',', CRM_Utils_Request::retrieve('caseid', 'CommaSeparatedIntegers', $this));
    }
    return $this->caseIDs;
  }

  /**
   * Get the schema for which tokens should be listed.
   */
  protected function getTokenSchema(): array {
    return ['caseId'];
  }

  /**
   * Get the subject for the message.
   *
   * The case handling should possibly be on the case form.....
   *
   * @param string $subject
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  protected function getSubject(string $subject):string {
    // CRM-5916: prepend case id hash to CiviCase-originating emails’ subjects
    if ($this->getCaseID()) {
      $hash = substr(sha1(CIVICRM_SITE_KEY . $this->getCaseID()), 0, 7);
      $subject = "[case #$hash] $subject";
    }
    return $subject;
  }

  /**
   * Get the schema for token rendering.
   *
   * Contact is included by default.
   *
   * e.g return ['contributionId' => 3]
   *
   * @param int $contactID
   *
   * @return array
   */
  protected function getTokenContext(int $contactID): array {
    $caseId = $this->getCaseID($contactID);
    return $caseId ? ['caseId' => $caseId] : [];
  }

  /**
   * Get the url to redirect the user to.
   *
   * @throws \CRM_Core_Exception
   */
  protected function getRedirectURL(): string {
    $firstContactID = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseContact', $this->getCaseID(),
      'contact_id', 'case_id'
    );
    return CRM_Utils_System::url('civicrm/contact/view/case',
      "&reset=1&action=view&cid={$firstContactID}&id=" . $this->getCaseID()
    );
  }

}
