<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for OpenCase Activity
 *
 */
class CRM_Case_Form_Activity_OpenCase {

  /**
   * the id of the client associated with this case
   *
   * @var int
   * @public
   */
  public $_contactID;

  static function preProcess(&$form) {
    //get multi client case configuration
    $xmlProcessorProcess = new CRM_Case_XMLProcessor_Process();
    $form->_allowMultiClient = (bool)$xmlProcessorProcess->getAllowMultipleCaseClients();

    if ($form->_context == 'caseActivity') {
      $contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $form);
      $atype = CRM_Core_OptionGroup::getValue('activity_type',
        'Change Case Start Date',
        'name'
      );
      $form->assign('changeStartURL', CRM_Utils_System::url('civicrm/case/activity',
          "action=add&reset=1&cid=$contactID&caseid={$form->_caseId}&atype=$atype"
        )
      );
      return;
    }

    $form->_context = CRM_Utils_Request::retrieve('context', 'String', $form);
    $form->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $form);
    $form->assign('context', $form->_context);

    // Add attachments
    CRM_Core_BAO_File::buildAttachment( $form, 'civicrm_activity', $form->_activityId );
  }

  /**
   * This function sets the default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  static function setDefaultValues(&$form) {
    $defaults = array();
    if ($form->_context == 'caseActivity') {
      return $defaults;
    }

    list($defaults['start_date'], $defaults['start_date_time']) = CRM_Utils_Date::setDateDefaults(NULL, 'activityDateTime');

    // set default case status, case type, encounter medium, location type and phone type defaults are set in DB
    $caseStatus = CRM_Core_OptionGroup::values('case_status', FALSE, FALSE, FALSE, 'AND is_default = 1');
    if (count($caseStatus) == 1) {
      $defaults['status_id'] = key($caseStatus);
    }
    $caseType = CRM_Core_OptionGroup::values('case_type', FALSE, FALSE, FALSE, 'AND is_default = 1');
    if (count($caseType) == 1) {
      $defaults['case_type_id'] = key($caseType);
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

  static function buildQuickForm(&$form) {
    if ($form->_context == 'caseActivity') {
      return;
    }
    if ($form->_context == 'standalone') {
      CRM_Contact_Form_NewContact::buildQuickForm($form);
    }

    $caseType = array('' => '-select-') + CRM_Case_PseudoConstant::caseType();
    $form->add('select', 'case_type_id', ts('Case Type'),
      $caseType, TRUE, array(
        'onchange' =>
        "CRM.buildCustomData( 'Case', this.value );",
      )
    );

    $caseStatus = CRM_Case_PseudoConstant::caseStatus();
    $form->add('select', 'status_id', ts('Case Status'),
      $caseStatus, TRUE
    );

    $form->add('text', 'duration', ts('Duration'), array('size' => 4, 'maxlength' => 8));
    $form->addRule('duration', ts('Please enter the duration as number of minutes (integers only).'), 'positiveInteger');

    if ($form->_currentlyViewedContactId) {
      list($displayName) = CRM_Contact_BAO_Contact::getDisplayAndImage($form->_currentlyViewedContactId);
      $form->assign('clientName', $displayName);
    }

    $form->addDate('start_date', ts('Case Start Date'), TRUE, array('formatType' => 'activityDateTime'));

    $form->add('select', 'medium_id', ts('Medium'),
      CRM_Case_PseudoConstant::encounterMedium(), TRUE
    );

    // calling this field activity_location to prevent conflict with contact location fields
    $form->add('text', 'activity_location', ts('Location'), CRM_Core_DAO::getAttribute('CRM_Activity_DAO_Activity', 'location'));

    $form->addWysiwyg('activity_details', ts('Details'), array('rows' => 4, 'cols' => 60), FALSE);

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
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  static function beginPostProcess(&$form, &$params) {
    if ($form->_context == 'caseActivity') {
      return;
    }

    // set the contact, when contact is selected
    if (isset($params['contact_select_id']) && CRM_utils_Array::value(1, $params['contact_select_id'])) {
      $params['contact_id'] = $params['contact_select_id'][1];
      $form->_currentlyViewedContactId = $params['contact_id'];
    }
    elseif ($form->_allowMultiClient && $form->_context != 'case') {
      $clients = explode(',', $params['contact'][1]);
      $form->_currentlyViewedContactId = $clients[0];
    }

    // for open case start date should be set to current date
    $params['start_date'] = CRM_Utils_Date::processDate($params['start_date'], $params['start_date_time']);
    $caseStatus = CRM_Case_PseudoConstant::caseStatus('name');
    // for resolved case the end date should set to now
    if ($params['status_id'] == array_search('Closed', $caseStatus)) {
      $params['end_date'] = $params['now'];
    }

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
   * global validation rules for the form
   *
   * @param array $values posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($fields, $files, $form) {
    if ($form->_context == 'caseActivity') {
      return TRUE;
    }

    $errors = array();

    if (!$form->_allowMultiClient) {
      //check if contact is selected in standalone mode
      if (isset($fields['contact_select_id'][1]) && !$fields['contact_select_id'][1]) {
        $errors['contact[1]'] = ts('Please select a contact or create new contact');
      }
    }
    else {
      //check selected contact for multi client option
      if (isset($fields['contact'][1]) && !$fields['contact'][1]) {
        $errors['contact[1]'] = ts('Please select a valid contact or create new contact');
      }
    }
    return $errors;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  static function endPostProcess(&$form, &$params) {
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
    if ($isMultiClient && $form->_context != 'case') {
      $client = explode(',', $params['contact'][1]);
      foreach ($client as $key => $cliId) {
        if (empty($cliId)) {
          CRM_Core_Error::fatal('contact_id cannot be empty');
        }
        $contactParams = array(
          'case_id' => $params['case_id'],
          'contact_id' => $cliId,
        );
        CRM_Case_BAO_Case::addCaseToContact($contactParams);
      }
    }
    else {
      $contactParams = array(
        'case_id' => $params['case_id'],
        'contact_id' => $form->_currentlyViewedContactId,
      );
      CRM_Case_BAO_Case::addCaseToContact($contactParams);
      $client = $form->_currentlyViewedContactId;
    }

    // 2. initiate xml processor
    $xmlProcessor = new CRM_Case_XMLProcessor_Process();

    $xmlProcessorParams = array(
      'clientID' => $client,
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
    );

    if (array_key_exists('custom', $params) && is_array($params['custom'])) {
      $xmlProcessorParams['custom'] = $params['custom'];
    }

    // Add parameters for attachments
    $numAttachments = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'max_attachments');
    for ( $i = 1; $i <= $numAttachments; $i++ ) {
      $attachName = "attachFile_$i";
      if ( isset( $params[$attachName] ) && !empty( $params[$attachName] ) ) {
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

