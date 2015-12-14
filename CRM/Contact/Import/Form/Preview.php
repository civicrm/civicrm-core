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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * This class previews the uploaded file and returns summary statistics.
 */
class CRM_Contact_Import_Form_Preview extends CRM_Import_Form_Preview {

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    //get the data from the session
    $dataValues = $this->get('dataValues');
    $mapper = $this->get('mapper');
    $invalidRowCount = $this->get('invalidRowCount');
    $conflictRowCount = $this->get('conflictRowCount');
    $mismatchCount = $this->get('unMatchCount');
    $columnNames = $this->get('columnNames');

    //assign column names
    $this->assign('columnNames', $columnNames);

    //get the mapping name displayed if the mappingId is set
    $mappingId = $this->get('loadMappingId');
    if ($mappingId) {
      $mapDAO = new CRM_Core_DAO_Mapping();
      $mapDAO->id = $mappingId;
      $mapDAO->find(TRUE);
      $this->assign('loadedMapping', $mappingId);
      $this->assign('savedName', $mapDAO->name);
    }

    $this->assign('rowDisplayCount', 2);

    $groups = CRM_Core_PseudoConstant::nestedGroup();
    $this->set('groups', $groups);

    $tag = CRM_Core_PseudoConstant::get('CRM_Core_DAO_EntityTag', 'tag_id', array('onlyActive' => FALSE));
    if ($tag) {
      $this->set('tag', $tag);
    }

