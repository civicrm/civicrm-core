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
 * This class provides the functionality to create PDF letter for a group of contacts or a single contact.
 */
class CRM_Contact_Form_Task_PDF extends CRM_Contact_Form_Task {

  use CRM_Contact_Form_Task_PDFTrait;

  /**
   * All the existing templates in the system.
   *
   * @var array
   */
  public $_templates = NULL;

  public $_single = NULL;

  public $_cid = NULL;

  public $_activityId = NULL;

  /**
   * This should be replaced by an externally supposed getCaseID() method.
   *
   * Property may change as it is not really required.
   *
   * @var int|null
   *
   * @internal
   */
  public $_caseId;

  /**
   * This should be replaced by an externally supposed getCaseID() method.
   *
   * Property may change as it is kinda weird.
   *
   * @var int|null
   *
   * @internal
   */
  public $_caseIds;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $this->preProcessPDF();

    // store case id if present
    $this->_caseId = CRM_Utils_Request::retrieve('caseid', 'CommaSeparatedIntegers', $this, FALSE);
    if (!empty($this->_caseId) && strpos($this->_caseId, ',')) {
      $this->_caseIds = explode(',', $this->_caseId);
      unset($this->_caseId);
    }

    // retrieve contact ID if this is 'single' mode
    $cid = CRM_Utils_Request::retrieve('cid', 'CommaSeparatedIntegers', $this, FALSE);

    if ($cid) {
      // this is true in non-search context / single mode
      // in search context 'id' is the default profile id for search display
      // CRM-11227
      $this->_activityId = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);
      $this->_contactIds = explode(',', $cid);
      // put contact display name in title for single contact mode
      if (count($this->_contactIds) === 1) {
        CRM_Utils_System::setTitle(ts('Print/Merge Document for %1', [1 => CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $cid, 'display_name')]));
      }
      $this->_single = TRUE;
    }
    else {
      parent::preProcess();
    }
    $this->assign('single', $this->_single);
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = $this->getPDFDefaultValues();
    if (isset($this->_activityId)) {
      $params = ['id' => $this->_activityId];
      CRM_Activity_BAO_Activity::retrieve($params, $defaults);
      $defaults['html_message'] = $defaults['details'] ?? NULL;
    }
    return $defaults;
  }

  /**
   * {@inheritDoc}
   */
  protected function getFieldsToExcludeFromPurification(): array {
    return [
      'details',
      'activity_details',
      'html_message',
    ];
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    //enable form element
    $this->assign('suppressForm', FALSE);
    $this->addPDFElementsToForm();
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens() {
    $tokens = CRM_Core_SelectValues::contactTokens();
    if (isset($this->_caseId) || isset($this->_caseIds)) {
      // For a single case, list tokens relevant for only that case type
      $caseTypeId = isset($this->_caseId) ? CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case', $this->_caseId, 'case_type_id') : NULL;
      $tokens += CRM_Core_SelectValues::caseTokens($caseTypeId);
    }
    return $tokens;
  }

  /**
   * Get the rows from the results to be pdf-d.
   *
   * @todo the case handling should be in the case pdf task.
   * It needs fixing to support standalone & some url fixes
   *
   * similar to https://github.com/civicrm/civicrm-core/pull/21688
   *
   * @return array
   */
  protected function getRows(): array {
    $rows = [];
    foreach ($this->_contactIds as $index => $contactID) {
      $caseID = $this->_caseId;
      if (empty($caseID) && !empty($this->_caseIds[$index])) {
        $caseID = $this->_caseIds[$index];
      }
      $rows[] = ['contact_id' => $contactID, 'schema' => ['caseId' => $caseID, 'contactId' => $contactID]];
    }
    return $rows;
  }

}
