<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */

/**
 * BAO object for crm_log table
 */
class CRM_Core_BAO_File extends CRM_Core_DAO_File {

  static $_signableFields = array('entityTable', 'entityID', 'fileID');

  /**
   * @param int $fileID
   * @param int $entityID
   * @param null $entityTable
   *
   * @return array
   */
  public static function path($fileID, $entityID, $entityTable = NULL) {
    $entityFileDAO = new CRM_Core_DAO_EntityFile();
    if ($entityTable) {
      $entityFileDAO->entity_table = $entityTable;
    }
    $entityFileDAO->entity_id = $entityID;
    $entityFileDAO->file_id = $fileID;

    if ($entityFileDAO->find(TRUE)) {
      $fileDAO = new CRM_Core_DAO_File();
      $fileDAO->id = $fileID;
      if ($fileDAO->find(TRUE)) {
        $config = CRM_Core_Config::singleton();
        $path = $config->customFileUploadDir . $fileDAO->uri;

        if (file_exists($path) && is_readable($path)) {
          return array($path, $fileDAO->mime_type);
        }
      }
    }

    return array(NULL, NULL);
  }


  /**
   * @param $data
   * @param int $fileTypeID
   * @param $entityTable
   * @param int $entityID
   * @param $entitySubtype
   * @param bool $overwrite
   * @param null|array $fileParams
   * @param string $uploadName
   * @param null $mimeType
   *
   * @throws Exception
   */
  public static function filePostProcess(
    $data,
    $fileTypeID,
    $entityTable,
    $entityID,
    $entitySubtype,
    $overwrite = TRUE,
    $fileParams = NULL,
    $uploadName = 'uploadFile',
    $mimeType = NULL
  ) {
    if (!$mimeType) {
      CRM_Core_Error::fatal(ts('Mime Type is now a required parameter'));
    }

    $config = CRM_Core_Config::singleton();

    $path = explode('/', $data);
    $filename = $path[count($path) - 1];

    // rename this file to go into the secure directory
    if ($entitySubtype) {
      $directoryName = $config->customFileUploadDir . $entitySubtype . DIRECTORY_SEPARATOR . $entityID;
    }
    else {
      $directoryName = $config->customFileUploadDir;
    }

    CRM_Utils_File::createDir($directoryName);

    if (!rename($data, $directoryName . DIRECTORY_SEPARATOR . $filename)) {
      CRM_Core_Error::fatal(ts('Could not move custom file to custom upload directory'));
    }

    // to get id's
    if ($overwrite && $fileTypeID) {
      list($sql, $params) = self::sql($entityTable, $entityID, $fileTypeID);
    }
    else {
      list($sql, $params) = self::sql($entityTable, $entityID, 0);
    }

    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $dao->fetch();

    $fileDAO = new CRM_Core_DAO_File();
    $op = 'create';
    if (isset($dao->cfID) && $dao->cfID) {
      $op = 'edit';
      $fileDAO->id = $dao->cfID;
      unlink($directoryName . DIRECTORY_SEPARATOR . $dao->uri);
    }

    if (!empty($fileParams)) {
      $fileDAO->copyValues($fileParams);
    }

    $fileDAO->uri = $filename;
    $fileDAO->mime_type = $mimeType;
    $fileDAO->file_type_id = $fileTypeID;
    $fileDAO->upload_date = date('YmdHis');
    $fileDAO->save();

    // need to add/update civicrm_entity_file
    $entityFileDAO = new CRM_Core_DAO_EntityFile();
    if (isset($dao->cefID) && $dao->cefID) {
      $entityFileDAO->id = $dao->cefID;
    }
    $entityFileDAO->entity_table = $entityTable;
    $entityFileDAO->entity_id = $entityID;
    $entityFileDAO->file_id = $fileDAO->id;
    $entityFileDAO->save();

    //save static tags
    if (!empty($fileParams['tag'])) {
      CRM_Core_BAO_EntityTag::create($fileParams['tag'], 'civicrm_file', $entityFileDAO->id);
    }

    //save free tags
    if (isset($fileParams['attachment_taglist']) && !empty($fileParams['attachment_taglist'])) {
      CRM_Core_Form_Tag::postProcess($fileParams['attachment_taglist'], $entityFileDAO->id, 'civicrm_file', CRM_Core_DAO::$_nullObject);
    }

    // lets call the post hook here so attachments code can do the right stuff
    CRM_Utils_Hook::post($op, 'File', $fileDAO->id, $fileDAO);
  }

