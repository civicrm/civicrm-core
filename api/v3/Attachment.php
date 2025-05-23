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
 * @throws CRM_Core_Exception validation errors
 * @see Civi\API\Subscriber\DynamicFKAuthorization
 */
function civicrm_api3_attachment_create($params) {
  if (empty($params['id'])) {
    // When creating we need either entity_table or field_name.
    civicrm_api3_verify_one_mandatory($params, NULL, ['entity_table', 'field_name']);
  }

  $config = CRM_Core_Config::singleton();
  list($id, $file, $entityFile, $name, $content, $moveFile, $isTrusted, $returnContent) = _civicrm_api3_attachment_parse_params($params);

  $fileDao = new CRM_Core_BAO_File();
  $entityFileDao = new CRM_Core_DAO_EntityFile();

  if ($id) {
    $file['id'] = $fileDao->id = $id;

    if (!$fileDao->find(TRUE)) {
      throw new CRM_Core_Exception("Invalid ID");
    }

    $entityFileDao->file_id = $id;
    if (!$entityFileDao->find(TRUE)) {
      throw new CRM_Core_Exception("Cannot modify orphaned file");
    }
  }

  if (!$id && !is_string($content) && !is_string($moveFile)) {
    throw new CRM_Core_Exception("Mandatory key(s) missing from params array: 'id' or 'content' or 'options.move-file'");
  }
  if (!$isTrusted && $moveFile) {
    throw new CRM_Core_Exception("options.move-file is only supported on secure calls");
  }
  if (is_string($content) && is_string($moveFile)) {
    throw new CRM_Core_Exception("'content' and 'options.move-file' are mutually exclusive");
  }
  if ($id && !$isTrusted && isset($file['upload_date']) && $file['upload_date'] != CRM_Utils_Date::isoToMysql($fileDao->upload_date)) {
    throw new CRM_Core_Exception("Cannot modify upload_date" . var_export([$file['upload_date'], $fileDao->upload_date, CRM_Utils_Date::isoToMysql($fileDao->upload_date)], TRUE));
  }
  if ($id && $name && $name != CRM_Utils_File::cleanFileName($fileDao->uri)) {
    throw new CRM_Core_Exception("Cannot modify name");
  }

  if (!$id) {
    $file['uri'] = CRM_Utils_File::makeFileName($name);
  }
  $fileDao = CRM_Core_BAO_File::create($file);
  $fileDao->find(TRUE);

  $entityFileDao->copyValues($entityFile);
  $entityFileDao->file_id = $fileDao->id;
  $entityFileDao->save();

  $path = $config->customFileUploadDir . $fileDao->uri;
  if (is_string($content)) {
    file_put_contents($path, $content);
  }
  elseif (is_string($moveFile)) {
    // CRM-17432 Do not use rename() since it will break file permissions.
    // Also avoid move_uploaded_file() because the API can use options.move-file.
    if (!copy($moveFile, $path)) {
      throw new CRM_Core_Exception("Cannot copy uploaded file $moveFile to $path");
    }
    unlink($moveFile);
  }

  // Save custom field to entity
  if (!$id && empty($params['entity_table']) && isset($params['field_name'])) {
    civicrm_api3('custom_value', 'create', [
      'entity_id' => $params['entity_id'],
      $params['field_name'] => $fileDao->id,
    ]);
  }

  $result = [
    $fileDao->id => _civicrm_api3_attachment_format_result($fileDao, $entityFileDao, $returnContent, $isTrusted),
  ];
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
 * @throws CRM_Core_Exception validation errors
 */
function civicrm_api3_attachment_get($params) {
  list($id, $file, $entityFile, $name, $content, $moveFile, $isTrusted, $returnContent) = _civicrm_api3_attachment_parse_params($params);

  $dao = __civicrm_api3_attachment_find($params, $id, $file, $entityFile, $isTrusted);
  $result = [];
  while ($dao->fetch()) {
    $result[$dao->id] = _civicrm_api3_attachment_format_result($dao, $dao, $returnContent, $isTrusted);
  }
  return civicrm_api3_create_success($result, $params, 'Attachment', 'create');
}

/**
 * Adjust metadata for Attachment delete action.
 *
 * @param array $spec
 */
function _civicrm_api3_attachment_delete_spec(&$spec) {
  unset($spec['id']['api.required']);
  $entityFileFields = CRM_Core_DAO_EntityFile::fields();
  $spec['entity_table'] = $entityFileFields['entity_table'];
  // Historically this field had no pseudoconstant and APIv3 can't handle it
  $spec['entity_table']['pseudoconstant'] = NULL;
  $spec['entity_table']['title'] = ($spec['entity_table']['title'] ?? 'Entity Table') . ' (write-once)';
  $spec['entity_id'] = $entityFileFields['entity_id'];
  $spec['entity_id']['title'] = ($spec['entity_id']['title'] ?? 'Entity ID') . ' (write-once)';
}

/**
 * Delete Attachment.
 *
 * @param array $params
 *
 * @return array
 * @throws CRM_Core_Exception
 */
function civicrm_api3_attachment_delete($params) {
  if (!empty($params['id'])) {
    // ok
  }
  elseif (!empty($params['entity_table']) && !empty($params['entity_id'])) {
    // ok
  }
  else {
    throw new CRM_Core_Exception("Mandatory key(s) missing from params array: id or entity_table+entity_table");
  }

  $config = CRM_Core_Config::singleton();
  list($id, $file, $entityFile, $name, $content, $moveFile, $isTrusted, $returnContent) = _civicrm_api3_attachment_parse_params($params);
  $dao = __civicrm_api3_attachment_find($params, $id, $file, $entityFile, $isTrusted);

  $filePaths = [];
  $fileIds = [];
  while ($dao->fetch()) {
    $filePaths[] = $config->customFileUploadDir . $dao->uri;
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
 * @throws CRM_Core_Exception
 */
function __civicrm_api3_attachment_find($params, $id, $file, $entityFile, $isTrusted) {
  foreach (['name', 'content', 'path', 'url'] as $unsupportedFilter) {
    if (!empty($params[$unsupportedFilter])) {
      throw new CRM_Core_Exception("Get by $unsupportedFilter is not currently supported");
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
 * @throws CRM_Core_Exception validation errors
 */
function _civicrm_api3_attachment_parse_params($params) {
  $id = $params['id'] ?? NULL;
  if ($id && !is_numeric($id)) {
    throw new CRM_Core_Exception("Malformed id");
  }

  $file = [];
  foreach (['mime_type', 'description', 'upload_date'] as $field) {
    if (array_key_exists($field, $params)) {
      $file[$field] = $params[$field];
    }
  }

  $entityFile = [];
  foreach (['entity_table', 'entity_id'] as $field) {
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
      throw new CRM_Core_Exception('Malformed name');
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

  $returns = $params['return'] ?? [];
  $returns = is_array($returns) ? $returns : [$returns];
  $returnContent = in_array('content', $returns);

  return [$id, $file, $entityFile, $name, $content, $moveFile, $isTrusted, $returnContent];
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
  $path = $config->customFileUploadDir . $fileDao->uri;

  $result = [
    'id' => $fileDao->id,
    'name' => CRM_Utils_File::cleanFileName($fileDao->uri),
    'mime_type' => $fileDao->mime_type,
    'description' => $fileDao->description,
    'upload_date' => is_numeric($fileDao->upload_date) ? CRM_Utils_Date::mysqlToIso($fileDao->upload_date) : $fileDao->upload_date,
    'entity_table' => $entityFileDao->entity_table,
    'entity_id' => $entityFileDao->entity_id,
    'icon' => CRM_Utils_File::getIconFromMimeType($fileDao->mime_type),
    'created_id' => $fileDao->created_id,
  ];
  $fileHash = CRM_Core_BAO_File::generateFileHash(NULL, $result['id']);
  $result['url'] = CRM_Utils_System::url(
    'civicrm/file', 'reset=1&id=' . $result['id'] . '&fcs=' . $fileHash,
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

  $spec = [];
  $spec['id'] = $fileFields['id'];
  $spec['name'] = [
    'title' => 'Name (write-once)',
    'description' => 'The logical file name (not searchable)',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['field_name'] = [
    'title' => 'Field Name (write-once)',
    'description' => 'Alternative to "entity_table" param - sets custom field value.',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['mime_type'] = $fileFields['mime_type'];
  $spec['description'] = $fileFields['description'];
  $spec['upload_date'] = $fileFields['upload_date'];
  $spec['entity_table'] = $entityFileFields['entity_table'];
  // Historically this field had no pseudoconstant and APIv3 can't handle it
  $spec['entity_table']['pseudoconstant'] = NULL;
  // Would be hard to securely handle changes.
  $spec['entity_table']['title'] = ($spec['entity_table']['title'] ?? 'Entity Table') . ' (write-once)';
  $spec['entity_id'] = $entityFileFields['entity_id'];
  // would be hard to securely handle changes
  $spec['entity_id']['title'] = ($spec['entity_id']['title'] ?? 'Entity ID') . ' (write-once)';
  $spec['url'] = [
    'title' => 'URL (read-only)',
    'description' => 'URL for downloading the file (not searchable, expire-able)',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['path'] = [
    'title' => 'Path (read-only)',
    'description' => 'Local file path (not searchable, local-only)',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['content'] = [
    'title' => 'Content',
    'description' => 'File content (not searchable, not returned by default)',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['created_id'] = [
    'title' => 'Created By Contact ID',
    'type' => CRM_Utils_Type::T_INT,
    'description' => 'FK to civicrm_contact, who uploaded this file',
  ];

  return $spec;
}
