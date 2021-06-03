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
 * Business objects for managing Attachment.
 */
class CRM_Core_BAO_Attachment extends CRM_Core_DAO {

  /**
   * Create a dedupe exception record.
   *
   * @param array $params
   *
   * @return \CRM_Dedupe_BAO_Exception
   */
  public static function create($params) {
    $config = CRM_Core_Config::singleton();
    list($id, $file, $entityFile, $name, $content, $moveFile, $isTrusted, $returnContent) = self::parseParams($params);

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

    $path = $config->customFileUploadDir . DIRECTORY_SEPARATOR . $fileDao->uri;
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
      $fileDao->id => self::formatResult($fileDao, $entityFileDao, $returnContent, $isTrusted),
    ];
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
  public static function parseParams($params) {
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
  public static function formatResult($fileDao, $entityFileDao, $returnContent, $isTrusted) {
    $config = CRM_Core_Config::singleton();
    $path = $config->customFileUploadDir . DIRECTORY_SEPARATOR . $fileDao->uri;

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
    $fileHash = CRM_Core_BAO_File::generateFileHash($result['entity_id'], $result['id']);
    $result['url'] = CRM_Utils_System::url(
      'civicrm/file', 'reset=1&id=' . $result['id'] . '&eid=' . $result['entity_id'] . '&fcs=' . $fileHash,
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
   * Returns all the column names of this table
   *
   * @return array
   */
  public static function &fields() {
    if (!isset(Civi::$statics[__CLASS__]['fields'])) {
      $fileFields = CRM_Core_DAO_File::fields();
      $entityFileFields = CRM_Core_DAO_EntityFile::fields();
      $fields = [];
      $fields['id'] = $fileFields['id'];
      $fields['name'] = [
        'name' => 'name',
        'title' => 'Name (write-once)',
        'description' => 'The logical file name (not searchable)',
        'type' => CRM_Utils_Type::T_STRING,
      ];
      $fields['field_name'] = [
        'name' => 'field_name',
        'title' => 'Field Name (write-once)',
        'description' => 'Alternative to "entity_table" param - sets custom field value.',
        'type' => CRM_Utils_Type::T_STRING,
      ];
      $fields['mime_type'] = $fileFields['mime_type'];
      $fields['description'] = $fileFields['description'];
      $fields['upload_date'] = $fileFields['upload_date'];
      $fields['entity_table'] = $entityFileFields['entity_table'];
      // Would be hard to securely handle changes.
      $fields['entity_table']['title'] = CRM_Utils_Array::value('title', $fields['entity_table'], 'Entity Table') . ' (write-once)';
      $fields['entity_id'] = $entityFileFields['entity_id'];
      // would be hard to securely handle changes
      $fields['entity_id']['title'] = CRM_Utils_Array::value('title', $fields['entity_id'], 'Entity ID') . ' (write-once)';
      $fields['url'] = [
        'name' => 'url',
        'title' => 'URL (read-only)',
        'description' => 'URL for downloading the file (not searchable, expire-able)',
        'type' => CRM_Utils_Type::T_STRING,
      ];
      $fields['path'] = [
        'name' => 'path',
        'title' => 'Path (read-only)',
        'description' => 'Local file path (not searchable, local-only)',
        'type' => CRM_Utils_Type::T_STRING,
      ];
      $fields['content'] = [
        'name' => 'content',
        'title' => 'Content',
        'description' => 'File content (not searchable, not returned by default)',
        'type' => CRM_Utils_Type::T_STRING,
      ];
      $fields['created_id'] = [
        'name' => 'created_id',
        'title' => 'Created By Contact ID',
        'type' => CRM_Utils_Type::T_INT,
        'description' => 'FK to civicrm_contact, who uploaded this file',
      ];
      Civi::$statics[__CLASS__]['fields'] = $fields;
    }

    return Civi::$statics[__CLASS__]['fields'];
  }

}
