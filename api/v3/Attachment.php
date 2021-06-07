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
 * "Attachment" is a pseudo-entity which represents a record in civicrm_file
 * combined with a record in civicrm_entity_file as well as the underlying
 * file content.
 * For core fields use "entity_table", for custom fields use "field_name"
 *
 * ```
 * // Create an attachment for a core field
 * $result = civicrm_api3('Attachment', 'create', array(
 *   'entity_table' => 'civicrm_activity',
 *   'entity_id' => 123,
 *   'name' => 'README.txt',
 *   'mime_type' => 'text/plain',
 *   'content' => 'Please to read the README',
 * ));
 * $attachment = $result['values'][$result['id']];
 * echo sprintf("<a href='%s'>View %s</a>", $attachment['url'], $attachment['name']);
 * ```
 *
 * ```
 * // Create an attachment for a custom file field
 * $result = civicrm_api3('Attachment', 'create', array(
 *   'field_name' => 'custom_6',
 *   'entity_id' => 123,
 *   'name' => 'README.txt',
 *   'mime_type' => 'text/plain',
 *   'content' => 'Please to read the README',
 * ));
 * $attachment = $result['values'][$result['id']];
 * echo sprintf("<a href='%s'>View %s</a>", $attachment['url'], $attachment['name']);
 * ```
 *
 * ```
 * // Move an existing file and save as an attachment
 * $result = civicrm_api3('Attachment', 'create', array(
 *   'entity_table' => 'civicrm_activity',
 *   'entity_id' => 123,
 *   'name' => 'README.txt',
 *   'mime_type' => 'text/plain',
 *   'options' => array(
 *      'move-file' => '/tmp/upload1a2b3c4d',
 *    ),
 * ));
 * $attachment = $result['values'][$result['id']];
 * echo sprintf("<a href='%s'>View %s</a>", $attachment['url'], $attachment['name']);
 * ```
 *
 * Notes:
 *  - File content is not returned by default. One must specify 'return => content'.
 *  - Features which deal with local file system (e.g. passing "options.move-file"
 *    or returning a "path") are only valid when executed as a local API (ie
 *    "check_permissions"==false)
 *
 * @package CiviCRM_APIv3
 */

/**
 * Adjust metadata for "create" action.
 *
 * @param array $spec
 *   List of fields.
 */
function _civicrm_api3_attachment_create_spec(&$spec) {
  $spec['name']['api.required'] = 1;
  $spec['mime_type']['api.required'] = 1;
  $spec['entity_id']['api.required'] = 1;
  $spec['upload_date']['api.default'] = 'now';
}

/**
 * Create an Attachment.
 *
 * @param array $params
 *
 * @return array
 * @throws API_Exception validation errors
 * @see Civi\API\Subscriber\DynamicFKAuthorization
 */
function civicrm_api3_attachment_create($params) {
  if (empty($params['id'])) {
    // When creating we need either entity_table or field_name.
    civicrm_api3_verify_one_mandatory($params, NULL, ['entity_table', 'field_name']);
  }
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);;
}

/**
 * Get Attachment.
 *
 * @param array $params
 *
 * @return array
 *   per APIv3
 * @throws API_Exception validation errors
 */
function civicrm_api3_attachment_get($params) {
  list($id, $file, $entityFile, $name, $content, $moveFile, $isTrusted, $returnContent) = CRM_Core_BAO_Attachment::parseParams($params);

  $attachments = CRM_Core_BAO_Attachment::getAttachment($params, $id, $file, $entityFile, $isTrusted)->fetchAll() ?? [];
  $result = [];
  $returnProperties = $params['return'] ?? [];
  foreach ($attachments as $attachment) {
    $result[$attachement['id']] = CRM_Core_BAO_Attachment::formatResult($attachment, $isTrusted, $returnProperties);
  }
  return civicrm_api3_create_success($result, $params, 'Attachment', 'create');
}

/**
 * Adjust metadata for Attachment get action.
 *
 * @param $spec
 */
function _civicrm_api3_attachment_get_spec(&$spec) {
  $spec = array_merge($spec, CRM_Core_BAO_Attachment::pseudoFields());
}

/**
 * Adjust metadata for Attachment delete action.
 *
 * @param $spec
 */
function _civicrm_api3_attachment_delete_spec(&$spec) {
  unset($spec['id']['api.required']);
}

/**
 * Delete Attachment.
 *
 * @param array $params
 *
 * @return array
 * @throws API_Exception
 */
function civicrm_api3_attachment_delete($params) {
  if (!empty($params['id'])) {
    // ok
  }
  elseif (!empty($params['entity_table']) && !empty($params['entity_id'])) {
    // ok
  }
  else {
    throw new API_Exception("Mandatory key(s) missing from params array: id or entity_table+entity_table");
  }

  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Attachment');
}
