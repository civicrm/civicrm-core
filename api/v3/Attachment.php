<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * "Attachment" is a pseudo-entity which represents a record in civicrm_file
 * combined with a record in civicrm_entity_file as well as the underlying
 * file content.
 * For core fields use "entity_table", for custom fields use "field_name"
 *
 * @code
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
 * @endcode
 *
 * @code
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
 * @endcode
 *
 * @code
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
 * @endcode
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
  $spec = array_merge($spec, _civicrm_api3_attachment_getfields());
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
    // When creating we need either entity_table or field_name
    civicrm_api3_verify_one_mandatory($params, NULL, array('entity_table', 'field_name'));
  }

  $config = CRM_Core_Config::singleton();
  list($id, $file, $entityFile, $name, $content, $moveFile, $isTrusted, $returnContent) = _civicrm_api3_attachment_parse_params($params);

  $fileDao = new CRM_Core_BAO_File();
  $entityFileDao = new CRM_Core_DAO_EntityFile();

  if ($id) {
    $fileDao->id = $id;
    if (!$fileDao->find(TRUE)) {
      throw new API_Exception("Invalid ID");
    }

    $entityFileDao->file_id = $id;
    if (!$entityFileDao->find(TRUE)) {
      throw new API_Exception("Cannot modify orphaned file");
    }
  }

  if (!$id && !is_string($content) && !is_string($moveFile)) {
    throw new API_Exception("Mandatory key(s) missing from params array: 'id' or 'content' or 'options.move-file'");
  }
  if (!$isTrusted && $moveFile) {
    throw new API_Exception("options.move-file is only supported on secure calls");
  }
  if (is_string($content) && is_string($moveFile)) {
    throw new API_Exception("'content' and 'options.move-file' are mutually exclusive");
  }
  if ($id && !$isTrusted && isset($file['upload_date']) && $file['upload_date'] != CRM_Utils_Date::isoToMysql($fileDao->upload_date)) {
    throw new API_Exception("Cannot modify upload_date" . var_export(array($file['upload_date'], $fileDao->upload_date, CRM_Utils_Date::isoToMysql($fileDao->upload_date)), TRUE));
  }
  if ($id && $name && $name != CRM_Utils_File::cleanFileName($fileDao->uri)) {
    throw new API_Exception("Cannot modify name");
  }

  $fileDao->copyValues($file);
  if (!$id) {
    $fileDao->uri = CRM_Utils_File::makeFileName($name);
  }
  $fileDao->save();

  $entityFileDao->copyValues($entityFile);
  $entityFileDao->file_id = $fileDao->id;
  $entityFileDao->save();

  $path = $config->customFileUploadDir . DIRECTORY_SEPARATOR . $fileDao->uri;
  if (is_string($content)) {
    file_put_contents($path, $content);
  }
  elseif (is_string($moveFile)) {
    // CRM-17432 Do not use rename() since it will break file permissions.
    // Also avoid move_uplaoded_file() because the API can use options.move-file.
    copy($moveFile, $path);
    unlink($moveFile);
  }

  // Save custom field to entity
  if (!$id && empty($params['entity_table']) && isset($params['field_name'])) {
    civicrm_api3('custom_value', 'create', array(
      'entity_id' => $params['entity_id'],
      $params['field_name'] => $fileDao->id,
    ));
  }

  $result = array(
    $fileDao->id => _civicrm_api3_attachment_format_result($fileDao, $entityFileDao, $returnContent, $isTrusted),
  );
  return civicrm_api3_create_success($result, $params, 'Attachment', 'create');
}

/**
 * Adjust metadata for get action.
 *
 * @param array $spec
 *   List of fields.
 */