  /**
   * A static function wrapper that deletes the various objects.
   *
   * Objects are those hat are connected to a file object (i.e. file, entityFile and customValue.
   *
   * @param int $fileID
   * @param int $entityID
   * @param int $fieldID
   *
   * @throws \Exception
   */
  public static function deleteFileReferences($fileID, $entityID, $fieldID) {
    $fileDAO = new CRM_Core_DAO_File();
    $fileDAO->id = $fileID;
    if (!$fileDAO->find(TRUE)) {
      CRM_Core_Error::fatal();
    }

    // lets call a pre hook before the delete, so attachments hooks can get the info before things
    // disappear
    CRM_Utils_Hook::pre('delete', 'File', $fileID, $fileDAO);

    // get the table and column name
    list($tableName, $columnName, $groupID) = CRM_Core_BAO_CustomField::getTableColumnGroup($fieldID);

    $entityFileDAO = new CRM_Core_DAO_EntityFile();
    $entityFileDAO->file_id = $fileID;
    $entityFileDAO->entity_id = $entityID;
    $entityFileDAO->entity_table = $tableName;

    if (!$entityFileDAO->find(TRUE)) {
      CRM_Core_Error::fatal();
    }

    $entityFileDAO->delete();
    $fileDAO->delete();

    // also set the value to null of the table and column
    $query = "UPDATE $tableName SET $columnName = null WHERE $columnName = %1";
    $params = array(1 => array($fileID, 'Integer'));
    CRM_Core_DAO::executeQuery($query, $params);
  }

  /**
   * The $useWhere is used so that the signature matches the parent class
   *
   * public function delete($useWhere = FALSE) {
   * list($fileID, $entityID, $fieldID) = func_get_args();
   *
   * self::deleteFileReferences($fileID, $entityID, $fieldID);
   * } */

  /**
   * Delete all the files and associated object associated with this combination.
   *
   * @param string $entityTable
   * @param int $entityID
   * @param int $fileTypeID
   * @param int $fileID
   *
   * @return bool
   *   Was file deleted?
   */
  public static function deleteEntityFile($entityTable, $entityID, $fileTypeID = NULL, $fileID = NULL) {
    $isDeleted = FALSE;
    if (empty($entityTable) || empty($entityID)) {
      return $isDeleted;
    }

    $config = CRM_Core_Config::singleton();

    list($sql, $params) = self::sql($entityTable, $entityID, $fileTypeID, $fileID);
    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    $cfIDs = array();
    $cefIDs = array();
    while ($dao->fetch()) {
      $cfIDs[$dao->cfID] = $dao->uri;
      $cefIDs[] = $dao->cefID;
    }

    if (!empty($cefIDs)) {
      $cefIDs = implode(',', $cefIDs);
      $sql = "DELETE FROM civicrm_entity_file where id IN ( $cefIDs )";
      CRM_Core_DAO::executeQuery($sql);
      $isDeleted = TRUE;
    }

    if (!empty($cfIDs)) {
      // Delete file only if there no any entity using this file.
      $deleteFiles = array();
      foreach ($cfIDs as $fId => $fUri) {
        //delete tags from entity tag table
        $tagParams = array(
          'entity_table' => 'civicrm_file',
          'entity_id' => $fId,
        );

        CRM_Core_BAO_EntityTag::del($tagParams);

        if (!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_EntityFile', $fId, 'id', 'file_id')) {
          unlink($config->customFileUploadDir . DIRECTORY_SEPARATOR . $fUri);
          $deleteFiles[$fId] = $fId;
        }
      }

      if (!empty($deleteFiles)) {
        $deleteFiles = implode(',', $deleteFiles);
        $sql = "DELETE FROM civicrm_file where id IN ( $deleteFiles )";
        CRM_Core_DAO::executeQuery($sql);
      }
      $isDeleted = TRUE;
    }
    return $isDeleted;
  }

