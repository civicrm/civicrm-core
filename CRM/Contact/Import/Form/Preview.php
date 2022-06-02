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

use Civi\Api4\Group;
use Civi\Api4\Tag;

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
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    parent::preProcess();
    $this->_disableUSPS = $this->getSubmittedValue('disableUSPS');
    $this->setStatusUrl();
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

    $groups = CRM_Core_PseudoConstant::nestedGroup();;

    if (!empty($groups)) {
      $this->addElement('select', 'groups', ts('Add imported records to existing group(s)'), $groups, [
        'multiple' => "multiple",
        'class' => 'crm-select2',
      ]);
    }

    //display new tag
    $this->addElement('text', 'newTagName', ts('Tag'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Tag', 'name'));
    $this->addElement('text', 'newTagDesc', ts('Description'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Tag', 'description'));

    $tag = CRM_Core_PseudoConstant::get('CRM_Core_DAO_EntityTag', 'tag_id', ['onlyActive' => FALSE]);
    if (!empty($tag)) {
      $this->addElement('select', 'tag', ts(' Tag imported records'), $tag, [
        'multiple' => 'multiple',
        'class' => 'crm-select2',
      ]);
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
   *
   * @throws \API_Exception|\CRM_Core_Exception
   */
  public function postProcess(): void {
    $groupsToAddTo = (array) $this->getSubmittedValue('groups');
    $summaryInfo = ['groups' => [], 'tags' => []];
    foreach ($groupsToAddTo as $groupID) {
      // This is a convenience for now - really url & name should be determined at
      // presentation stage - ie the summary screen. The only info we are really
      // preserving is which groups were created vs already existed.
      $summaryInfo['groups'][$groupID] = [
        'url' => CRM_Utils_System::url('civicrm/group/search', 'reset=1&force=1&context=smog&gid=' . $groupID),
        'name' => Group::get(FALSE)->addWhere('id', '=', $groupID)->addSelect('name')->execute()->first()['name'],
        'new' => FALSE,
        'added' => 0,
        'notAdded' => 0,
      ];
    }

    if ($this->getSubmittedValue('newGroupName')) {
      /* Create a new group */
      $groupsToAddTo[] = $groupID = Group::create(FALSE)->setValues([
        'title' => $this->getSubmittedValue('newGroupName'),
        'description' => $this->getSubmittedValue('newGroupDesc'),
        'group_type' => $this->getSubmittedValue('newGroupType') ?? [],
        'is_active' => TRUE,
      ])->execute()->first()['id'];
      $summaryInfo['groups'][$groupID] = [
        'url' => CRM_Utils_System::url('civicrm/group/search', 'reset=1&force=1&context=smog&gid=' . $groupID),
        'name' => $this->getSubmittedValue('newGroupName'),
        'new' => TRUE,
        'added' => 0,
        'notAdded' => 0,
      ];
    }
    $tagsToAdd = (array) $this->getSubmittedValue('tag');
    foreach ($tagsToAdd as $tagID) {
      // This is a convenience for now - really url & name should be determined at
      // presentation stage - ie the summary screen. The only info we are really
      // preserving is which tags were created vs already existed.
      $summaryInfo['tags'][$tagID] = [
        'url' => CRM_Utils_System::url('civicrm/contact/search', 'reset=1&force=1&context=smog&id=' . $tagID),
        'name' => Tag::get(FALSE)->addWhere('id', '=', $tagID)->addSelect('name')->execute()->first()['name'],
        'new' => TRUE,
        'added' => 0,
        'notAdded' => 0,
      ];
    }
    if ($this->getSubmittedValue('newTagName')) {
      $tagsToAdd[] = $tagID = Tag::create(FALSE)->setValues([
        'name' => $this->getSubmittedValue('newTagName'),
        'description' => $this->getSubmittedValue('newTagDesc'),
        'is_selectable' => TRUE,
        'used_for' => 'civicrm_contact',
      ])->execute()->first()['id'];
      $summaryInfo['tags'][$tagID] = [
        'url' => CRM_Utils_System::url('civicrm/contact/search', 'reset=1&force=1&context=smog&id=' . $tagID),
        'name' => $this->getSubmittedValue('newTagName'),
        'new' => FALSE,
        'added' => 0,
        'notAdded' => 0,
      ];
    }
    // Store the actions to take on each row & the data to present at the end to the userJob.
    $this->updateUserJobMetadata('post_actions', ['group' => $groupsToAddTo, 'tag' => $tagsToAdd]);
    $this->updateUserJobMetadata('summary_info', $summaryInfo);

    // If ACL applies to the current user, update cache before running the import.
    if (!CRM_Core_Permission::check('view all contacts')) {
      $userID = CRM_Core_Session::getLoggedInContactID();
      CRM_ACL_BAO_Cache::deleteEntry($userID);
      CRM_ACL_BAO_Cache::deleteContactCacheEntry($userID);
    }

    CRM_Utils_Address_USPS::disable($this->_disableUSPS);

    // run the import

    $this->_parser = $this->getParser();
    $this->_parser->run(
      [],
      CRM_Import_Parser::MODE_IMPORT,
      $this->get('statusID')
    );

    // Clear all caches, forcing any searches to recheck the ACLs or group membership as the import
    // may have changed it.
    CRM_Contact_BAO_Contact_Utils::clearContactCaches(TRUE);

    // check if there is any error occurred
    // @todo - it's really unclear that this error code should still exist...
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
    }
  }

  /**
   * @return \CRM_Contact_Import_Parser_Contact
   */
  protected function getParser(): CRM_Contact_Import_Parser_Contact {
    if (!$this->parser) {
      $this->parser = new CRM_Contact_Import_Parser_Contact();
      $this->parser->setUserJobID($this->getUserJobID());
    }
    return $this->parser;
  }

}