function _civicrm_api3_attachment_get_spec(&$spec) {
  $spec = array_merge($spec, _civicrm_api3_attachment_getfields());
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
  list($id, $file, $entityFile, $name, $content, $moveFile, $isTrusted, $returnContent) = _civicrm_api3_attachment_parse_params($params);

  $dao = __civicrm_api3_attachment_find($params, $id, $file, $entityFile, $isTrusted);
  $result = array();
  while ($dao->fetch()) {
    $result[$dao->id] = _civicrm_api3_attachment_format_result($dao, $dao, $returnContent, $isTrusted);
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
  list($id, $file, $entityFile, $name, $content, $moveFile, $isTrusted, $returnContent) = _civicrm_api3_attachment_parse_params($params);
  $dao = __civicrm_api3_attachment_find($params, $id, $file, $entityFile, $isTrusted);

  $filePaths = array();
  $fileIds = array();
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

  $result = array();
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
  foreach (array('name', 'content', 'path', 'url') as $unsupportedFilter) {
    if (!empty($params[$unsupportedFilter])) {
      throw new API_Exception("Get by $unsupportedFilter is not currently supported");
    }
  }

  $select = CRM_Utils_SQL_Select::from('civicrm_file cf')
    ->join('cef', 'INNER JOIN civicrm_entity_file cef ON cf.id = cef.file_id')
    ->select(array(
      'cf.id',
      'cf.uri',
      'cf.mime_type',
      'cf.description',
      'cf.upload_date',
      'cef.entity_table',
      'cef.entity_id',
    ));

  if ($id) {
    $select->where('cf.id = #id', array('#id' => $id));
  }
  // Recall: $file is filtered by parse_params.
  foreach ($file as $key => $value) {
    $select->where('cf.!field = @value', array(
      '!field' => $key,
      '@value' => $value,
    ));
  }
  // Recall: $entityFile is filtered by parse_params.
  foreach ($entityFile as $key => $value) {
    $select->where('cef.!field = @value', array(
      '!field' => $key,
      '@value' => $value,
    ));
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

/**
 * Attachment parsing helper.
 *
 * @param array $params
 *
 * @return array
 *   (0 => int $id, 1 => array $file, 2 => array $entityFile, 3 => string $name, 4 => string $content,
 *    5 => string $moveFile, 6 => $isTrusted, 7 => bool $returnContent)
 *    - array $file: whitelisted fields that can pass through directly to civicrm_file
 *    - array $entityFile: whitelisted fields that can pass through directly to civicrm_entity_file
 *    - string $name: the printable name
 *    - string $moveFile: the full path to a local file whose content should be loaded
 *    - bool $isTrusted: whether we trust the requester to do sketchy things (like moving files or reassigning entities)
 *    - bool $returnContent: whether we are expected to return the full content of the file
 * @throws API_Exception validation errors
 */
function _civicrm_api3_attachment_parse_params($params) {
  $id = CRM_Utils_Array::value('id', $params, NULL);
  if ($id && !is_numeric($id)) {
    throw new API_Exception("Malformed id");
  }

  $file = array();
  foreach (array('mime_type', 'description', 'upload_date') as $field) {
    if (array_key_exists($field, $params)) {
      $file[$field] = $params[$field];
    }
  }

  $entityFile = array();
  foreach (array('entity_table', 'entity_id') as $field) {
    if (array_key_exists($field, $params)) {
      $entityFile[$field] = $params[$field];
    }
  }

  if (empty($params['entity_table']) && isset($params['field_name'])) {
    $tableInfo = CRM_Core_BAO_CustomField::getTableColumnGroup(intval(str_replace('custom_', '', $params['field_name'])));
    $entityFile['entity_table'] = $tableInfo[0];
  }

  $name = NULL;
  if (array_key_exists('name', $params)) {
    if ($params['name'] != basename($params['name']) || preg_match(':[/\\\\]:', $params['name'])) {
      throw new API_Exception('Malformed name');
    }
    $name = $params['name'];
  }

  $content = NULL;
  if (isset($params['content'])) {
    $content = $params['content'];
  }

  $moveFile = NULL;
  if (isset($params['options']['move-file'])) {
    $moveFile = $params['options']['move-file'];
  }
  elseif (isset($params['options.move-file'])) {
    $moveFile = $params['options.move-file'];
  }

  $isTrusted = empty($params['check_permissions']);

  $returns = isset($params['return']) ? $params['return'] : array();
  $returns = is_array($returns) ? $returns : array($returns);
  $returnContent = in_array('content', $returns);

  return array($id, $file, $entityFile, $name, $content, $moveFile, $isTrusted, $returnContent);
}

/**
 * Attachment result formatting helper.
 *
 * @param CRM_Core_DAO_File $fileDao
 *   Maybe "File" or "File JOIN EntityFile".
 * @param CRM_Core_DAO_EntityFile $entityFileDao
 *   Maybe "EntityFile" or "File JOIN EntityFile".
 * @param bool $returnContent
 *   Whether to return the full content of the file.
 * @param bool $isTrusted
 *   Whether the current request is trusted to perform file-specific operations.
 *
 * @return array
 */
function _civicrm_api3_attachment_format_result($fileDao, $entityFileDao, $returnContent, $isTrusted) {
  $config = CRM_Core_Config::singleton();
  $path = $config->customFileUploadDir . DIRECTORY_SEPARATOR . $fileDao->uri;

  $result = array(
    'id' => $fileDao->id,
    'name' => CRM_Utils_File::cleanFileName($fileDao->uri),
    'mime_type' => $fileDao->mime_type,
    'description' => $fileDao->description,
    'upload_date' => is_numeric($fileDao->upload_date) ? CRM_Utils_Date::mysqlToIso($fileDao->upload_date) : $fileDao->upload_date,
    'entity_table' => $entityFileDao->entity_table,
    'entity_id' => $entityFileDao->entity_id,
  );
  $result['url'] = CRM_Utils_System::url(
    'civicrm/file', 'reset=1&id=' . $result['id'] . '&eid=' . $result['entity_id'],
    TRUE,
    NULL,
    FALSE,
    TRUE
  );
  if ($isTrusted) {
    $result['path'] = $path;
  }
  if ($returnContent) {
    $result['content'] = file_get_contents($path);
  }
  return $result;
}

/**
 * Attachment getfields helper.
 *
 * @return array
 *   list of fields (indexed by name)
 */
function _civicrm_api3_attachment_getfields() {
  $fileFields = CRM_Core_DAO_File::fields();
  $entityFileFields = CRM_Core_DAO_EntityFile::fields();

  $spec = array();
  $spec['id'] = $fileFields['id'];
  $spec['name'] = array(
    'title' => 'Name (write-once)',
    'description' => 'The logical file name (not searchable)',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $spec['field_name'] = array(
    'title' => 'Field Name (write-once)',
    'description' => 'Alternative to "entity_table" param - sets custom field value.',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $spec['mime_type'] = $fileFields['mime_type'];
  $spec['description'] = $fileFields['description'];
  $spec['upload_date'] = $fileFields['upload_date'];
  $spec['entity_table'] = $entityFileFields['entity_table'];
  // Would be hard to securely handle changes.
  $spec['entity_table']['title'] = CRM_Utils_Array::value('title', $spec['entity_table'], 'Entity Table') . ' (write-once)';
  $spec['entity_id'] = $entityFileFields['entity_id'];
  $spec['entity_id']['title'] = CRM_Utils_Array::value('title', $spec['entity_id'], 'Entity ID') . ' (write-once)'; // would be hard to securely handle changes
  $spec['url'] = array(
    'title' => 'URL (read-only)',
    'description' => 'URL for downloading the file (not searchable, expire-able)',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $spec['path'] = array(
    'title' => 'Path (read-only)',
    'description' => 'Local file path (not searchable, local-only)',
    'type' => CRM_Utils_Type::T_STRING,
  );
  $spec['content'] = array(
    'title' => 'Content',
    'description' => 'File content (not searchable, not returned by default)',
    'type' => CRM_Utils_Type::T_STRING,
  );

  return $spec;
}
