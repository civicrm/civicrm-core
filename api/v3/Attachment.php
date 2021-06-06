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

  $attachments = __civicrm_api3_attachment_find($params, $id, $file, $entityFile, $isTrusted)->fetchAll() ?? [];
  $result = [];
  $returnProperties = $params['return'] ?? [];
  foreach ($attachments as $attachment) {
    $result[$attachement['id']] = CRM_Core_BAO_Attachment::formatResult($attachment, $isTrusted, $returnProperties);
  }
  return civicrm_api3_create_success($result, $params, 'Attachment', 'create');
}

/**
 * Adjust metadata for Attachment delete action.
 *
 * @param $spec
 */
function _civicrm_api3_attachment_delete_spec(&$spec) {
  unset($spec['id']['api.required']);
  $entityFileFields = CRM_Core_DAO_EntityFile::fields();
  $spec['entity_table'] = $entityFileFields['entity_table'];
  $spec['entity_table']['title'] = CRM_Utils_Array::value('title', $spec['entity_table'], 'Entity Table') . ' (write-once)';
  $spec['entity_id'] = $entityFileFields['entity_id'];
  $spec['entity_id']['title'] = CRM_Utils_Array::value('title', $spec['entity_id'], 'Entity ID') . ' (write-once)';
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

  $config = CRM_Core_Config::singleton();
  list($id, $file, $entityFile, $name, $content, $moveFile, $isTrusted, $returnContent) = CRM_Core_BAO_Attachment::parseParams($params);
  $dao = __civicrm_api3_attachment_find($params, $id, $file, $entityFile, $isTrusted);

  $filePaths = [];
  $fileIds = [];
  while ($dao->fetch()) {
    $filePaths[] = $config->customFileUploadDir . DIRECTORY_SEPARATOR . $dao->uri;
    $fileIds[] = $dao->id;
  }

  if (!empty($fileIds)) {
    $idString = implode(',', array_filter($fileIds, 'is_numeric'));
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_entity_file WHERE file_id in ($idString)");
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_file WHERE id in ($idString)");
  }

  // unlink is non-transactional, so we do this as the last step -- just in case the other steps produce errors
  if (!empty($filePaths)) {
    foreach ($filePaths as $filePath) {
      unlink($filePath);
    }
  }

  $result = [];
  return civicrm_api3_create_success($result, $params, 'Attachment', 'create');
}

/**
 * Attachment find helper.
 *
 * @param array $params
 * @param int|null $id the user-supplied ID of the attachment record
 * @param array $file
 *   The user-supplied vales for the file (mime_type, description, upload_date).
 * @param array $entityFile
 *   The user-supplied values of the entity-file (entity_table, entity_id).
 * @param bool $isTrusted
 *
 * @return CRM_Core_DAO
 * @throws API_Exception
 */
function __civicrm_api3_attachment_find($params, $id, $file, $entityFile, $isTrusted) {
  foreach (['name', 'content', 'path', 'url'] as $unsupportedFilter) {
    if (!empty($params[$unsupportedFilter])) {
      throw new API_Exception("Get by $unsupportedFilter is not currently supported");
    }
  }

  $select = CRM_Utils_SQL_Select::from('civicrm_file cf')
    ->join('cef', 'INNER JOIN civicrm_entity_file cef ON cf.id = cef.file_id')
    ->select([
      'cf.id',
      'cf.uri',
      'cf.mime_type',
      'cf.description',
      'cf.upload_date',
      'cf.created_id',
      'cef.entity_table',
      'cef.entity_id',
    ]);

  if ($id) {
    $select->where('cf.id = #id', ['#id' => $id]);
  }
  // Recall: $file is filtered by parse_params.
  foreach ($file as $key => $value) {
    $select->where('cf.!field = @value', [
      '!field' => $key,
      '@value' => $value,
    ]);
  }
  // Recall: $entityFile is filtered by parse_params.
  foreach ($entityFile as $key => $value) {
    $select->where('cef.!field = @value', [
      '!field' => $key,
      '@value' => $value,
    ]);
  }
  if (!$isTrusted) {
    // FIXME ACLs: Add any JOIN or WHERE clauses needed to enforce access-controls for the target entity.
    //
    // The target entity is identified by "cef.entity_table" (aka $entityFile['entity_table']) and "cef.entity_id".
    //
    // As a simplification, we *require* the "get" actions to filter on a single "entity_table" which should
    // avoid the complexity of matching ACL's against multiple entity types.
  }

  $dao = CRM_Core_DAO::executeQuery($select->toSQL());
  return $dao;
}
