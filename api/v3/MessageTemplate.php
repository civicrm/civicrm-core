<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @throws \API_Exception
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
 * @throws API_Exception
 */
function civicrm_api3_message_template_send($params) {
  // Change external param names to internal ones
  $fieldSpec = array();
  _civicrm_api3_message_template_send_spec($fieldSpec);

  foreach ($fieldSpec as $field => $spec) {
    if (isset($spec['api.aliases']) && array_key_exists($field, $params)) {
      $params[CRM_Utils_Array::first($spec['api.aliases'])] = $params[$field];
      unset($params[$field]);
    }
  }
  if (empty($params['messageTemplateID'])) {
    if (empty($params['groupName']) || empty($params['valueName'])) {
      // Can't use civicrm_api3_verify_mandatory for this because it would give the wrong field names
      throw new API_Exception(
        "Mandatory key(s) missing from params array: requires id or option_group_name + option_value_name",
        "mandatory_missing",
        array("fields" => array('id', 'option_group_name', 'option_value_name'))
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
  $params['id']['api.aliases'] = array('messageTemplateID', 'message_template_id');
  $params['id']['type'] = CRM_Utils_Type::T_INT;

  $params['option_group_name']['description'] = 'option group name of the template (required if no id supplied)';
  $params['option_group_name']['title'] = 'Option Group Name';
  $params['option_group_name']['api.aliases'] = array('groupName');
  $params['option_group_name']['type'] = CRM_Utils_Type::T_STRING;

  $params['option_value_name']['description'] = 'option value name of the template (required if no id supplied)';
  $params['option_value_name']['title'] = 'Option Value Name';
  $params['option_value_name']['api.aliases'] = array('valueName');
  $params['option_value_name']['type'] = CRM_Utils_Type::T_STRING;

  $params['contact_id']['description'] = 'contact id if contact tokens are to be replaced';
  $params['contact_id']['title'] = 'Contact ID';
  $params['contact_id']['api.aliases'] = array('contactId');
  $params['contact_id']['type'] = CRM_Utils_Type::T_INT;

  $params['template_params']['description'] = 'additional template params (other than the ones already set in the template singleton)';
  $params['template_params']['title'] = 'Template Params';
  $params['template_params']['api.aliases'] = array('tplParams');
  // FIXME: Type??

  $params['from']['description'] = 'the From: header';
  $params['from']['title'] = 'From';
  $params['from']['type'] = CRM_Utils_Type::T_STRING;

  $params['to_name']['description'] = 'the recipient’s name';
  $params['to_name']['title'] = 'Recipient Name';
  $params['to_name']['api.aliases'] = array('toName');
  $params['to_name']['type'] = CRM_Utils_Type::T_STRING;

  $params['to_email']['description'] = 'the recipient’s email - mail is sent only if set';
  $params['to_email']['title'] = 'Recipient Email';
  $params['to_email']['api.aliases'] = array('toEmail');
  $params['to_email']['type'] = CRM_Utils_Type::T_STRING;

  $params['cc']['description'] = 'the Cc: header';
  $params['cc']['title'] = 'CC';
  $params['cc']['type'] = CRM_Utils_Type::T_STRING;

  $params['bcc']['description'] = 'the Bcc: header';
  $params['bcc']['title'] = 'BCC';
  $params['bcc']['type'] = CRM_Utils_Type::T_STRING;

  $params['reply_to']['description'] = 'the Reply-To: header';
  $params['reply_to']['title'] = 'Reply To';
  $params['reply_to']['api.aliases'] = array('replyTo');
  $params['reply_to']['type'] = CRM_Utils_Type::T_STRING;

  $params['attachments']['description'] = 'email attachments';
  $params['attachments']['title'] = 'Attachments';
  // FIXME: Type??

  $params['is_test']['description'] = 'whether this is a test email (and hence should include the test banner)';
  $params['is_test']['title'] = 'Is Test';
  $params['is_test']['api.aliases'] = array('isTest');
  $params['is_test']['type'] = CRM_Utils_Type::T_BOOLEAN;

  $params['pdf_filename']['description'] = 'filename of optional PDF version to add as attachment (do not include path)';
  $params['pdf_filename']['title'] = 'PDF Filename';
  $params['pdf_filename']['api.aliases'] = array('PDFFilename');
  $params['pdf_filename']['type'] = CRM_Utils_Type::T_STRING;
}
