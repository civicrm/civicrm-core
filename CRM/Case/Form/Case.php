<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class generates form components for case activity.
 */
class CRM_Case_Form_Case extends CRM_Core_Form {

  /**
   * The context
   *
   * @var string
   */
  public $_context = 'case';

  /**
   * Case Id
   */
  public $_caseId = NULL;

  /**
   * Client Id
   */
  public $_currentlyViewedContactId = NULL;

  /**
   * Activity Type File
   */
  public $_activityTypeFile = NULL;

  /**
   * Logged in contact Id
   */
  public $_currentUserId = NULL;

  /**
   * Activity type Id
   */
  public $_activityTypeId = NULL;

  /**
   * Activity type Id
   */
  public $_activityId = NULL;

  /**
   * Action
   */
  public $_action;

  /**
   * Case type id
   */
  public $_caseTypeId = NULL;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Case';
  }

  /**
   * Get the entity id being edited.
   *
   * @return int|null
   */
  public function getEntityId() {
    return $this->_caseId;
  }

  /**
   * Get the entity subtype ID being edited
   *
   * @param $subTypeId
   *
   * @return int|null
   */
  public function getEntitySubTypeId($subTypeId) {
    if ($subTypeId) {
      return $subTypeId;
    }
    return $this->_caseTypeId;
  }

  /**
   * Build the form object.
   */
  public function preProcess() {
    if (empty($this->_action)) {
      $this->_action = CRM_Core_Action::ADD;
    }

    $this->_caseId = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    $this->_currentlyViewedContactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    if ($this->_action & CRM_Core_Action::ADD && !$this->_currentlyViewedContactId) {
      // check for add contacts permissions
      if (!CRM_Core_Permission::check('add contacts')) {
        CRM_Utils_System::permissionDenied();
        return;
      }
    }

    //CRM-4418
    if (!CRM_Core_Permission::checkActionPermission('CiviCase', $this->_action)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
    }

    if ($this->_action & CRM_Core_Action::DELETE || $this->_action & CRM_Core_Action::RENEW) {
      return TRUE;
    }

    if (!$this->_caseId) {
      $caseAttributes = [
        'case_type_id' => ts('Case Type'),
        'status_id' => ts('Case Status'),
        'medium_id' => ts('Activity Medium'),
      ];

      foreach ($caseAttributes as $key => $label) {
        if (!CRM_Case_BAO_Case::buildOptions($key, 'create')) {
          CRM_Core_Error::fatal(ts('You do not have any active %1', [1 => $label]));
        }
      }
    }

    if ($this->_action & CRM_Core_Action::ADD) {
      $this->_activityTypeId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Open Case');
      if (!$this->_activityTypeId) {
        CRM_Core_Error::fatal(ts('The Open Case activity type is missing or disabled. Please have your site administrator check Administer > Option Lists > Activity Types for the CiviCase component.'));
      }
    }

    //check for case permissions.
    if (!CRM_Case_BAO_Case::accessCiviCase()) {
      CRM_Core_Error::fatal(ts('You are not authorized to access this page.'));
    }
    if (($this->_action & CRM_Core_Action::ADD) &&
      (!CRM_Core_Permission::check('access all cases and activities') &&
        !CRM_Core_Permission::check('add cases')
      )
    ) {
      CRM_Core_Error::fatal(ts('You are not authorized to access this page.'));
    }

    if ($this->_activityTypeFile = CRM_Activity_BAO_Activity::getFileForActivityTypeId($this->_activityTypeId,
        'Case'
      )
    ) {
      $this->assign('activityTypeFile', $this->_activityTypeFile);
    }

    $details = CRM_Case_PseudoConstant::caseActivityType(FALSE);

    CRM_Utils_System::setTitle($details[$this->_activityTypeId]['label']);
    $this->assign('activityType', $details[$this->_activityTypeId]['label']);
    $this->assign('activityTypeDescription', $details[$this->_activityTypeId]['description']);

    if (isset($this->_currentlyViewedContactId)) {
      $contact = new CRM_Contact_DAO_Contact();
      $contact->id = $this->_currentlyViewedContactId;
      if (!$contact->find(TRUE)) {
        CRM_Core_Error::statusBounce(ts('Client contact does not exist: %1', [1 => $this->_currentlyViewedContactId]));
      }
      $this->assign('clientName', $contact->display_name);
    }

    $session = CRM_Core_Session::singleton();
    $this->_currentUserId = $session->get('userID');

    //Add activity custom data is included in this page
    CRM_Custom_Form_CustomData::preProcess($this, NULL, $this->_activityTypeId, 1, 'Activity');
    $className = "CRM_Case_Form_Activity_{$this->_activityTypeFile}";
    $className::preProcess($this);
    $activityGroupTree = $this->_groupTree;

    // Add case custom data to form
    $caseTypeId = CRM_Utils_Array::value('case_type_id', CRM_Utils_Request::exportValues(), $this->_caseTypeId);
    CRM_Custom_Form_CustomData::addToForm($this, $caseTypeId);

    // so that grouptree is not populated with case fields, since the grouptree is used
    // for populating activity custom fields.
    $this->_groupTree = $activityGroupTree;
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    if ($this->_action & CRM_Core_Action::DELETE || $this->_action & CRM_Core_Action::RENEW) {
      return TRUE;
    }
    $className = "CRM_Case_Form_Activity_{$this->_activityTypeFile}";
    $defaults = $className::setDefaultValues($this);
    $defaults = array_merge($defaults, CRM_Custom_Form_CustomData::setDefaultValues($this));
    return $defaults;
  }

  public function buildQuickForm() {
    $xmlProcessorProcess = new CRM_Case_XMLProcessor_Process();
    $isMultiClient = $xmlProcessorProcess->getAllowMultipleCaseClients();
    $this->assign('multiClient', $isMultiClient);

    if ($this->_action & CRM_Core_Action::DELETE || $this->_action & CRM_Core_Action::RENEW) {
      $title = ts('Delete');
      if ($this->_action & CRM_Core_Action::RENEW) {
        $title = ts('Restore');
      }
      $this->addButtons([
        [
          'type' => 'next',
          'name' => $title,
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
      return;
    }

    // Add the activity custom data to the form
    CRM_Custom_Form_CustomData::buildQuickForm($this);

    // we don't want to show button on top of custom form
    $this->assign('noPreCustomButton', TRUE);

    $s = CRM_Core_DAO::getAttribute('CRM_Activity_DAO_Activity', 'subject');
    if (!is_array($s)) {
      $s = [];
    }
    $this->add('text', 'activity_subject', ts('Subject'),
      array_merge($s, [
        'maxlength' => '128',
      ]), TRUE
    );

    $tags = CRM_Core_BAO_Tag::getColorTags('civicrm_case');

    if (!empty($tags)) {
      $this->add('select2', 'tag', ts('Tags'), $tags, FALSE,
        ['class' => 'huge', 'multiple' => 'multiple']
      );
    }

    // build tag widget
    $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_case');
    CRM_Core_Form_Tag::buildQuickForm($this, $parentNames, 'civicrm_case', NULL, FALSE, TRUE);

    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Save'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);

    $className = "CRM_Case_Form_Activity_{$this->_activityTypeFile}";
    $className::buildQuickForm($this);
  }

  /**
   * Add local and global form rules.
   *
   * @return bool
   */
  public function addRules() {
    if ($this->_action & CRM_Core_Action::DELETE || $this->_action & CRM_Core_Action::RENEW) {
      return TRUE;
    }
    $className = "CRM_Case_Form_Activity_{$this->_activityTypeFile}";
    $this->addFormRule([$className, 'formRule'], $this);
    $this->addFormRule(['CRM_Case_Form_Case', 'formRule'], $this);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $values
   *   Posted values of the form.
   *
   * @param $files
   * @param CRM_Core_Form $form
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($values, $files, $form) {
    return TRUE;
  }

  /**
   * Wrapper for unit testing the post process submit function.
   *
   * @param $params
   * @param $activityTypeFile
   * @param $contactId
   * @param $context
   * @return CRM_Case_BAO_Case
   */
  public function testSubmit($params, $activityTypeFile, $contactId, $context = "case") {
    $this->controller = new CRM_Core_Controller();

    $this->_activityTypeFile = $activityTypeFile;
    $this->_currentUserId = $contactId;
    $this->_context = $context;

    return $this->submit($params);
  }

  /**
   * Submit the form with given params.
   *
   * @param $params
   */
  public function submit(&$params) {
    $params['now'] = date("Ymd");

    // 1. call begin post process
    if ($this->_activityTypeFile) {
      $className = "CRM_Case_Form_Activity_{$this->_activityTypeFile}";
      $className::beginPostProcess($this, $params);
    }

    if (!empty($params['hidden_custom']) &&
      !isset($params['custom'])
    ) {
      $params['custom'] = CRM_Core_BAO_CustomField::postProcess(
        $params,
        NULL,
        'Case'
      );
    }

    // 2. create/edit case
    if (!empty($params['case_type_id'])) {
      $params['case_type'] = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseType', $params['case_type_id'], 'name', 'id');
      $params['subject'] = $params['activity_subject'];
    }
    $caseObj = CRM_Case_BAO_Case::create($params);
    $this->_caseId = $params['case_id'] = $caseObj->id;
    // unset any ids, custom data
    unset($params['id'], $params['custom']);

    // add tags if exists
    $tagParams = [];
    if (!empty($params['tag'])) {
      $tagParams = [];
      if (!is_array($params['tag'])) {
        $params['tag'] = explode(',', $params['tag']);
      }
      foreach ($params['tag'] as $tag) {
        $tagParams[$tag] = 1;
      }
    }
    CRM_Core_BAO_EntityTag::create($tagParams, 'civicrm_case', $caseObj->id);

    //save free tags
    if (isset($params['case_taglist']) && !empty($params['case_taglist'])) {
      CRM_Core_Form_Tag::postProcess($params['case_taglist'], $caseObj->id, 'civicrm_case', $this);
    }

    // user context
    $url = CRM_Utils_System::url('civicrm/contact/view/case',
      "reset=1&action=view&cid={$this->_currentlyViewedContactId}&id={$caseObj->id}"
    );
    CRM_Core_Session::singleton()->pushUserContext($url);

    // 3. format activity custom data
    if (!empty($params['hidden_custom'])) {
      $customFields = CRM_Core_BAO_CustomField::getFields('Activity', FALSE, FALSE, $this->_activityTypeId);
      $customFields = CRM_Utils_Array::crmArrayMerge($customFields,
        CRM_Core_BAO_CustomField::getFields('Activity', FALSE, FALSE,
          NULL, NULL, TRUE
        )
      );
      $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
        $this->_activityId,
        'Activity'
      );
    }

    // 4. call end post process
    if ($this->_activityTypeFile) {
      $className::endPostProcess($this, $params);
    }

    return $caseObj;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $transaction = new CRM_Core_Transaction();

    // check if dedupe button, if so return.
    $buttonName = $this->controller->getButtonName();
    if (isset($this->_dedupeButtonName) && $buttonName == $this->_dedupeButtonName) {
      return;
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      $caseDelete = CRM_Case_BAO_Case::deleteCase($this->_caseId, TRUE);
      if ($caseDelete) {
        CRM_Core_Session::setStatus(ts('You can view and / or restore deleted cases by checking the "Deleted Cases" option under Find Cases.'), ts('Case Deleted'), 'success');
      }
      return;
    }

    if ($this->_action & CRM_Core_Action::RENEW) {
      $caseRestore = CRM_Case_BAO_Case::restoreCase($this->_caseId);
      if ($caseRestore) {
        CRM_Core_Session::setStatus(ts('The selected case has been restored.'), ts('Restored'), 'success');
      }
      return;
    }
    // store the submitted values in an array
    $params = $this->controller->exportValues($this->_name);
    $this->submit($params);

    CRM_Core_Session::setStatus($params['statusMsg'], ts('Saved'), 'success');

  }

}
