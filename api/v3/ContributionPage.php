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
 * This api exposes CiviCRM contribution pages.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create or update a ContributionPage.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   api result array
 */
function civicrm_api3_contribution_page_create($params) {
  $result = _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'ContributionPage');
  CRM_Contribute_PseudoConstant::flush('contributionPageAll');
  CRM_Contribute_PseudoConstant::flush('contributionPageActive');
  return $result;
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array per getfields metadata.
 */
function _civicrm_api3_contribution_page_create_spec(&$params) {
  $params['financial_type_id']['api.required'] = 1;
  $params['payment_processor']['api.aliases'] = ['payment_processor_id'];
  $params['is_active']['api.default'] = 1;
}

/**
 * Returns array of ContributionPage(s) matching a set of one or more group properties.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API Result array Array of matching contribution_pages
 */
function civicrm_api3_contribution_page_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete an existing ContributionPage.
 *
 * This method is used to delete any existing ContributionPage given its id.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API result Array
 */
function civicrm_api3_contribution_page_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Submit a ContributionPage.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_contribution_page_submit($params) {
  $result = CRM_Contribute_Form_Contribution_Confirm::submit($params);
  return civicrm_api3_create_success($result, $params, 'ContributionPage', 'submit');
}

/**
 * Validate ContributionPage submission parameters.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_contribution_page_validate($params) {
  // If we are calling this as a result of a POST action (e.g validating a form submission before first getting payment
  // authorization from a payment processor like Paypal checkout) the lack of a qfKey will not result in a valid
  // one being generated so we generate one first.
  $originalRequest = $_REQUEST;
  $qfKey = CRM_Utils_Array::value('qfKey', $_REQUEST);
  if (!$qfKey) {
    $_REQUEST['qfKey'] = CRM_Core_Key::get('CRM_Core_Controller', TRUE);
  }
  $form = new CRM_Contribute_Form_Contribution_Main();
  $form->controller = new CRM_Core_Controller();
  $form->set('id', $params['id']);
  $form->preProcess();
  $errors = CRM_Contribute_Form_Contribution_Main::formRule($params, [], $form);
  if ($errors === TRUE) {
    $errors = [];
  }
  $_REQUEST = $originalRequest;
  return civicrm_api3_create_success($errors, $params, 'ContributionPage', 'validate');
}

/**
 * Metadata for validate action.
 *
 * @param array $params
 */
function _civicrm_api3_contribution_page_validate_spec(&$params) {
  $params['id'] = [
    'title' => ts('Contribution Page ID'),
    'api.required' => TRUE,
    'type' => CRM_Utils_Type::T_INT,
  ];
}

/**
 * Set default getlist parameters.
 *
 * @see _civicrm_api3_generic_getlist_defaults
 *
 * @param array $request
 *
 * @return array
 */
function _civicrm_api3_contribution_page_getlist_defaults(&$request) {
  return [
    'description_field' => [
      'intro_text',
    ],
    'params' => [
      'is_active' => 1,
    ],
  ];
}
