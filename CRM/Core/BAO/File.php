<?php
 /*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.3                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2013                                |
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
  * @copyright CiviCRM LLC (c) 2004-2013
  * $Id$
  *
  */

 /**
  * BAO object for crm_log table
  */
 class CRM_Core_BAO_File extends CRM_Core_DAO_File {

   static $_signableFields = array('entityTable', 'entityID', 'fileID');

   static function path($fileID, $entityID, $entityTable = NULL) {
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


   static function filePostProcess(
     $data,
     $fileTypeID,
     $entityTable,
     $entityID,
     $entitySubtype,
     $overwrite = TRUE,
     $fileParams = NULL,
     $uploadName = 'uploadFile',
     $mimeType = null
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
       break;
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
     $fileDAO->upload_date = date('Ymdhis');
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
    * A static function wrapper that deletes the various objects that are
    * connected to a file object (i.e. file, entityFile and customValue
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
    */
   public function delete($useWhere = false) {
     list($fileID, $entityID, $fieldID) = func_get_args();

     self::deleteFileReferences($fileID, $entityID, $fieldID);
   }

   /**
    * delete all the files and associated object associated with this
    * combination
    */
   static function deleteEntityFile($entityTable, $entityID, $fileTypeID = NULL, $fileID = NULL) {
     if (empty($entityTable) || empty($entityID)) {
       return;
     }

     $config = CRM_Core_Config::singleton();

     list($sql, $params) = self::sql($entityTable, $entityID, $fileTypeID, $fileID);
     $dao    = CRM_Core_DAO::executeQuery($sql, $params);

     $cfIDs  = array();
     $cefIDs = array();
     while ($dao->fetch()) {
       $cfIDs[$dao->cfID] = $dao->uri;
       $cefIDs[] = $dao->cefID;
     }

     if (!empty($cefIDs)) {
       $cefIDs = implode(',', $cefIDs);
       $sql = "DELETE FROM civicrm_entity_file where id IN ( $cefIDs )";
       CRM_Core_DAO::executeQuery($sql);
     }

     if (!empty($cfIDs)) {
       // Delete file only if there no any entity using this file.
       $deleteFiles = array();
       foreach ($cfIDs as $fId => $fUri) {
         //delete tags from entity tag table
         $tagParams = array(
           'entity_table' => 'civicrm_file',
           'entity_id'    => $fId
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
     }
   }

   /**
    * get all the files and associated object associated with this
    * combination
    */
   static function getEntityFile($entityTable, $entityID, $addDeleteArgs = false) {
     if (empty($entityTable) || !$entityID) {
       $results = NULL;
       return $results;
     }

     $config = CRM_Core_Config::singleton();

     list($sql, $params) = self::sql($entityTable, $entityID, NULL);
     $dao = CRM_Core_DAO::executeQuery($sql, $params);
     $results = array();
     while ($dao->fetch()) {
       $result['fileID']    = $dao->cfID;
       $result['entityID']  = $dao->cefID;
       $result['mime_type'] = $dao->mime_type;
       $result['fileName']  = $dao->uri;
       $result['description'] = $dao->description;
       $result['cleanName'] = CRM_Utils_File::cleanFileName($dao->uri);
       $result['fullPath']  = $config->customFileUploadDir . DIRECTORY_SEPARATOR . $dao->uri;
       $result['url']       = CRM_Utils_System::url('civicrm/file', "reset=1&id={$dao->cfID}&eid={$entityID}");
       $result['href']      = "<a href=\"{$result['url']}\">{$result['cleanName']}</a>";
       $result['tag']       = CRM_Core_BAO_EntityTag::getTag($dao->cfID, 'civicrm_file');
       if ($addDeleteArgs) {
         $result['deleteURLArgs'] =  self::deleteURLArgs($entityTable, $entityID, $dao->cfID);
       }
       $results[$dao->cfID] = $result;
     }

     //fix tag names
     $tags = CRM_Core_PseudoConstant::tag();

     foreach($results as &$values) {
       if (!empty($values['tag'])) {
         $tagNames = array();
         foreach( $values['tag'] as $tid ) {
           $tagNames[] = $tags[$tid];
         }
         $values['tag'] = implode(', ', $tagNames);
       } else {
         $values['tag'] = '';
       }
     }

     $dao->free();
     return $results;
   }

   static function sql($entityTable, $entityID, $fileTypeID = NULL, $fileID = NULL) {
     $sql = "
 SELECT    CF.id as cfID,
           CF.uri as uri,
           CF.mime_type as mime_type,
           CF.description as description,
           CEF.id as cefID
 FROM      civicrm_file AS CF
 LEFT JOIN civicrm_entity_file AS CEF ON ( CEF.file_id = CF.id )
 WHERE     CEF.entity_table = %1
 AND       CEF.entity_id    = %2";

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

   static function buildAttachment(&$form, $entityTable, $entityID = NULL, $numAttachments = NULL, $ajaxDelete = FALSE) {

     if (!$numAttachments) {
       $numAttachments = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'max_attachments');
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

     if ( $totalAttachments ) {
       if ($totalAttachments >= $numAttachments) {
         $numAttachments = 0;
       }
       else {
         $numAttachments -= $totalAttachments;
       }
     }

     $form->assign('numAttachments', $numAttachments);

     $tags = CRM_Core_BAO_Tag::getTags('civicrm_file');

     // get tagset info
     $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_file');

     // add attachments
     for ($i = 1; $i <= $numAttachments; $i++) {
       $form->addElement('file', "attachFile_$i", ts('Attach File'), 'size=30 maxlength=60');
       $form->setMaxFileSize($maxFileSize * 1024 * 1024);
       $form->addRule("attachFile_$i",
         ts('File size should be less than %1 MByte(s)',
           array(1 => $maxFileSize)
         ),
         'maxfilesize',
         $maxFileSize * 1024 * 1024
       );
       $form->addElement('text', "attachDesc_$i", ts('Description'), 'size=40 maxlength=255');

       if (!empty($tags)) {
         $form->add('select', "tag_$i", ts('Tags'), $tags, FALSE,
           array('id' => "tags_$i", 'multiple' => 'multiple', 'title' => ts('- select -'))
         );
       }
     }

     // build tagset widget
     CRM_Core_Form_Tag::buildQuickForm($form, $parentNames, 'civicrm_file', NULL, FALSE, TRUE, FALSE);
   }

   /**
    * Function to return a clean url string and the number of attachment for a
    * given entityTable, entityID
    *
    * @param $entityTable string The entityTable to which the file is attached
    * @param $entityID    int    The id of the object in the above entityTable
    * @param $separator   string The string separator where to implode the urls
    *
    * @return array              An array with 2 elements. The string and the number of attachments
    * @static
    */
   static function attachmentInfo($entityTable, $entityID, $separator = '<br />') {
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

   static function formatAttachment(
     &$formValues,
     &$params,
     $entityTable,
     $entityID = NULL
   ) {

     // delete current attachments if applicable
     if ($entityID && CRM_Utils_Array::value('is_delete_attachment', $formValues)) {
       CRM_Core_BAO_File::deleteEntityFile($entityTable, $entityID);
     }

     $numAttachments = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'max_attachments');

     $now = date('Ymdhis');

     // setup all attachments
     for ($i = 1; $i <= $numAttachments; $i++) {
       $attachName = "attachFile_$i";
       $attachDesc = "attachDesc_$i";
       $attachTags = "tag_$i";
       $attachFreeTags = "attachment_taglist_$i";
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
         $fileParams = array(
           'uri' => $formValues[$attachName]['name'],
           'type' => $formValues[$attachName]['type'],
           'location' => $formValues[$attachName]['name'],
           'description' => $formValues[$attachDesc],
           'upload_date' => $now,
           'tag' => $tagParams,
           'attachment_taglist' => CRM_Utils_Array::value($attachFreeTags, $formValues, array())
         );

         $params[$attachName] = $fileParams;
       }
     }
   }

   static function processAttachment(&$params, $entityTable, $entityID) {
     $numAttachments = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'max_attachments');

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

   static function uploadNames() {
     $numAttachments = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'max_attachments');

     $names = array();
     for ($i = 1; $i <= $numAttachments; $i++) {
       $names[] = "attachFile_{$i}";
     }
     $names[] = 'uploadFile';
     return $names;
   }

   /*
    * Function to copy/attach an existing file to a different entity
    * table and id.
    */
   static function copyEntityFile($oldEntityTable, $oldEntityId, $newEntityTable, $newEntityId) {
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

  static function deleteURLArgs($entityTable, $entityID, $fileID) {
    $params['entityTable'] = $entityTable;
    $params['entityID']    = $entityID;
    $params['fileID']      = $fileID;

    $signer = new CRM_Utils_Signer(CRM_Core_Key::privateKey(), self::$_signableFields);
    $params['_sgn'] = $signer->sign($params);
    return CRM_Utils_System::makeQueryString($params);
  }

  /**
   * function to delete a file attachment from an entity table / entity ID
   *
   * @static
   * @access public
   */
  static function deleteAttachment( ) {
    $params = array( );
    $params['entityTable'] = CRM_Utils_Request::retrieve( 'entityTable', 'String'  , CRM_Core_DAO::$_nullObject, TRUE);
    $params['entityID']    = CRM_Utils_Request::retrieve( 'entityID'   , 'Positive', CRM_Core_DAO::$_nullObject, TRUE);
    $params['fileID']      = CRM_Utils_Request::retrieve( 'fileID'     , 'Positive', CRM_Core_DAO::$_nullObject, TRUE);

    $signature = CRM_Utils_Request::retrieve( '_sgn', 'String', CRM_Core_DAO::$_nullObject, TRUE);

    $signer = new CRM_Utils_Signer(CRM_Core_Key::privateKey(), self::$_signableFields);
    if (! $signer->validate($signature, $params)) {
      CRM_Core_Error::fatal('Request signature is invalid');
    }

    CRM_Core_BAO_File::deleteEntityFile($params['entityTable'], $params['entityID'], NULL, $params['fileID']);
  }

}