  /**
   * Get all the files and associated object associated with this combination.
   *
   * @param string $entityTable
   * @param int $entityID
   * @param bool $addDeleteArgs
   *
   * @return array|null
   */
  public static function getEntityFile($entityTable, $entityID, $addDeleteArgs = FALSE) {
    if (empty($entityTable) || !$entityID) {
      $results = NULL;
      return $results;
    }

    $config = CRM_Core_Config::singleton();

    list($sql, $params) = self::sql($entityTable, $entityID, NULL);
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $results = array();
    while ($dao->fetch()) {
      $result['fileID'] = $dao->cfID;
      $result['entityID'] = $dao->cefID;
      $result['mime_type'] = $dao->mime_type;
      $result['fileName'] = $dao->uri;
      $result['description'] = $dao->description;
      $result['cleanName'] = CRM_Utils_File::cleanFileName($dao->uri);
      $result['fullPath'] = $config->customFileUploadDir . DIRECTORY_SEPARATOR . $dao->uri;
      $result['url'] = CRM_Utils_System::url('civicrm/file', "reset=1&id={$dao->cfID}&eid={$dao->entity_id}");
      $result['href'] = "<a href=\"{$result['url']}\">{$result['cleanName']}</a>";
      $result['tag'] = CRM_Core_BAO_EntityTag::getTag($dao->cfID, 'civicrm_file');
      if ($addDeleteArgs) {
        $result['deleteURLArgs'] = self::deleteURLArgs($dao->entity_table, $dao->entity_id, $dao->cfID);
      }
      $results[$dao->cfID] = $result;
    }

    //fix tag names
    $tags = CRM_Core_PseudoConstant::get('CRM_Core_DAO_EntityTag', 'tag_id', array('onlyActive' => FALSE));

    foreach ($results as &$values) {
      if (!empty($values['tag'])) {
        $tagNames = array();
        foreach ($values['tag'] as $tid) {
          $tagNames[] = $tags[$tid];
        }
        $values['tag'] = implode(', ', $tagNames);
      }
      else {
        $values['tag'] = '';
      }
    }

    $dao->free();
    return $results;
  }

  /**
   * @param string $entityTable
   *   Table-name or "*" (to reference files directly by file-id).
   * @param int $entityID
   * @param int $fileTypeID
   * @param int $fileID
   *
   * @return array
   */
  public static function sql($entityTable, $entityID, $fileTypeID = NULL, $fileID = NULL) {
    if ($entityTable == '*') {
      // $entityID is the ID of a specific file
      $sql = "
SELECT    CF.id as cfID,
           CF.uri as uri,
           CF.mime_type as mime_type,
           CF.description as description,
           CEF.id as cefID,
           CEF.entity_table as entity_table,
           CEF.entity_id as entity_id
FROM      civicrm_file AS CF
LEFT JOIN civicrm_entity_file AS CEF ON ( CEF.file_id = CF.id )
WHERE     CF.id = %2";

    }
    else {
      $sql = "
SELECT    CF.id as cfID,
           CF.uri as uri,
           CF.mime_type as mime_type,
           CF.description as description,
           CEF.id as cefID,
           CEF.entity_table as entity_table,
           CEF.entity_id as entity_id
FROM      civicrm_file AS CF
LEFT JOIN civicrm_entity_file AS CEF ON ( CEF.file_id = CF.id )
WHERE     CEF.entity_table = %1
AND       CEF.entity_id    = %2";
    }

    $params = array(
      1 => array($entityTable, 'String'),
      2 => array($entityID, 'Integer'),
    );

    if ($fileTypeID !== NULL) {
      $sql .= " AND CF.file_type_id = %3";
      $params[3] = array($fileTypeID, 'Integer');
    }

    if ($fileID !== NULL) {
      $sql .= " AND CF.id = %4";
      $params[4] = array($fileID, 'Integer');
    }

    return array($sql, $params);
  }

