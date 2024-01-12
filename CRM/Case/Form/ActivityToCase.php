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
 * This class generates form components for building activity to a case.
 */
class CRM_Case_Form_ActivityToCase extends CRM_Core_Form {

  /**
   * Case Activity being copied or moved
   * @var int
   */
  public $_activityId;


  /**
   * Current CiviCase ID associated with the activity
   * @var int
   */
  public $_currentCaseId;

  /**
   * Build all the data structures needed to build the form.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    $this->_activityId = CRM_Utils_Request::retrieve('activityId', 'Positive');
    if (!$this->_activityId) {
      throw new CRM_Core_Exception('required activity id is missing.');
    }

    $this->_currentCaseId = CRM_Utils_Request::retrieve('caseId', 'Positive');
    $this->assign('currentCaseId', $this->_currentCaseId);
    $this->assign('buildCaseActivityForm', TRUE);

    switch (CRM_Utils_Request::retrieve('fileOnCaseAction', 'String')) {
      case 'move':
        $this->setTitle(ts('Move to Case'));
        break;

      case 'copy':
        $this->setTitle(ts('Copy to Case'));
        break;

    }
  }

  /**
   * Set default values for the form. For edit/view mode.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public function setDefaultValues() {
    $defaults = [];
    $params = ['id' => $this->_activityId];

    CRM_Activity_BAO_Activity::retrieve($params, $defaults);
    $defaults['file_on_case_activity_subject'] = $defaults['subject'] ?? '';
    $defaults['file_on_case_target_contact_id'] = $defaults['target_contact'];

    // If this contact has an open case, supply it as a default
    $cid = CRM_Utils_Request::retrieve('cid', 'Integer');
    if (!$cid) {
      $act = civicrm_api3('Activity', 'getsingle', ['id' => $this->_activityId, 'return' => 'target_contact_id']);
      if (!empty($act['target_contact_id'])) {
        $cid = $act['target_contact_id'][0];
      }
    }
    if ($cid) {
      $cases = civicrm_api3('CaseContact', 'get', [
        'contact_id' => $cid,
        'case_id' => ['!=' => $this->_currentCaseId],
        'case_id.status_id' => ['!=' => "Closed"],
        'case_id.is_deleted' => 0,
        'case_id.end_date' => ['IS NULL' => 1],
        'options' => ['limit' => 1],
        'return' => 'case_id',
      ]);
      foreach ($cases['values'] as $record) {
        $defaults['file_on_case_unclosed_case_id'] = $record['case_id'];
        break;
      }
    }
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addEntityRef('file_on_case_unclosed_case_id', ts('Select Case'), [
      'entity' => 'Case',
      'api' => [
        'extra' => ['contact_id'],
        'params' => [
          'case_id' => ['!=' => $this->_currentCaseId],
          'case_id.status_id' => ['!=' => "Closed"],
          'case_id.end_date' => ['IS NULL' => 1],
        ],
      ],
    ], TRUE);
    $this->addEntityRef('file_on_case_target_contact_id', ts('With Contact(s)'), ['multiple' => TRUE]);
    $this->add('text', 'file_on_case_activity_subject', ts('Subject'), ['size' => 50]);
  }

}
