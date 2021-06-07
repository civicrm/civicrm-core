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
   * @param array $attachement
   * @param bool $isTrusted
   *   Whether the current request is trusted to perform file-specific operations.
   * @param array $returnProperties
   *
   * @return array
   */
  public static function formatResult($attachment, $isTrusted, $returnProperties) {
    $config = CRM_Core_Config::singleton();
    $path = $config->customFileUploadDir . DIRECTORY_SEPARATOR . $attachment['uri'];

    $result = [
      'id' => $attachment['id'],
      'name' => CRM_Utils_File::cleanFileName($attachment['uri']),
      'mime_type' => $attachment['mime_type'],
      'description' => $attachment['description'],
      'upload_date' => is_numeric($attachment['upload_date']) ? CRM_Utils_Date::mysqlToIso($attachment['upload_date']) : $attachment['upload_date'],
      'entity_table' => $attachment['entity_table'],
      'entity_id' => $attachment['entity_id'],
      'icon' => CRM_Utils_File::getIconFromMimeType($attachment['mime_type']),
      'created_id' => $attachment['created_id'],
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
    if (!empty($returnProperties)) {
      foreach ($result as $fieldName => $dontCare) {
        if (!in_array($fieldName, $returnProperties)) {
          unset($result[$fieldName]);
        }
      }
    }
    if (in_array('content', $returnProperties)) {
      $result['content'] = file_get_contents($path);
    }
    return $result;
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
  public static function getAttachment($params, $id, $file, $entityFile, $isTrusted) {
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
      $fields['mime_type'] = $fileFields['mime_type'];
      $fields['description'] = $fileFields['description'];
      $fields['upload_date'] = $fileFields['upload_date'];
      $fields['entity_table'] = $entityFileFields['entity_table'];
      // Would be hard to securely handle changes.
      $fields['entity_table']['title'] = CRM_Utils_Array::value('title', $fields['entity_table'], 'Entity Table') . ' (write-once)';
      $fields['entity_id'] = $entityFileFields['entity_id'];
      // would be hard to securely handle changes
      $fields['entity_id']['title'] = CRM_Utils_Array::value('title', $fields['entity_id'], 'Entity ID') . ' (write-once)';
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

  /**
   * Returns all the pseudo fields of Attachement Entity
   *
   * @return array
   */
  public static function pseudoFields() {
    return [
      'name' => [
        'pseudo' => TRUE,
        'name' => 'name',
        'title' => 'Name (write-once)',
        'description' => 'The logical file name (not searchable)',
        'type' => CRM_Utils_Type::T_STRING,
      ],
      'field_name' => [
        'pseudo' => TRUE,
        'name' => 'field_name',
        'title' => 'Field Name (write-once)',
        'description' => 'Alternative to "entity_table" param - sets custom field value.',
        'type' => CRM_Utils_Type::T_STRING,
      ],
      'url' => [
        'pseudo' => TRUE,
        'name' => 'url',
        'title' => 'URL (read-only)',
        'description' => 'URL for downloading the file (not searchable, expire-able)',
        'type' => CRM_Utils_Type::T_STRING,
      ],
      'path' => [
        'pseudo' => TRUE,
        'name' => 'path',
        'title' => 'Path (read-only)',
        'description' => 'Local file path (not searchable, local-only)',
        'type' => CRM_Utils_Type::T_STRING,
      ],
      'content' => [
        'pseudo' => TRUE,
        'name' => 'content',
        'title' => 'Content',
        'description' => 'File content (not searchable, not returned by default)',
        'type' => CRM_Utils_Type::T_STRING,
      ],
    ];
  }

 /**
  * Bulk delete multiple records.
  *
  * @param array[] $record
  * @return static[]
  * @throws CRM_Core_Exception
  */
 public static function deleteRecord(array $record) {
   $config = CRM_Core_Config::singleton();
   list($id, $file, $entityFile, $name, $content, $moveFile, $isTrusted, $returnContent) = self::parseParams($record);

   $dao = self::getAttachment($record, $id, $file, $entityFile, $isTrusted);

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

   return [];
 }

}