  /**
   * @param CRM_Core_Form $form
   * @param string $entityTable
   * @param int $entityID
   * @param null $numAttachments
   * @param bool $ajaxDelete
   */
  public static function buildAttachment(&$form, $entityTable, $entityID = NULL, $numAttachments = NULL, $ajaxDelete = FALSE) {

    if (!$numAttachments) {
      $numAttachments = Civi::settings()->get('max_attachments');
    }
    // Assign maxAttachments count to template for help message
    $form->assign('maxAttachments', $numAttachments);

    $config = CRM_Core_Config::singleton();
    // set default max file size as 2MB
    $maxFileSize = $config->maxFileSize ? $config->maxFileSize : 2;

    $currentAttachmentInfo = self::getEntityFile($entityTable, $entityID, TRUE);
    $totalAttachments = 0;
    if ($currentAttachmentInfo) {
      $totalAttachments = count($currentAttachmentInfo);
      $form->add('checkbox', 'is_delete_attachment', ts('Delete All Attachment(s)'));
      $form->assign('currentAttachmentInfo', $currentAttachmentInfo);
    }
    else {
      $form->assign('currentAttachmentInfo', NULL);
    }

    if ($totalAttachments) {
      if ($totalAttachments >= $numAttachments) {
        $numAttachments = 0;
      }
      else {
        $numAttachments -= $totalAttachments;
      }
    }

    $form->assign('numAttachments', $numAttachments);

    CRM_Core_BAO_Tag::getTags('civicrm_file', $tags, NULL,
      '&nbsp;&nbsp;', TRUE);

    // get tagset info
    $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_file');

    // add attachments
    for ($i = 1; $i <= $numAttachments; $i++) {
      $form->addElement('file', "attachFile_$i", ts('Attach File'), 'size=30 maxlength=60');
      $form->addUploadElement("attachFile_$i");
      $form->setMaxFileSize($maxFileSize * 1024 * 1024);
      $form->addRule("attachFile_$i",
        ts('File size should be less than %1 MByte(s)',
          array(1 => $maxFileSize)
        ),
        'maxfilesize',
        $maxFileSize * 1024 * 1024
      );
      $form->addElement('text', "attachDesc_$i", NULL, array(
        'size' => 40,
        'maxlength' => 255,
        'placeholder' => ts('Description'),
      ));

      if (!empty($tags)) {
        $form->add('select', "tag_$i", ts('Tags'), $tags, FALSE,
          array(
            'id' => "tags_$i",
            'multiple' => 'multiple',
            'class' => 'huge crm-select2',
            'placeholder' => ts('- none -'),
          )
        );
      }
      CRM_Core_Form_Tag::buildQuickForm($form, $parentNames, 'civicrm_file', NULL, FALSE, TRUE, "file_taglist_$i");
    }
  }

  /**
   * Return a clean url string and the number of attachment for a
   * given entityTable, entityID
   *
   * @param string $entityTable
   *   The entityTable to which the file is attached.
   * @param int $entityID
   *   The id of the object in the above entityTable.
   * @param string $separator
   *   The string separator where to implode the urls.
   *
   * @return array
   *   An array with 2 elements. The string and the number of attachments
   */
  public static function attachmentInfo($entityTable, $entityID, $separator = '<br />') {
    if (!$entityID) {
      return NULL;
    }

    $currentAttachments = self::getEntityFile($entityTable, $entityID);
    if (!empty($currentAttachments)) {
      $currentAttachmentURL = array();
      foreach ($currentAttachments as $fileID => $attach) {
        $currentAttachmentURL[] = $attach['href'];
      }
      return implode($separator, $currentAttachmentURL);
    }
    return NULL;
  }