    if ($invalidRowCount) {
      $urlParams = 'type=' . CRM_Import_Parser::ERROR . '&parser=CRM_Contact_Import_Parser';
      $this->set('downloadErrorRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }

    if ($conflictRowCount) {
      $urlParams = 'type=' . CRM_Import_Parser::CONFLICT . '&parser=CRM_Contact_Import_Parser';
      $this->set('downloadConflictRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }

    if ($mismatchCount) {
      $urlParams = 'type=' . CRM_Import_Parser::NO_MATCH . '&parser=CRM_Contact_Import_Parser';
      $this->set('downloadMismatchRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }

    $properties = array(
      'mapper',
      'locations',
      'phones',
      'ims',
      'dataValues',
      'columnCount',
      'totalRowCount',
      'validRowCount',
      'invalidRowCount',
      'conflictRowCount',
      'downloadErrorRecordsUrl',
      'downloadConflictRecordsUrl',
      'downloadMismatchRecordsUrl',
      'related',
      'relatedContactDetails',
      'relatedContactLocType',
      'relatedContactPhoneType',
      'relatedContactImProvider',
      'websites',
      'relatedContactWebsiteType',
    );

    foreach ($properties as $property) {
      $this->assign($property, $this->get($property));
    }

    $statusID = $this->get('statusID');
    if (!$statusID) {
      $statusID = md5(uniqid(rand(), TRUE));
      $this->set('statusID', $statusID);
    }
    $statusUrl = CRM_Utils_System::url('civicrm/ajax/status', "id={$statusID}", FALSE, NULL, FALSE);
    $this->assign('statusUrl', $statusUrl);

    $showColNames = TRUE;
    if ('CRM_Import_DataSource_CSV' == $this->get('dataSource') &&
      !$this->get('skipColumnHeader')
    ) {
      $showColNames = FALSE;
    }
    $this->assign('showColNames', $showColNames);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addElement('text', 'newGroupName', ts('Name for new group'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Group', 'title'));
    $this->addElement('text', 'newGroupDesc', ts('Description of new group'));

    $groups = $this->get('groups');

    if (!empty($groups)) {
      $this->addElement('select', 'groups', ts('Add imported records to existing group(s)'), $groups, array(
          'multiple' => "multiple",
          'class' => 'crm-select2',
        ));
    }

    //display new tag
    $this->addElement('text', 'newTagName', ts('Tag'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Tag', 'name'));
    $this->addElement('text', 'newTagDesc', ts('Description'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Tag', 'description'));

    $tag = $this->get('tag');
    if (!empty($tag)) {
      foreach ($tag as $tagID => $tagName) {
        $this->addElement('checkbox', "tag[$tagID]", NULL, $tagName);
      }
    }

    $path = "_qf_MapField_display=true";
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $form);
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $path .= "&qfKey=$qfKey";
    }

    $previousURL = CRM_Utils_System::url('civicrm/import/contact', $path, FALSE, NULL, FALSE);
    $cancelURL = CRM_Utils_System::url('civicrm/import/contact', 'reset=1');

    $buttons = array(
      array(
        'type' => 'back',
        'name' => ts('Previous'),
        'js' => array('onclick' => "location.href='{$previousURL}'; return false;"),
      ),
      array(
        'type' => 'next',
        'name' => ts('Import Now'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
        'js' => array('onclick' => "return verify( );"),
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
        'js' => array('onclick' => "location.href='{$cancelURL}'; return false;"),
      ),
    );

    $this->addButtons($buttons);

    $this->addFormRule(array('CRM_Contact_Import_Form_Preview', 'formRule'), $this);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @param $files
   * @param $self
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields, $files, $self) {
    $errors = array();
    $invalidTagName = $invalidGroupName = FALSE;

    if (!empty($fields['newTagName'])) {
      if (!CRM_Utils_Rule::objectExists(trim($fields['newTagName']),
        array('CRM_Core_DAO_Tag')
      )
      ) {
        $errors['newTagName'] = ts('Tag \'%1\' already exists.',
          array(1 => $fields['newTagName'])
        );
        $invalidTagName = TRUE;
      }
    }

    if (!empty($fields['newGroupName'])) {
      $title = trim($fields['newGroupName']);
      $name = CRM_Utils_String::titleToVar($title);
      $query = 'select count(*) from civicrm_group where name like %1 OR title like %2';
      $grpCnt = CRM_Core_DAO::singleValueQuery(
        $query,
        array(
          1 => array($name, 'String'),
          2 => array($title, 'String'),
        )
      );
      if ($grpCnt) {
        $invalidGroupName = TRUE;
        $errors['newGroupName'] = ts('Group \'%1\' already exists.', array(1 => $fields['newGroupName']));
      }
    }

    $self->assign('invalidTagName', $invalidTagName);
    $self->assign('invalidGroupName', $invalidGroupName);

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the mapped fields and map it into the uploaded file.
   */
  public function postProcess() {

    $importJobParams = array(
      'doGeocodeAddress' => $this->controller->exportValue('DataSource', 'doGeocodeAddress'),
      'invalidRowCount' => $this->get('invalidRowCount'),
      'conflictRowCount' => $this->get('conflictRowCount'),
      'onDuplicate' => $this->get('onDuplicate'),
      'dedupe' => $this->get('dedupe'),
      'newGroupName' => $this->controller->exportValue($this->_name, 'newGroupName'),
      'newGroupDesc' => $this->controller->exportValue($this->_name, 'newGroupDesc'),
      'groups' => $this->controller->exportValue($this->_name, 'groups'),
      'allGroups' => $this->get('groups'),
      'newTagName' => $this->controller->exportValue($this->_name, 'newTagName'),
      'newTagDesc' => $this->controller->exportValue($this->_name, 'newTagDesc'),
      'tag' => $this->controller->exportValue($this->_name, 'tag'),
      'allTags' => $this->get('tag'),
      'mapper' => $this->controller->exportValue('MapField', 'mapper'),
      'mapFields' => $this->get('fields'),
      'contactType' => $this->get('contactType'),
      'contactSubType' => $this->get('contactSubType'),
      'primaryKeyName' => $this->get('primaryKeyName'),
      'statusFieldName' => $this->get('statusFieldName'),
      'statusID' => $this->get('statusID'),
      'totalRowCount' => $this->get('totalRowCount'),
    );

    $tableName = $this->get('importTableName');
    $importJob = new CRM_Contact_Import_ImportJob($tableName);
    $importJob->setJobParams($importJobParams);

    // If ACL applies to the current user, update cache before running the import.
    if (!CRM_Core_Permission::check('view all contacts')) {
      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');
      CRM_ACL_BAO_Cache::updateEntry($userID);
    }

    // run the import
    $importJob->runImport($this);

    // update cache after we done with runImport
    if (!CRM_Core_Permission::check('view all contacts')) {
      CRM_ACL_BAO_Cache::updateEntry($userID);
    }

    // clear all caches
    CRM_Contact_BAO_Contact_Utils::clearContactCaches();

    // add all the necessary variables to the form
    $importJob->setFormVariables($this);

    // check if there is any error occurred
    $errorStack = CRM_Core_Error::singleton();
    $errors = $errorStack->getErrors();
    $errorMessage = array();

    if (is_array($errors)) {
      foreach ($errors as $key => $value) {
        $errorMessage[] = $value['message'];
      }

      // there is no fileName since this is a sql import
      // so fudge it
      $config = CRM_Core_Config::singleton();
      $errorFile = $config->uploadDir . "sqlImport.error.log";
      if ($fd = fopen($errorFile, 'w')) {
        fwrite($fd, implode('\n', $errorMessage));
      }
      fclose($fd);

      $this->set('errorFile', $errorFile);

      $urlParams = 'type=' . CRM_Import_Parser::ERROR . '&parser=CRM_Contact_Import_Parser';
      $this->set('downloadErrorRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));

      $urlParams = 'type=' . CRM_Import_Parser::CONFLICT . '&parser=CRM_Contact_Import_Parser';
      $this->set('downloadConflictRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));

      $urlParams = 'type=' . CRM_Import_Parser::NO_MATCH . '&parser=CRM_Contact_Import_Parser';
      $this->set('downloadMismatchRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }

    //hack to clean db
    //if job complete drop table.
    $importJob->isComplete(TRUE);
  }

  /**
   * Process the mapped fields and map it into the uploaded file.
   */
  public function postProcessOld() {

    $doGeocodeAddress = $this->controller->exportValue('DataSource', 'doGeocodeAddress');
    $invalidRowCount = $this->get('invalidRowCount');
    $conflictRowCount = $this->get('conflictRowCount');
    $onDuplicate = $this->get('onDuplicate');
    $newGroupName = $this->controller->exportValue($this->_name, 'newGroupName');
    $newGroupDesc = $this->controller->exportValue($this->_name, 'newGroupDesc');
    $groups = $this->controller->exportValue($this->_name, 'groups');
    $allGroups = $this->get('groups');
    $newTagName = $this->controller->exportValue($this->_name, 'newTagName');
    $newTagDesc = $this->controller->exportValue($this->_name, 'newTagDesc');
    $tag = $this->controller->exportValue($this->_name, 'tag');
    $allTags = $this->get('tag');

    $mapper = $this->controller->exportValue('MapField', 'mapper');

    $mapperKeys = array();
    $mapperLocTypes = array();
    $mapperPhoneTypes = array();
    $mapperRelated = array();
    $mapperRelatedContactType = array();
    $mapperRelatedContactDetails = array();
    $mapperRelatedContactLocType = array();
    $mapperRelatedContactPhoneType = array();

    foreach ($mapper as $key => $value) {
      $mapperKeys[$key] = $mapper[$key][0];
      if (is_numeric($mapper[$key][1])) {
        $mapperLocTypes[$key] = $mapper[$key][1];
      }
      else {
        $mapperLocTypes[$key] = NULL;
      }

      if (CRM_Utils_Array::value($key, $mapperKeys) == 'phone') {
        $mapperPhoneTypes[$key] = $mapper[$key][2];
      }
      else {
        $mapperPhoneTypes[$key] = NULL;
      }

      list($id, $first, $second) = explode('_', $mapper[$key][0]);
      if (($first == 'a' && $second == 'b') || ($first == 'b' && $second == 'a')) {
        $relationType = new CRM_Contact_DAO_RelationshipType();
        $relationType->id = $id;
        $relationType->find(TRUE);
        $fieldName = "contact_type_$second";
        $mapperRelatedContactType[$key] = $relationType->$fieldName;
        $mapperRelated[$key] = $mapper[$key][0];
        $mapperRelatedContactDetails[$key] = $mapper[$key][1];
        $mapperRelatedContactLocType[$key] = $mapper[$key][2];
        $mapperRelatedContactPhoneType[$key] = $mapper[$key][3];
      }
      else {
        $mapperRelated[$key] = NULL;
        $mapperRelatedContactType[$key] = NULL;
        $mapperRelatedContactDetails[$key] = NULL;
        $mapperRelatedContactLocType[$key] = NULL;
        $mapperRelatedContactPhoneType[$key] = NULL;
      }
    }

    $parser = new CRM_Contact_Import_Parser_Contact($mapperKeys, $mapperLocTypes,
      $mapperPhoneTypes, $mapperRelated, $mapperRelatedContactType,
      $mapperRelatedContactDetails, $mapperRelatedContactLocType,
      $mapperRelatedContactPhoneType
    );

    $mapFields = $this->get('fields');

    $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
    $phoneTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');

    foreach ($mapper as $key => $value) {
      $header = array();
      list($id, $first, $second) = explode('_', $mapper[$key][0]);
      if (($first == 'a' && $second == 'b') || ($first == 'b' && $second == 'a')) {
        $relationType = new CRM_Contact_DAO_RelationshipType();
        $relationType->id = $id;
        $relationType->find(TRUE);

        $header[] = $relationType->name_a_b;
        $header[] = ucwords(str_replace("_", " ", $mapper[$key][1]));

        if (isset($mapper[$key][2])) {
          $header[] = $locationTypes[$mapper[$key][2]];
        }
        if (isset($mapper[$key][3])) {
          $header[] = $phoneTypes[$mapper[$key][3]];
        }
      }
      else {
        if (isset($mapFields[$mapper[$key][0]])) {
          $header[] = $mapFields[$mapper[$key][0]];
          if (isset($mapper[$key][1])) {
            $header[] = $locationTypes[$mapper[$key][1]];
          }
          if (isset($mapper[$key][2])) {
            $header[] = $phoneTypes[$mapper[$key][2]];
          }
        }
      }
      $mapperFields[] = implode(' - ', $header);
    }

    $tableName = $this->get('importTableName');
    //print "Running parser on table: $tableName<br/>";
    $parser->run($tableName, $mapperFields,
      CRM_Import_Parser::MODE_IMPORT,
      $this->get('contactType'),
      $this->get('primaryKeyName'),
      $this->get('statusFieldName'),
      $onDuplicate,
      $this->get('statusID'),
      $this->get('totalRowCount'),
      $doGeocodeAddress,
      CRM_Contact_Import_Parser::DEFAULT_TIMEOUT,
      $this->get('contactSubType'),
      $this->get('dedupe')
    );

    // add the new contacts to selected groups
    $contactIds = &$parser->getImportedContacts();

    // add the new related contacts to selected groups
    $relatedContactIds = &$parser->getRelatedImportedContacts();

    $this->set('relatedCount', count($relatedContactIds));
    $newGroupId = NULL;

    //changed below if-statement "if ($newGroup) {" to "if ($newGroupName) {"
    if ($newGroupName) {
      /* Create a new group */

      $gParams = array(
        'name' => $newGroupName,
        'title' => $newGroupName,
        'description' => $newGroupDesc,
        'is_active' => TRUE,
      );
      $group = CRM_Contact_BAO_Group::create($gParams);
      $groups[] = $newGroupId = $group->id;
    }

    if (is_array($groups)) {
      $groupAdditions = array();
      foreach ($groups as $groupId) {
        $addCount = CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIds, $groupId);
        if (!empty($relatedContactIds)) {
          $addRelCount = CRM_Contact_BAO_GroupContact::addContactsToGroup($relatedContactIds, $groupId);
        }
        $totalCount = $addCount[1] + $addRelCount[1];
        if ($groupId == $newGroupId) {
          $name = $newGroupName;
          $new = TRUE;
        }
        else {
          $name = $allGroups[$groupId];
          $new = FALSE;
        }
        $groupAdditions[] = array(
          'url' => CRM_Utils_System::url('civicrm/group/search',
            'reset=1&force=1&context=smog&gid=' . $groupId
          ),
          'name' => $name,
          'added' => $totalCount,
          'notAdded' => $addCount[2] + $addRelCount[2],
          'new' => $new,
        );
      }
      $this->set('groupAdditions', $groupAdditions);
    }

    $newTagId = NULL;
    if ($newTagName) {
      /* Create a new Tag */

      $tagParams = array(
        'name' => $newTagName,
        'title' => $newTagName,
        'description' => $newTagDesc,
        'is_active' => TRUE,
      );
      $id = array();
      $addedTag = CRM_Core_BAO_Tag::add($tagParams, $id);
      $tag[$addedTag->id] = 1;
    }
    //add Tag to Import

    if (is_array($tag)) {

      $tagAdditions = array();
      foreach ($tag as $tagId => $val) {
        $addTagCount = CRM_Core_BAO_EntityTag::addContactsToTag($contactIds, $tagId);
        if (!empty($relatedContactIds)) {
          $addRelTagCount = CRM_Core_BAO_EntityTag::addContactsToTag($relatedContactIds, $tagId);
        }
        $totalTagCount = $addTagCount[1] + $addRelTagCount[1];
        if ($tagId == $addedTag->id) {
          $tagName = $newTagName;
          $new = TRUE;
        }
        else {
          $tagName = $allTags[$tagId];
          $new = FALSE;
        }
        $tagAdditions[] = array(
          'url' => CRM_Utils_System::url('civicrm/contact/search',
            'reset=1&force=1&context=smog&id=' . $tagId
          ),
          'name' => $tagName,
          'added' => $totalTagCount,
          'notAdded' => $addTagCount[2] + $addRelTagCount[2],
          'new' => $new,
        );
      }
      $this->set('tagAdditions', $tagAdditions);
    }

    // add all the necessary variables to the form
    $parser->set($this, CRM_Import_Parser::MODE_IMPORT);

    // check if there is any error occurred

    $errorStack = CRM_Core_Error::singleton();
    $errors = $errorStack->getErrors();
    $errorMessage = array();

    if (is_array($errors)) {
      foreach ($errors as $key => $value) {
        $errorMessage[] = $value['message'];
      }

      // there is no fileName since this is a sql import
      // so fudge it
      $config = CRM_Core_Config::singleton();
      $errorFile = $config->uploadDir . "sqlImport.error.log";
      if ($fd = fopen($errorFile, 'w')) {
        fwrite($fd, implode('\n', $errorMessage));
      }
      fclose($fd);

      $this->set('errorFile', $errorFile);

      $urlParams = 'type=' . CRM_Import_Parser::ERROR . '&parser=CRM_Contact_Import_Parser';
      $this->set('downloadErrorRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlparams));

      $urlParams = 'type=' . CRM_Import_Parser::CONFLICT . '&parser=CRM_Contact_Import_Parser';
      $this->set('downloadConflictRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));

      $urlParams = 'type=' . CRM_Import_Parser::NO_MATCH . '&parser=CRM_Contact_Import_Parser';
      $this->set('downloadMismatchRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }
  }

}
