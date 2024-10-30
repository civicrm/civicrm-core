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
 * This api exposes CiviCRM message_template.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create message template.
 *
 * @param array $params
 *
 * @return array
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_message_template_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'MessageTemplate');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_message_template_create_spec(&$params) {
  $params['msg_title']['api.required'] = 1;
  $params['is_active']['api.default'] = TRUE;
  /*  $params['entity_id']['api.required'] = 1;
  $params['entity_table']['api.default'] = "civicrm_contribution_recur";
  $params['type']['api.default'] = "R";
   */
}

/**
 * Delete message template.
 *
 * @param array $params
 *
 * @return bool
 *   API result array
 */
function civicrm_api3_message_template_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust metadata for message_template get action.
 *
 * @param array $params
 */
function _civicrm_api3_message_template_get_spec(&$params) {
  // fetch active records by default
  $params['is_active']['api.default'] = 1;
}

/**
 * Retrieve one or more message_template.
 *
 * @param array $params
 *   Array of name/value pairs.
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_message_template_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Sends a template.
 *
 * @param array $params
 *
 * @throws CRM_Core_Exception
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_message_template_send($params) {
  // Change external param names to internal ones
  $fieldSpec = [];
  _civicrm_api3_message_template_send_spec($fieldSpec);

  foreach ($fieldSpec as $field => $spec) {
    // There is some dark magic going on here.
    // The point of the 'api.aliases' metadata is generally
    // to ensure that old params can be passed in and they still work.
    // However, in this case the api params don't match the BAO
    // params so the names that have been determined as
    // 'right' for the api are being transformed into
    // the 'wrong' BAO ones. It works, it's tested &
    // we can do better in apiv4 once we get a suitable
    // api there.
    if (($spec['name'] ?? '') !== 'workflow' && isset($spec['api.aliases']) && array_key_exists($field, $params)) {
      $params[CRM_Utils_Array::first($spec['api.aliases'])] = $params[$field];
      unset($params[$field]);
    }
  }
  $params['modelProps'] = [
    'userEnteredText' => $params['tplParams']['receipt_text'] ?? NULL,
  ];
  if (empty($params['messageTemplateID'])) {
    if (empty($params['workflow'])) {
      // Can't use civicrm_api3_verify_mandatory for this because it would give the wrong field names
      throw new CRM_Core_Exception(
        'Mandatory key(s) missing from params array: requires id or workflow',
        'mandatory_missing',
        ['fields' => ['id', 'workflow']]
      );
    }
  }
  CRM_Core_BAO_MessageTemplate::sendTemplate($params);
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation &
 * validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_message_template_send_spec(&$params) {
  $params['id']['description'] = 'ID of the template';
  $params['id']['title'] = 'Message Template ID';
  $params['id']['api.aliases'] = ['messageTemplateID', 'message_template_id'];
  $params['id']['type'] = CRM_Utils_Type::T_INT;

  $params['workflow']['description'] = 'option value name of the template (required if no id supplied)';
  $params['workflow']['title'] = ts('Workflow');
  $params['workflow']['api.aliases'] = ['option_value_name', 'valueName'];
  $params['workflow']['type'] = CRM_Utils_Type::T_STRING;
  $params['workflow']['name'] = 'workflow';

  $params['contact_id']['description'] = 'contact id if contact tokens are to be replaced';
  $params['contact_id']['title'] = 'Contact ID';
  $params['contact_id']['api.aliases'] = ['contactId'];
  $params['contact_id']['type'] = CRM_Utils_Type::T_INT;

  $params['template_params']['description'] = 'additional template params (other than the ones already set in the template singleton)';
  $params['template_params']['title'] = 'Template Params';
  $params['template_params']['api.aliases'] = ['tplParams'];
  // FIXME: Type??

  $params['from']['description'] = 'the From: header';
  $params['from']['title'] = 'From';
  $params['from']['type'] = CRM_Utils_Type::T_STRING;

  $params['to_name']['description'] = 'the recipient’s name';
  $params['to_name']['title'] = 'Recipient Name';
  $params['to_name']['api.aliases'] = ['toName'];
  $params['to_name']['type'] = CRM_Utils_Type::T_STRING;

  $params['to_email']['description'] = 'the recipient’s email - mail is sent only if set';
  $params['to_email']['title'] = 'Recipient Email';
  $params['to_email']['api.aliases'] = ['toEmail'];
  $params['to_email']['type'] = CRM_Utils_Type::T_STRING;

  $params['cc']['description'] = 'the Cc: header';
  $params['cc']['title'] = 'CC';
  $params['cc']['type'] = CRM_Utils_Type::T_STRING;

  $params['bcc']['description'] = 'the Bcc: header';
  $params['bcc']['title'] = 'BCC';
  $params['bcc']['type'] = CRM_Utils_Type::T_STRING;

  $params['reply_to']['description'] = 'the Reply-To: header';
  $params['reply_to']['title'] = 'Reply To';
  $params['reply_to']['api.aliases'] = ['replyTo'];
  $params['reply_to']['type'] = CRM_Utils_Type::T_STRING;

  $params['attachments']['description'] = 'email attachments';
  $params['attachments']['title'] = 'Attachments';
  // FIXME: Type??

  $params['is_test']['description'] = 'whether this is a test email (and hence should include the test banner)';
  $params['is_test']['title'] = 'Is Test';
  $params['is_test']['api.aliases'] = ['isTest'];
  $params['is_test']['type'] = CRM_Utils_Type::T_BOOLEAN;

  $params['pdf_filename']['description'] = 'filename of optional PDF version to add as attachment (do not include path)';
  $params['pdf_filename']['title'] = 'PDF Filename';
  $params['pdf_filename']['api.aliases'] = ['PDFFilename'];
  $params['pdf_filename']['type'] = CRM_Utils_Type::T_STRING;

  $params['disable_smarty']['description'] = 'Disable Smarty. Normal CiviMail tokens are still supported. By default Smarty is enabled.';
  $params['disable_smarty']['title'] = 'Disable Smarty';
  $params['disable_smarty']['api.aliases'] = ['disableSmarty'];
  $params['disable_smarty']['type'] = CRM_Utils_Type::T_BOOLEAN;
}
