<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This class generates form components for OpenCase Activity.
 */
class CRM_Case_Form_Activity_OpenCase {

  /**
   * The id of the client associated with this case.
   *
   * @var int
   */
  public $_contactID;

  /**
   * @param CRM_Core_Form $form
   */
  public static function preProcess(&$form) {
    //get multi client case configuration
    $xmlProcessorProcess = new CRM_Case_XMLProcessor_Process();
    $form->_allowMultiClient = (bool) $xmlProcessorProcess->getAllowMultipleCaseClients();

    if ($form->_context == 'caseActivity') {
      $contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $form);
      $atype = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Change Case Start Date');
      $caseId = CRM_Utils_Array::first($form->_caseId);
      $form->assign('changeStartURL', CRM_Utils_System::url('civicrm/case/activity',
          "action=add&reset=1&cid=$contactID&caseid={$caseId}&atype=$atype"
        )
      );
      return;
    }

    $form->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $form);
    $form->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $form);
    $form->assign('context', $form->_context);

    // check if the case type id passed in url is a valid one
    $caseTypeId = CRM_Utils_Request::retrieve('ctype', 'Positive', $form);
    $caseTypes = CRM_Case_BAO_Case::buildOptions('case_type_id', 'create');
    $form->_caseTypeId = array_key_exists($caseTypeId, $caseTypes) ? $caseTypeId : NULL;

    // check if the case status id passed in url is a valid one
    $caseStatusId = CRM_Utils_Request::retrieve('case_status_id', 'Positive', $form);
    $caseStatus = CRM_Case_PseudoConstant::caseStatus();
    $form->_caseStatusId = array_key_exists($caseStatusId, $caseStatus) ? $caseStatusId : NULL;

    // Add attachments
    CRM_Core_BAO_File::buildAttachment($form, 'civicrm_activity', $form->_activityId);
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/case', 'reset=1'));
  }

  /**
   * Set default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @param CRM_Core_Form $form
   */
  public static function setDefaultValues(&$form) {
    $defaults = array();
    if ($form->_context == 'caseActivity') {
      return $defaults;
    }

    list($defaults['start_date'], $defaults['start_date_time']) = CRM_Utils_Date::setDateDefaults(NULL, 'activityDateTime');

    // set default case status, case type, encounter medium, location type and phone type defaults are set in DB
    if ($form->_caseStatusId) {
      $caseStatus = $form->_caseStatusId;
    }
    else {
      $caseStatus = CRM_Core_OptionGroup::values('case_status', FALSE, FALSE, FALSE, 'AND is_default = 1');
      if (count($caseStatus) == 1) {
        $caseStatus = key($caseStatus); //$defaults['status_id'] = key($caseStatus);
      }
    }
    $defaults['status_id'] = $caseStatus;

    // set default case type passed in url
    if ($form->_caseTypeId) {
      $defaults['case_type_id'] = $form->_caseTypeId;
    }
    else {
      // TODO: Not possible yet to set a default case type in the system
      // For now just add the convenience of auto-selecting if there is only one option
      $caseTypes = CRM_Case_BAO_Case::buildOptions('case_type_id', 'create');
      if (count($caseTypes) == 1) {
        reset($caseTypes);
        $defaults['case_type_id'] = key($caseTypes);
      }
    }

    $medium = CRM_Core_OptionGroup::values('encounter_medium', FALSE, FALSE, FALSE, 'AND is_default = 1');
    if (count($medium) == 1) {
      $defaults['medium_id'] = key($medium);
    }

    $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();
    if ($defaultLocationType->id) {
      $defaults['location[1][location_type_id]'] = $defaultLocationType->id;
    }

    $phoneType = CRM_Core_OptionGroup::values('phone_type', FALSE, FALSE, FALSE, 'AND is_default = 1');
    if (count($phoneType) == 1) {
      $defaults['location[1][phone][1][phone_type_id]'] = key($phoneType);
    }

    return $defaults;
  }

  /**
   * @param CRM_Case_Form_Case $form
   */
  public static function buildQuickForm(&$form) {
    if ($form->_context == 'caseActivity') {
      return;
    }
    if ($form->_context == 'standalone') {
      $form->addEntityRef('client_id', ts('Client'), array(
          'create' => TRUE,
          'multiple' => $form->_allowMultiClient,
        ), TRUE);
    }

    $element = $form->addField('case_type_id', array(
      'context' => 'create',
      'entity' => 'Case',
      'onchange' => "CRM.buildCustomData('Case', this.value);",
    ), TRUE);
    if ($form->_caseTypeId) {
      $element->freeze();
    }

    $csElement = $form->addField('status_id', array(
      'context' => 'create',
      'entity' => 'Case',
    ), TRUE);
    if ($form->_caseStatusId) {
      $csElement->freeze();
    }

    $form->add('text', 'duration', ts('Activity Duration'), array('size' => 4, 'maxlength' => 8));
    $form->addRule('duration', ts('Please enter the duration as number of minutes (integers only).'), 'positiveInteger');

    if ($form->_currentlyViewedContactId) {
      list($displayName) = CRM_Contact_BAO_Contact::getDisplayAndImage($form->_currentlyViewedContactId);
      $form->assign('clientName', $displayName);
    }

    $form->addDate('start_date', ts('Case Start Date'), TRUE, array('formatType' => 'activityDateTime'));

    $form->addField('medium_id', array('entity' => 'activity', 'context' => 'create'), TRUE);

    // calling this field activity_location to prevent conflict with contact location fields
    $form->add('text', 'activity_location', ts('Location'), CRM_Core_DAO::getAttribute('CRM_Activity_DAO_Activity', 'location'));

    $form->add('wysiwyg', 'activity_details', ts('Details'), array('rows' => 4, 'cols' => 60), FALSE);

    $form->addButtons(array(
        array(
          'type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'upload',
          'name' => ts('Save and New'),
          'subName' => 'new',
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  /**
   * Process the form submission.
   *
   *
   * @param CRM_Core_Form $form
   * @param array $params
   */
  public static function beginPostProcess(&$form, &$params) {
    if ($form->_context == 'caseActivity') {
      return;
    }

    if ($form->_context == 'standalone') {
      $params['client_id'] = explode(',', $params['client_id']);
      $form->_currentlyViewedContactId = $params['client_id'][0];
    }

    // for open case start date should be set to current date
    $params['start_date'] = CRM_Utils_Date::processDate($params['start_date'], $params['start_date_time']);

    // rename activity_location param to the correct column name for activity DAO
    $params['location'] = CRM_Utils_Array::value('activity_location', $params);

    // Add attachments
    CRM_Core_BAO_File::formatAttachment(
      $params,
      $params,
      'civicrm_activity',
      $form->_activityId
    );

  }

  /**
   * Global validation rules for the form.
   *
   * @param $fields
   * @param $files
   * @param CRM_Core_Form $form
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields, $files, $form) {
    if ($form->_context == 'caseActivity') {
      return TRUE;
    }

    $errors = array();
    return $errors;
  }

  /**
   * Process the form submission.
   *
   * @param CRM_Core_Form $form
   * @param array $params
   */
  public static function endPostProcess(&$form, &$params) {
    if ($form->_context == 'caseActivity') {
      return;
    }

    $xmlProcessorProcess = new CRM_Case_XMLProcessor_Process();
    $isMultiClient = $xmlProcessorProcess->getAllowMultipleCaseClients();

    if (!$isMultiClient && !$form->_currentlyViewedContactId) {
      CRM_Core_Error::fatal('Required parameter missing for OpenCase - end post processing');
    }

    if (!$form->_currentUserId ||
      !$params['case_id'] ||
      !$params['case_type']
    ) {
      CRM_Core_Error::fatal('Required parameter missing for OpenCase - end post processing');
    }

    // 1. create case-contact
    if ($isMultiClient && $form->_context == 'standalone') {
      foreach ($params['client_id'] as $cliId) {
        if (empty($cliId)) {
          CRM_Core_Error::fatal('client_id cannot be empty');
        }
        $contactParams = array(
          'case_id' => $params['case_id'],
          'contact_id' => $cliId,
        );
        CRM_Case_BAO_CaseContact::create($contactParams);
      }
    }
    else {
      $contactParams = array(
        'case_id' => $params['case_id'],
        'contact_id' => $form->_currentlyViewedContactId,
      );
      CRM_Case_BAO_CaseContact::create($contactParams);
    }

    // 2. initiate xml processor
    $xmlProcessor = new CRM_Case_XMLProcessor_Process();

    $xmlProcessorParams = array(
      'clientID' => $form->_currentlyViewedContactId,
      'creatorID' => $form->_currentUserId,
      'standardTimeline' => 1,
      'activityTypeName' => 'Open Case',
      'caseID' => $params['case_id'],
      'subject' => $params['activity_subject'],
      'location' => $params['location'],
      'activity_date_time' => $params['start_date'],
      'duration' => CRM_Utils_Array::value('duration', $params),
      'medium_id' => $params['medium_id'],
      'details' => $params['activity_details'],
      'relationship_end_date' => CRM_Utils_Array::value('end_date', $params),
    );

    if (array_key_exists('custom', $params) && is_array($params['custom'])) {
      $xmlProcessorParams['custom'] = $params['custom'];
    }

    // Add parameters for attachments
    $numAttachments = Civi::settings()->get('max_attachments');
    for ($i = 1; $i <= $numAttachments; $i++) {
      $attachName = "attachFile_$i";
      if (isset($params[$attachName]) && !empty($params[$attachName])) {
        $xmlProcessorParams[$attachName] = $params[$attachName];
      }
    }

    $xmlProcessor->run($params['case_type'], $xmlProcessorParams);

    // status msg
    $params['statusMsg'] = ts('Case opened successfully.');

    $buttonName = $form->controller->getButtonName();
    $session = CRM_Core_Session::singleton();
    if ($buttonName == $form->getButtonName('upload', 'new')) {
      if ($form->_context == 'standalone') {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/case/add',
          'reset=1&action=add&context=standalone'
        ));
      }
      else {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view/case',
          "reset=1&action=add&context=case&cid={$form->_contactID}"
        ));
      }
    }
  }

}
