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
    $this->_contactIds = $this->getContactIDs();
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
   * Get the rows from the results to be pdf-d.
   *
   * @return array
   */
  protected function getRows(): array {
    $rows = [];
    foreach ($this->_contactIds as $index => $contactID) {
      $caseID = $this->getVar('_caseId');
      if (empty($caseID) && !empty($this->_caseIds[$index])) {
        $caseID = $this->_caseIds[$index];
      }
      $rows[] = ['contact_id' => $contactID, 'schema' => ['caseId' => $caseID, 'contactId' => $contactID]];
    }
    return $rows;
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

  protected function getContactIDs(): array {
    if (isset($this->_contactIds)) {
      return $this->_contactIds;
    }
    $contactIDSFromUrl = CRM_Utils_Request::retrieve('cid', 'CommaSeparatedIntegers', $this);
    if (!empty($contactIDSFromUrl)) {
      return explode(',', $contactIDSFromUrl);
    }
    // @todo Parameters shouldn't be needed and should be class member
    // variables instead, set appropriately by each subclass.
    return $this->getContactIDsFromComponent($this->_entityIds,
      'civicrm_case_contact', 'case_id'
    );
  }

  /**
   * Get the token processor schema required to list any tokens for this task.
   *
   * @return array
   */
  protected function getTokenSchema(): array {
    return ['contactId', 'caseId'];
  }

}
