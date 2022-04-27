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
 * This class previews the uploaded file and returns summary statistics.
 */
class CRM_Contact_Import_Form_Preview extends CRM_Import_Form_Preview {

  /**
   * Whether USPS validation should be disabled during import.
   *
   * @var bool
   */
  protected $_disableUSPS;

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    $mapper = $this->get('mapper');
    $invalidRowCount = $this->get('invalidRowCount');
    $conflictRowCount = $this->get('conflictRowCount');
    $mismatchCount = $this->get('unMatchCount');
    $columnNames = $this->get('columnNames');
    $this->_disableUSPS = $this->get('disableUSPS');

    //assign column names
    $this->assign('columnNames', $columnNames);

    //get the mapping name displayed if the mappingId is set
    $mappingId = $this->get('loadMappingId');
    if ($mappingId) {
      $mapDAO = new CRM_Core_DAO_Mapping();
      $mapDAO->id = $mappingId;
      $mapDAO->find(TRUE);
    }
    $this->assign('savedMappingName', $mappingId ? $mapDAO->name : NULL);

    $this->assign('rowDisplayCount', 2);

    $groups = CRM_Core_PseudoConstant::nestedGroup();
    $this->set('groups', $groups);

    $tag = CRM_Core_PseudoConstant::get('CRM_Core_DAO_EntityTag', 'tag_id', array('onlyActive' => FALSE));
    if ($tag) {
      $this->set('tag', $tag);
    }

    if ($invalidRowCount) {
      $urlParams = 'type=' . CRM_Import_Parser::ERROR . '&parser=CRM_Contact_Import_Parser_Contact';
      $this->set('downloadErrorRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }

    if ($conflictRowCount) {
      $urlParams = 'type=' . CRM_Import_Parser::CONFLICT . '&parser=CRM_Contact_Import_Parser_Contact';
      $this->set('downloadConflictRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }

    if ($mismatchCount) {
      $urlParams = 'type=' . CRM_Import_Parser::NO_MATCH . '&parser=CRM_Contact_Import_Parser_Contact';
      $this->set('downloadMismatchRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }

    $properties = array(
      'mapper',
      'locations',
      'phones',
      'ims',
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
    $this->assign('dataValues', $this->getDataRows(2));

    $this->setStatusUrl();

    $showColNames = TRUE;
    if ('CRM_Import_DataSource_CSV' == $this->get('dataSource') &&
      !$this->getSubmittedValue('skipColumnHeader')
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
    $groupTypes = CRM_Core_OptionGroup::values('group_type', TRUE);
    if (!empty($groupTypes)) {
      $this->addCheckBox('newGroupType',
        ts('Group Type'),
        $groupTypes,
        NULL, NULL, NULL, NULL, '&nbsp;&nbsp;&nbsp;'
      );
    }

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

    $this->addFormRule(array('CRM_Contact_Import_Form_Preview', 'formRule'), $this);

    parent::buildQuickForm();
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @param $files
   * @param self $self
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];
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
      'dedupe' => $this->getSubmittedValue('dedupe_rule_id'),
      'newGroupName' => $this->controller->exportValue($this->_name, 'newGroupName'),
      'newGroupDesc' => $this->controller->exportValue($this->_name, 'newGroupDesc'),
      'newGroupType' => $this->controller->exportValue($this->_name, 'newGroupType'),
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
      'userJobID' => $this->getUserJobID(),
    );

    $tableName = $this->get('importTableName');
    $importJob = new CRM_Contact_Import_ImportJob($tableName);
    $importJob->setJobParams($importJobParams);

    // If ACL applies to the current user, update cache before running the import.
    if (!CRM_Core_Permission::check('view all contacts')) {
      $userID = CRM_Core_Session::getLoggedInContactID();
      CRM_ACL_BAO_Cache::deleteEntry($userID);
      CRM_ACL_BAO_Cache::deleteContactCacheEntry($userID);
    }

    CRM_Utils_Address_USPS::disable($this->_disableUSPS);

    // run the import
    $importJob->runImport($this);

    // Clear all caches, forcing any searches to recheck the ACLs or group membership as the import
    // may have changed it.
    CRM_Contact_BAO_Contact_Utils::clearContactCaches(TRUE);

    // add all the necessary variables to the form
    $importJob->setFormVariables($this);

    // check if there is any error occurred
    $errorStack = CRM_Core_Error::singleton();
    $errors = $errorStack->getErrors();
    $errorMessage = [];

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

      $urlParams = 'type=' . CRM_Import_Parser::ERROR . '&parser=CRM_Contact_Import_Parser_Contact';
      $this->set('downloadErrorRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));

      $urlParams = 'type=' . CRM_Import_Parser::CONFLICT . '&parser=CRM_Contact_Import_Parser_Contact';
      $this->set('downloadConflictRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));

      $urlParams = 'type=' . CRM_Import_Parser::NO_MATCH . '&parser=CRM_Contact_Import_Parser_Contact';
      $this->set('downloadMismatchRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }

    //hack to clean db
    //if job complete drop table.
    $importJob->isComplete(TRUE);
  }

}
