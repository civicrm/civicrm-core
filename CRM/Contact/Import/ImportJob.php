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

  protected $_tableName;
  protected $_primaryKeyName;
  protected $_statusFieldName;

  protected $_doGeocodeAddress;
  protected $_invalidRowCount;
  protected $_conflictRowCount;
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

  protected $_parser;

  /**
   * @param null $tableName
   * @param null $createSql
   * @param bool $createTable
   *
   * @throws \CRM_Core_Exception
   */
  public function __construct($tableName = NULL, $createSql = NULL, $createTable = FALSE) {
    $dao = new CRM_Core_DAO();
    $db = $dao->getDatabaseConnection();

    if ($createTable) {
      if (!$createSql) {
        throw new CRM_Core_Exception(ts('Either an existing table name or an SQL query to build one are required'));
      }
      if ($tableName) {
        // Drop previous table if passed in and create new one.
        $db->query("DROP TABLE IF EXISTS $tableName");
      }
      $table = CRM_Utils_SQL_TempTable::build()->setDurable();
      $tableName = $table->getName();
      $table->createWithQuery($createSql);
    }

    if (!$tableName) {
      throw new CRM_Core_Exception(ts('Import Table is required.'));
    }

    $this->_tableName = $tableName;
  }

  /**
   * @return null|string
   */
  public function getTableName() {
    return $this->_tableName;
  }

  /**
   * @param bool $dropIfComplete
   *
   * @return bool
   * @throws Exception
   */
  public function isComplete($dropIfComplete = TRUE) {
    if (!$this->_statusFieldName) {
      throw new CRM_Core_Exception("Could not get name of the import status field");
    }
    $query = "SELECT * FROM $this->_tableName
                  WHERE  $this->_statusFieldName = 'NEW' LIMIT 1";
    $result = CRM_Core_DAO::executeQuery($query);
    if ($result->fetch()) {
      return FALSE;
    }
    if ($dropIfComplete) {
      $query = "DROP TABLE $this->_tableName";
      CRM_Core_DAO::executeQuery($query);
    }
    return TRUE;
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
    $mapperFields = [];
    $parserParameters = CRM_Contact_Import_Parser_Contact::getParameterForParser(count($mapper));
    $phoneTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
    $imProviders = CRM_Core_PseudoConstant::get('CRM_Core_DAO_IM', 'provider_id');
    $websiteTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Website', 'website_type_id');
    $locationTypes = array('Primary' => ts('Primary')) + CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');

    foreach ($mapper as $key => $value) {

      $fldName = $mapper[$key][0] ?? NULL;
      $header = array($this->_mapFields[$fldName]);
      $selOne = $mapper[$key][1] ?? NULL;
      $selTwo = $mapper[$key][2] ?? NULL;
      $selThree = $mapper[$key][3] ?? NULL;
      $this->_mapperKeys[$key] = $fldName;

      //need to differentiate non location elements.
      // @todo merge this with duplicate code on MapField class.
      if ($selOne && (is_numeric($selOne) || $selOne === 'Primary')) {
        if ($fldName === 'url') {
          $header[] = $websiteTypes[$selOne];
          $parserParameters['mapperWebsiteType'][$key] = $selOne;
        }
        else {
          $header[] = $locationTypes[$selOne];
          $parserParameters['mapperLocType'][$key] = $selOne;
          if ($selTwo && is_numeric($selTwo)) {
            if ($fldName === 'phone') {
              $header[] = $phoneTypes[$selTwo];
              $parserParameters['mapperPhoneType'][$key] = $selTwo;
            }
            elseif ($fldName === 'im') {
              $header[] = $imProviders[$selTwo];
              $parserParameters['mapperImProvider'][$key] = $selTwo;
            }
          }
        }
      }

      $fldNameParts = explode('_', $fldName, 3);
      $id = $fldNameParts[0];
      $first = $fldNameParts[1] ?? NULL;
      $second = $fldNameParts[2] ?? NULL;
      if (($first == 'a' && $second == 'b') ||
        ($first == 'b' && $second == 'a')
      ) {

        $header[] = ucwords(str_replace("_", " ", $selOne));

        $relationType = new CRM_Contact_DAO_RelationshipType();
        $relationType->id = $id;
        $relationType->find(TRUE);
        $parserParameters['relatedContactType'][$key] = $relationType->{"contact_type_$second"};

        $parserParameters['mapperRelated'][$key] = $fldName;
        if ($selOne) {
          $parserParameters['relatedContactDetails'][$key] = $selOne;
          if ($selTwo) {
            if ($selOne == 'url') {
              $header[] = $websiteTypes[$selTwo];
              $parserParameters[$key]['relatedContactWebsiteType'][$key] = $selTwo;
            }
            else {
              $header[] = $locationTypes[$selTwo];
              $parserParameters['relatedContactLocType'][$key] = $selTwo;
              if ($selThree) {
                if ($selOne == 'phone') {
                  $header[] = $phoneTypes[$selThree];
                  $parserParameters['relatedContactPhoneType'][$key] = $selThree;
                }
                elseif ($selOne == 'im') {
                  $header[] = $imProviders[$selThree];
                  $parserParameters['relatedContactImProvider'][$key] = $selThree;
                }
              }
            }
          }
        }
      }
      $mapperFields[] = implode(' - ', $header);
    }

    $this->_parser = new CRM_Contact_Import_Parser_Contact(
      $this->_mapperKeys,
      $parserParameters['mapperLocType'],
      $parserParameters['mapperPhoneType'],
      $parserParameters['mapperImProvider'],
      $parserParameters['mapperRelated'],
      $parserParameters['relatedContactType'],
      $parserParameters['relatedContactDetails'],
      $parserParameters['relatedContactLocType'],
      $parserParameters['relatedContactPhoneType'],
      $parserParameters['relatedContactImProvider'],
      $parserParameters['mapperWebsiteType'],
      $parserParameters['relatedContactWebsiteType']
    );

    $this->_parser->run($this->_tableName, $mapperFields,
      CRM_Import_Parser::MODE_IMPORT,
      $this->_contactType,
      $this->_primaryKeyName,
      $this->_statusFieldName,
      $this->_onDuplicate,
      $this->_statusID,
      $this->_totalRowCount,
      $this->_doGeocodeAddress,
      CRM_Contact_Import_Parser::DEFAULT_TIMEOUT,
      $this->_contactSubType,
      $this->_dedupe
    );

    $contactIds = $this->_parser->getImportedContacts();

    //get the related contactIds. CRM-2926
    $relatedContactIds = $this->_parser->getRelatedImportedContacts();
    if ($relatedContactIds) {
      $contactIds = array_merge($contactIds, $relatedContactIds);
      if ($form) {
        $form->set('relatedCount', count($relatedContactIds));
      }
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
