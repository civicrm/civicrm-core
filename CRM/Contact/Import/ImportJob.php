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
 * This class acts like a psuedo-BAO for transient import job tables.
 */
class CRM_Contact_Import_ImportJob {

  protected $_onDuplicate;
  protected $_dedupe;
  protected $_newGroupName;
  protected $_newGroupDesc;
  protected $_newGroupType;
  protected $_groups;
  protected $_allGroups;
  protected $_newTagName;
  protected $_newTagDesc;
  protected $_tag;
  protected $_allTags;

  protected $_mapper;
  protected $_mapperKeys = [];
  protected $_mapFields;

  /**
   * @var CRM_Contact_Import_Parser_Contact
   */
  protected $_parser;

  protected $_userJobID;

  /**
   * Has the job completed.
   *
   * @return bool
   */
  public function isComplete(): bool {
    return $this->_parser->isComplete();
  }

  /**
   * @param array $params
   */
  public function setJobParams(&$params) {
    foreach ($params as $param => $value) {
      $fldName = "_$param";
      $this->$fldName = $value;
    }
  }

  /**
   * @param CRM_Core_Form $form
   * @param int $timeout
   */
  public function runImport(&$form, $timeout = 55) {
    $mapper = $this->_mapper;
    foreach ($mapper as $key => $value) {
      $this->_mapperKeys[$key] = $mapper[$key][0] ?? NULL;
    }

    $this->_parser = new CRM_Contact_Import_Parser_Contact(
      $this->_mapperKeys
    );
    $this->_parser->setUserJobID($this->_userJobID);
    $this->_parser->run(
      [],
      CRM_Import_Parser::MODE_IMPORT,
      $this->_statusID
    );

    $contactIds = $this->_parser->getImportedContacts();

    //get the related contactIds. CRM-2926
    $relatedContactIds = $this->_parser->getRelatedImportedContacts();
    if ($relatedContactIds) {
      $contactIds = array_merge($contactIds, $relatedContactIds);
    }

    if ($this->_newGroupName || count($this->_groups)) {
      $groupAdditions = $this->_addImportedContactsToNewGroup($contactIds,
        $this->_newGroupName,
        $this->_newGroupDesc,
        $this->_newGroupType
      );
      if ($form) {
        $form->set('groupAdditions', $groupAdditions);
      }
    }

    if ($this->_newTagName || !empty($this->_tag)) {
      $tagAdditions = $this->_tagImportedContactsWithNewTag($contactIds,
        $this->_newTagName,
        $this->_newTagDesc
      );
      if ($form) {
        $form->set('tagAdditions', $tagAdditions);
      }
    }
  }

  /**
   * @param $form
   */
  public function setFormVariables($form) {
    $this->_parser->set($form, CRM_Import_Parser::MODE_IMPORT);
  }

  /**
   * Add imported contacts.
   *
   * @param array $contactIds
   * @param string $newGroupName
   * @param string $newGroupDesc
   * @param string $newGroupType
   *
   * @return array|bool
   */
  private function _addImportedContactsToNewGroup(
    $contactIds,
    $newGroupName, $newGroupDesc, $newGroupType
  ) {

    $newGroupId = NULL;

    if ($newGroupName) {
      /* Create a new group */
      $newGroupType = $newGroupType ?? [];
      $gParams = array(
        'title' => $newGroupName,
        'description' => $newGroupDesc,
        'group_type' => $newGroupType,
        'is_active' => TRUE,
      );
      $group = CRM_Contact_BAO_Group::create($gParams);
      $this->_groups[] = $newGroupId = $group->id;
    }

    if (is_array($this->_groups)) {
      $groupAdditions = [];
      foreach ($this->_groups as $groupId) {
        $addCount = CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIds, $groupId);
        $totalCount = $addCount[1];
        if ($groupId == $newGroupId) {
          $name = $newGroupName;
          $new = TRUE;
        }
        else {
          $name = $this->_allGroups[$groupId];
          $new = FALSE;
        }
        $groupAdditions[] = array(
          'url' => CRM_Utils_System::url('civicrm/group/search',
            'reset=1&force=1&context=smog&gid=' . $groupId
          ),
          'name' => $name,
          'added' => $totalCount,
          'notAdded' => $addCount[2],
          'new' => $new,
        );
      }
      return $groupAdditions;
    }
    return FALSE;
  }

  /**
   * @param $contactIds
   * @param string $newTagName
   * @param $newTagDesc
   *
   * @return array|bool
   * @throws \CRM_Core_Exception
   */
  private function _tagImportedContactsWithNewTag(
    $contactIds,
    $newTagName, $newTagDesc
  ) {

    $newTagId = NULL;
    if ($newTagName) {
      /* Create a new Tag */

      $tagParams = array(
        'name' => $newTagName,
        'description' => $newTagDesc,
        'is_selectable' => TRUE,
        'used_for' => 'civicrm_contact',
      );
      $addedTag = CRM_Core_BAO_Tag::add($tagParams);
      $this->_tag[$addedTag->id] = 1;
    }
    //add Tag to Import

    if (is_array($this->_tag)) {
      $tagAdditions = [];
      foreach ($this->_tag as $tagId => $val) {
        $addTagCount = CRM_Core_BAO_EntityTag::addEntitiesToTag($contactIds, $tagId, 'civicrm_contact', FALSE);
        $totalTagCount = $addTagCount[1];
        if (isset($addedTag) && $tagId == $addedTag->id) {
          $tagName = $newTagName;
          $new = TRUE;
        }
        else {
          $tagName = $this->_allTags[$tagId];
          $new = FALSE;
        }
        $tagAdditions[] = array(
          'url' => CRM_Utils_System::url('civicrm/contact/search',
            'reset=1&force=1&context=smog&id=' . $tagId
          ),
          'name' => $tagName,
          'added' => $totalTagCount,
          'notAdded' => $addTagCount[2],
          'new' => $new,
        );
      }
      return $tagAdditions;
    }
    return FALSE;
  }

}