  /**
   * @param $formValues
   * @param array $params
   * @param $entityTable
   * @param int $entityID
   */
  public static function formatAttachment(
    &$formValues,
    &$params,
    $entityTable,
    $entityID = NULL
  ) {

    // delete current attachments if applicable
    if ($entityID && !empty($formValues['is_delete_attachment'])) {
      CRM_Core_BAO_File::deleteEntityFile($entityTable, $entityID);
    }

    $numAttachments = Civi::settings()->get('max_attachments');

    // setup all attachments
    for ($i = 1; $i <= $numAttachments; $i++) {
      $attachName = "attachFile_$i";
      $attachDesc = "attachDesc_$i";
      $attachTags = "tag_$i";
      $attachFreeTags = "file_taglist_$i";
      if (isset($formValues[$attachName]) && !empty($formValues[$attachName])) {
        // add static tags if selects
        $tagParams = array();
        if (!empty($formValues[$attachTags])) {
          foreach ($formValues[$attachTags] as $tag) {
            $tagParams[$tag] = 1;
          }
        }

        // we dont care if the file is empty or not
        // CRM-7448
        $extraParams = array(
          'description' => $formValues[$attachDesc],
          'tag' => $tagParams,
          'attachment_taglist' => CRM_Utils_Array::value($attachFreeTags, $formValues, array()),
        );

        CRM_Utils_File::formatFile($formValues, $attachName, $extraParams);

        // set the formatted attachment attributes to $params, later used by
        // CRM_Activity_BAO_Activity::sendEmail(...) to send mail with desired attachments
        if (!empty($formValues[$attachName])) {
          $params[$attachName] = $formValues[$attachName];
        }
      }
    }
  }

  /**
   * @param array $params
   * @param $entityTable
   * @param int $entityID
   */
  public static function processAttachment(&$params, $entityTable, $entityID) {
    $numAttachments = Civi::settings()->get('max_attachments');

    for ($i = 1; $i <= $numAttachments; $i++) {
      if (
        isset($params["attachFile_$i"]) &&
        is_array($params["attachFile_$i"])
      ) {
        self::filePostProcess(
          $params["attachFile_$i"]['location'],
          NULL,
          $entityTable,
          $entityID,
          NULL,
          TRUE,
          $params["attachFile_$i"],
          "attachFile_$i",
          $params["attachFile_$i"]['type']
        );
      }
    }
  }

  /**
   * @return array
   */
  public static function uploadNames() {
    $numAttachments = Civi::settings()->get('max_attachments');

    $names = array();
    for ($i = 1; $i <= $numAttachments; $i++) {
      $names[] = "attachFile_{$i}";
    }
    $names[] = 'uploadFile';
    return $names;
  }

  /**
   * copy/attach an existing file to a different entity
   * table and id.
   *
   * @param $oldEntityTable
   * @param int $oldEntityId
   * @param $newEntityTable
   * @param int $newEntityId
   */
  public static function copyEntityFile($oldEntityTable, $oldEntityId, $newEntityTable, $newEntityId) {
    $oldEntityFile = new CRM_Core_DAO_EntityFile();
    $oldEntityFile->entity_id = $oldEntityId;
    $oldEntityFile->entity_table = $oldEntityTable;
    $oldEntityFile->find();

    while ($oldEntityFile->fetch()) {
      $newEntityFile = new CRM_Core_DAO_EntityFile();
      $newEntityFile->entity_id = $newEntityId;
      $newEntityFile->entity_table = $newEntityTable;
      $newEntityFile->file_id = $oldEntityFile->file_id;
      $newEntityFile->save();
    }
  }

