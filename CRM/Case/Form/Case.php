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
 * This class generates form components for case activity.
 */
class CRM_Case_Form_Case extends CRM_Core_Form {
  use CRM_Custom_Form_CustomDataTrait;
  use CRM_Case_Form_CaseFormTrait;

  /**
   * The context
   *
   * @var string
   */
  public $_context = 'case';

  /**
   * Case Id
   *
   * @var int
   *
   * @internal
   *
   * use getCaseID to access.
   */
  public $_caseId = NULL;

  /**
   * Client Id
   * @var int
   */
  public $_currentlyViewedContactId = NULL;

  /**
   * Activity Type File
   * @var int
   */
  public $_activityTypeFile = NULL;

  /**
   * Logged in contact Id
   * @var int
   */
  public $_currentUserId = NULL;

  /**
   * Activity type Id
   * @var int
   */
  public $_activityTypeId = NULL;

  /**
   * Activity type Id
   * @var int
   */
  public $_activityId = NULL;

  /**
   * Action
   * @var int
   */
  public $_action;

  /**
   * Case type id
   * @var int
   */
  public $_caseTypeId = NULL;

  public $submitOnce = TRUE;

  /**
   * @var float|int|mixed|string|null
   *
   * This is inconsistently set & likely to be replaced by a local variable or getter.
   */
  public $_contactID;

  /**
   * @var float|int|mixed|string|null
   * @deprecated
   *
   * This is inconsistently set & likely to be replaced by a local variable or getter.
   */
  public $_caseStatusId;

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
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
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
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
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
          CRM_Core_Error::statusBounce(ts('You do not have any active %1', [1 => $label]));
        }
      }
    }

    if ($this->_action & CRM_Core_Action::ADD) {
      $this->_activityTypeId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Open Case');
      if (!$this->_activityTypeId) {
        CRM_Core_Error::statusBounce(ts('The Open Case activity type is missing or disabled. Please have your site administrator check Administer > Option Lists > Activity Types for the CiviCase component.'));
      }
    }

    //check for case permissions.
    if (!CRM_Case_BAO_Case::accessCiviCase()) {
      CRM_Core_Error::statusBounce(ts('You are not authorized to access this page.'));
    }
    if (($this->_action & CRM_Core_Action::ADD) &&
      (!CRM_Core_Permission::check('access all cases and activities') &&
        !CRM_Core_Permission::check('add cases')
      )
    ) {
      CRM_Core_Error::statusBounce(ts('You are not authorized to access this page.'));
    }

    if ($this->_activityTypeFile = CRM_Activity_BAO_Activity::getFileForActivityTypeId($this->_activityTypeId,
        'Case'
      )
    ) {
      $this->assign('activityTypeFile', $this->_activityTypeFile);
    }

    $details = CRM_Case_PseudoConstant::caseActivityType(FALSE);

    $this->setTitle($details[$this->_activityTypeId]['label']);
    $this->assign('activityType', $details[$this->_activityTypeId]['label']);
    $this->assign('activityTypeDescription', $details[$this->_activityTypeId]['description']);

    if (isset($this->_currentlyViewedContactId)) {
      $contact = new CRM_Contact_DAO_Contact();
      $contact->id = $this->_currentlyViewedContactId;
      if (!$contact->find(TRUE)) {
        CRM_Core_Error::statusBounce(ts('Client contact does not exist: %1', [1 => $this->_currentlyViewedContactId]));
      }
    }
    $this->assign('clientName', isset($this->_currentlyViewedContactId) ? $contact->display_name : NULL);

    $this->_currentUserId = CRM_Core_Session::getLoggedInContactID();

    CRM_Case_Form_Activity_OpenCase::preProcess($this);

    if ($this->isSubmitted()) {
      // The custom data fields are added to the form by an ajax form.
      // However, if they are not present in the element index they will
      // not be available from `$this->getSubmittedValue()` in post process.
      // We do not have to set defaults or otherwise render - just add to the element index.
      $this->addCustomDataFieldsToForm('Case', array_filter([
        'id' => $this->getCaseID(),
        'case_type_id' => $this->getSubmittedValue('case_type_id'),
      ]));
      $this->addCustomDataFieldsToForm('Activity', [
        'activity_type_id' => $this->_activityTypeId,
      ]);
    }
    // Used for loading custom data fields
    $this->assign('activityTypeID', $this->_activityTypeId);
    $this->assign('caseTypeID', $this->getSubmittedValue('case_type_id') ?: $this->getCaseValue('case_type_id'));
  }

  /**
   * Get the selected Case ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getCaseID(): ?int {
    if (!isset($this->_caseId)) {
      $this->_caseId = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    }
    return $this->_caseId;
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues(): array {
    if ($this->_action & CRM_Core_Action::DELETE || $this->_action & CRM_Core_Action::RENEW) {
      return [];
    }
    return CRM_Case_Form_Activity_OpenCase::setDefaultValues($this);
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

    CRM_Case_Form_Activity_OpenCase::buildQuickForm($this);
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
    $className = "CRM_Case_Form_Activity_OpenCase";
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
   * Submit the form with given params.
   *
   * @param $params
   */
  public function submit(&$params) {
    $params['now'] = date("Ymd");

    // 1. call begin post process
    if ($this->_activityTypeFile) {
      CRM_Case_Form_Activity_OpenCase::beginPostProcess($this, $params);
    }

    $params['custom'] = CRM_Core_BAO_CustomField::postProcess(
      $this->getSubmittedValues(),
      NULL,
      'Case'
    );

    // 2. create/edit case
    if (!empty($params['case_type_id'])) {
      $params['case_type'] = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseType', $params['case_type_id'], 'name', 'id');
      $params['subject'] = $params['activity_subject'];
      // 'civicrm_case.details' is not used in core but is used in the CiviCase extension
      $params['details'] = $params['activity_details'];
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
    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($this->getSubmittedValues(),
      $this->_activityId,
      'Activity'
    );

    // 4. call end post process
    if ($this->_activityTypeFile) {
      CRM_Case_Form_Activity_OpenCase::endPostProcess($this, $params);
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