  /**
   * @param $entityTable
   * @param int $entityID
   * @param int $fileID
   *
   * @return string
   */
  public static function deleteURLArgs($entityTable, $entityID, $fileID) {
    $params['entityTable'] = $entityTable;
    $params['entityID'] = $entityID;
    $params['fileID'] = $fileID;

    $signer = new CRM_Utils_Signer(CRM_Core_Key::privateKey(), self::$_signableFields);
    $params['_sgn'] = $signer->sign($params);
    return CRM_Utils_System::makeQueryString($params);
  }

  /**
   * Delete a file attachment from an entity table / entity ID
   *
   */
  public static function deleteAttachment() {
    $params = array();
    $params['entityTable'] = CRM_Utils_Request::retrieve('entityTable', 'String', CRM_Core_DAO::$_nullObject, TRUE);
    $params['entityID'] = CRM_Utils_Request::retrieve('entityID', 'Positive', CRM_Core_DAO::$_nullObject, TRUE);
    $params['fileID'] = CRM_Utils_Request::retrieve('fileID', 'Positive', CRM_Core_DAO::$_nullObject, TRUE);

    $signature = CRM_Utils_Request::retrieve('_sgn', 'String', CRM_Core_DAO::$_nullObject, TRUE);

    $signer = new CRM_Utils_Signer(CRM_Core_Key::privateKey(), self::$_signableFields);
    if (!$signer->validate($signature, $params)) {
      CRM_Core_Error::fatal('Request signature is invalid');
    }

    CRM_Core_BAO_File::deleteEntityFile($params['entityTable'], $params['entityID'], NULL, $params['fileID']);
  }


  /**
   * Display paper icon for a file attachment -- CRM-13624
   *
   * @param string $entityTable
   *   The entityTable to which the file is attached. eg "civicrm_contact", "civicrm_note", "civicrm_activity".
   *                             If you have the ID of a specific row in civicrm_file, use $entityTable='*'
   * @param int $entityID
   *   The id of the object in the above entityTable.
   *
   * @return array|NULL
   *   list of HTML snippets; one HTML snippet for each attachment. If none found, then NULL
   *
   */
  public static function paperIconAttachment($entityTable, $entityID) {
    if (empty($entityTable) || !$entityID) {
      $results = NULL;
      return $results;
    }
    $currentAttachmentInfo = self::getEntityFile($entityTable, $entityID);
    foreach ($currentAttachmentInfo as $fileKey => $fileValue) {
      $fileID = $fileValue['fileID'];
      if ($fileID) {
        $fileType = $fileValue['mime_type'];
        $url = $fileValue['url'];
        $title = $fileValue['cleanName'];
        if ($fileType == 'image/jpeg' ||
          $fileType == 'image/pjpeg' ||
          $fileType == 'image/gif' ||
          $fileType == 'image/x-png' ||
          $fileType == 'image/png'
        ) {
          $file_url[$fileID] = "
              <a href='$url' class='crm-image-popup' title='$title'>
                <i class='crm-i fa-file-image-o'></i>
              </a>";
        }
        // for non image files
        else {
          $file_url[$fileID] = "
              <a href='$url' title='$title'>
                <i class='crm-i fa-paperclip'></i>
              </a>";
        }
      }
    }
    if (empty($file_url)) {
      $results = NULL;
    }
    else {
      $results = $file_url;
    }
    return $results;
  }

  /**
   * Get a reference to the file-search service (if one is available).
   *
   * @return CRM_Core_FileSearchInterface|NULL
   */
  public static function getSearchService() {
    $fileSearches = array();
    CRM_Utils_Hook::fileSearches($fileSearches);

    // use the first available search
    foreach ($fileSearches as $fileSearch) {
      /** @var $fileSearch CRM_Core_FileSearchInterface */
      return $fileSearch;
    }
    return NULL;
  }

}
