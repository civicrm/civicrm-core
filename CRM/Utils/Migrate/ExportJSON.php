<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Utils_Migrate_ExportJSON {
  CONST CHUNK_SIZE = 128;

  protected $_contactIDs;

  protected $_allContactIDs;

  protected $_values;

  protected $_discoverContacts = FALSE;

  protected $_renameGroups = 1;

  protected $_renameTags = 1;

  protected $_sitePrefix = 'Site 1';

  /**
   * @param $params
   */
  function __construct(&$params) {
    foreach ($params as $name => $value) {
      $varName = '_' . $name;
      $this->$varName = $value;
    }
  }

  /**
   * Split a large array of contactIDs into more manageable smaller chunks
   */
  function &splitContactIDs(&$contactIDs) {
    // contactIDs could be a real large array, so we split it up into
    // smaller chunks and then general xml for each chunk
    $chunks = array();
    $current = 0;
    $chunks[$current] = array();
    $count = 0;

    foreach ($contactIDs as $k => $v) {
      $chunks[$current][$k] = $v;
      $count++;

      if ($count == self::CHUNK_SIZE) {
        $current++;
        $chunks[$current] = array();
        $count = 0;
      }
    }

    if (empty($chunks[$current])) {
      unset($chunks[$current]);
    }

    return $chunks;
  }

  /**
   * Given a set of contact IDs get the values
   */
  function getValues(&$contactIDs, &$additionalContactIDs) {

    $this->contact($contactIDs);
    $this->address($contactIDs);
    $this->phone($contactIDs);
    $this->email($contactIDs);
    $this->im($contactIDs);
    $this->website($contactIDs);
    $this->note($contactIDs);

    $this->group($contactIDs);
    $this->groupContact($contactIDs);
    $this->savedSearch($contactIDs);

    $this->tag($contactIDs);
    $this->entityTag($contactIDs);

    $this->relationship($contactIDs, $additionalContactIDs);
    $this->activity($contactIDs, $additionalContactIDs);
  }

  function metaData() {
    $optionGroupVars = array(
      'prefix_id' => 'individual_prefix',
      'suffix_id' => 'individual_suffix',
      'gender_id' => 'gender',
      'mobile_provider' => 'mobile_provider',
      'phone_type' => 'phone_type',
      'activity_type' => 'activity_type',
      'status_id' => 'activity_status_id',
      'priority_id' => 'activity_priority_id',
      'medium_id' => 'encounter_medium',
      'communication_style_id' => 'communication_style',
      'email_greeting' => 'email_greeting',
      'postal_greeting' => 'postal_greeting',
      'addressee_id' => 'addressee',
    );
    $this->optionGroup($optionGroupVars);

    $auxilaryTables = array(
      'civicrm_location_type' => 'CRM_Core_DAO_LocationType',
      'civicrm_relationship_type' => 'CRM_Contact_DAO_RelationshipType',
    );
    $this->auxTable($auxilaryTables);
  }

  /**
   * @param $tables
   */
  function auxTable($tables) {
    foreach ($tables as $tableName => $daoName) {
      $fields = & $this->dbFields($daoName, TRUE);

      $sql = "SELECT * from $tableName";
      $this->sql($sql, $tableName, $fields);
    }
  }

  /**
   * @param $optionGroupVars
   */
  function optionGroup($optionGroupVars) {
    $names = array_values($optionGroupVars);
    $str = array();
    foreach ($names as $name) {
      $str[] = "'$name'";
    }
    $nameString = implode(",", $str);

    $sql = "
SELECT *
FROM   civicrm_option_group
WHERE  name IN ( $nameString )
";
    $fields = & $this->dbFields('CRM_Core_DAO_OptionGroup', TRUE);
    $this->sql($sql, 'civicrm_option_group', $fields);

    $sql = "
SELECT     v.*
FROM       civicrm_option_value v
INNER JOIN civicrm_option_group g ON v.option_group_id = g.id
WHERE      g.name IN ( $nameString )
";
    $fields = & $this->dbFields('CRM_Core_DAO_OptionValue', TRUE);
    $this->sql($sql, 'civicrm_option_value', $fields);
  }

  /**
   * @param $ids
   * @param $tableName
   * @param $fields
   * @param $whereField
   * @param null $additionalWhereCond
   */
  function table(&$ids,
                 $tableName,
                 &$fields,
                 $whereField,
                 $additionalWhereCond = NULL
  ) {
    if (empty($ids)) {
      return;
    }

    $idString = implode(',', $ids);

    $sql = "
SELECT *
  FROM $tableName
 WHERE $whereField IN ( $idString )
";

    if ($additionalWhereCond) {
      $sql .= " AND $additionalWhereCond";
    }

    $this->sql($sql, $tableName, $fields);
  }

  /**
   * @param $sql
   * @param $tableName
   * @param $fields
   */
  function sql($sql, $tableName, &$fields) {
    $dao = & CRM_Core_DAO::executeQuery($sql);

    while ($dao->fetch()) {
      $value = array();
      foreach ($fields as $name) {
        if (empty($dao->$name)) {
          $value[$name] = NULL;
        }
        else {
          $value[$name] = $dao->$name;
        }
      }
      $this->appendValue($dao->id, $tableName, $value);
    }
    $dao->free();
  }

  /**
   * @param $contactIDs
   */
  function contact(&$contactIDs) {
    $fields = & $this->dbFields('CRM_Contact_DAO_Contact', TRUE);
    $this->table($contactIDs, 'civicrm_contact', $fields, 'id', NULL);
  }

  /**
   * @param $contactIDs
   */
  function note(&$contactIDs) {
    $fields = & $this->dbFields('CRM_Core_DAO_Note', TRUE);
    $this->table($contactIDs, 'civicrm_note', $fields, 'entity_id', "entity_table = 'civicrm_contact'");
  }

  /**
   * @param $contactIDs
   */
  function phone(&$contactIDs) {
    $fields = & $this->dbFields('CRM_Core_DAO_Phone', TRUE);
    $this->table($contactIDs, 'civicrm_phone', $fields, 'contact_id', NULL);
  }

  /**
   * @param $contactIDs
   */
  function email(&$contactIDs) {
    $fields = & $this->dbFields('CRM_Core_DAO_Email', TRUE);
    $this->table($contactIDs, 'civicrm_email', $fields, 'contact_id', NULL);
  }

  /**
   * @param $contactIDs
   */
  function im(&$contactIDs) {
    $fields = & $this->dbFields('CRM_Core_DAO_IM', TRUE);
    $this->table($contactIDs, 'civicrm_im', $fields, 'contact_id', NULL);
  }

  /**
   * @param $contactIDs
   */
  function website(&$contactIDs) {
    $fields = & $this->dbFields('CRM_Core_DAO_Website', TRUE);
    $this->table($contactIDs, 'civicrm_website', $fields, 'contact_id', NULL);
  }

  /**
   * @param $contactIDs
   */
  function address(&$contactIDs) {
    $fields = & $this->dbFields('CRM_Core_DAO_Email', TRUE);
    $this->table($contactIDs, 'civicrm_address', $fields, 'contact_id', NULL);
  }

  /**
   * @param $contactIDs
   */
  function groupContact(&$contactIDs) {
    $fields = & $this->dbFields('CRM_Contact_DAO_GroupContact', TRUE);
    $this->table($contactIDs, 'civicrm_group_contact', $fields, 'contact_id', NULL);
  }

  // TODO - support group inheritance
  // Parent child group ids are encoded in a text string
  /**
   * @param $contactIDs
   */
  function group(&$contactIDs) {
    // handle groups only once
    static $_groupsHandled = array();

    $ids = implode(',', $contactIDs);

    $sql = "
SELECT DISTINCT group_id
FROM   civicrm_group_contact
WHERE  contact_id IN ( $ids )
";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $groupIDs = array();
    while ($dao->fetch()) {
      if (!isset($_groupsHandled[$dao->group_id])) {
        $groupIDs[] = $dao->group_id;
        $_groupsHandled[$dao->group_id] = 1;
      }
    }

    $fields = & $this->dbFields('CRM_Contact_DAO_Group', TRUE);
    $this->table($groupIDs, 'civicrm_group', $fields, 'id');

    $this->savedSearch($groupIDs);
  }

  // TODO - support search builder and custom saved searches
  /**
   * @param $groupIDs
   */
  function savedSearch(&$groupIDs) {
    if (empty($groupIDs)) {
      return;
    }

    $idString = implode(",", $groupIDs);
    $sql = "
SELECT     s.*
FROM       civicrm_saved_search s
INNER JOIN civicrm_group g on g.saved_search_id = s.id
WHERE      g.id IN ( $idString )
";

    $fields = & $this->dbFields('CRM_Contact_DAO_SavedSearch', TRUE);
    $this->sql($sql, 'civicrm_saved_search', $fields);
  }

  /**
   * @param $contactIDs
   */
  function entityTag(&$contactIDs) {
    $fields = & $this->dbFields('CRM_Core_DAO_EntityTag', TRUE);
    $this->table($contactIDs, 'civicrm_entity_tag', $fields, 'entity_id', "entity_table = 'civicrm_contact'");
  }

  /**
   * @param $contactIDs
   */
  function tag(&$contactIDs) {
    // handle tags only once
    static $_tagsHandled = array();

    $ids = implode(',', $contactIDs);

    $sql = "
SELECT DISTINCT tag_id
FROM   civicrm_entity_tag
WHERE  entity_id IN ( $ids )
AND    entity_table = 'civicrm_contact'
";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $tagIDs = array();
    while ($dao->fetch()) {
      if (!isset($_tagsHandled[$dao->tag_id])) {
        $tagIDs[] = $dao->tag_id;
        $_tagsHandled[$dao->tag_id] = 1;
      }
    }

    $fields = & $this->dbFields('CRM_Core_DAO_Tag', TRUE);
    $this->table($tagIDs, 'civicrm_tag', $fields, 'id');
  }

  /**
   * @param $contactIDs
   * @param $additionalContacts
   */
  function relationship(&$contactIDs, &$additionalContacts) {
    // handle relationships only once
    static $_relationshipsHandled = array();

    $ids = implode(',', $contactIDs);

    $sql = "(
  SELECT     r.*
  FROM       civicrm_relationship r
  WHERE      r.contact_id_a IN ( $ids )
) UNION (
  SELECT     r.*
  FROM       civicrm_relationship r
  WHERE      r.contact_id_b IN ( $ids )
)
";

    $fields = $this->dbFields('CRM_Contact_DAO_Relationship', TRUE);
    $dao = & CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      if (isset($_relationshipsHandled[$dao->id])) {
        continue;
      }
      $_relationshipsHandled[$dao->id] = $dao->id;

      $relationship = array();
      foreach ($fields as $fld) {
        if (empty($dao->$fld)) {
          $relationship[$fld] = NULL;
        }
        else {
          $relationship[$fld] = $dao->$fld;
        }
      }
      $this->appendValue($dao->id, 'civicrm_relationship', $relationship);

      $this->addAdditionalContacts(array(
          $dao->contact_id_a,
          $dao->contact_id_b,
        ),
        $additionalContacts
      );
    }
    $dao->free();
  }

  /**
   * @param $contactIDs
   * @param $additionalContacts
   */
  function activity(&$contactIDs, &$additionalContacts) {
    static $_activitiesHandled = array();
    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
    $ids = implode(',', $contactIDs);

    // query framing returning all contacts in valid activity
    $sql = "
SELECT  a.*, ac.id as acID, ac.activity_id, ac.contact_id, ac.record_type_id
FROM civicrm_activity a
INNER JOIN civicrm_activity_contact ac ON ac.activity_id = a.id
WHERE ac.contact_id IN ( $ids )
  AND (a.activity_type_id != 3 AND a.activity_type_id != 20)
";

    $fields = & $this->dbFields('CRM_Activity_DAO_Activity', TRUE);

    $dao = & CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      // adding source, target and assignee contacts in additional contacts array
      $this->addAdditionalContacts(array($dao->contact_id),
        $additionalContacts
      );

      // append values of activity contacts
      $activityContacts = array(
        'id' => $dao->acID,
        'contact_id' => $dao->contact_id,
        'activity_id' => $dao->activity_id,
        'record_type_id' => $dao->record_type_id
      );
      $this->appendValue($dao->acID, 'civicrm_activity_contact', $activityContacts);

      if (isset($_activitiesHandled[$dao->id])) {
        continue;
      }
      $_activitiesHandled[$dao->id] = $dao->id;

      $activity = array();
      foreach ($fields as $fld) {
        if (empty($dao->$fld)) {
          $activity[$fld] = NULL;
        }
        else {
          $activity[$fld] = $dao->$fld;
        }
      }

      // append activity value
      $this->appendValue($dao->id, 'civicrm_activity', $activity);
    }
    $dao->free();
  }

  /**
   * @param $id
   * @param $name
   * @param $value
   */
  function appendValue($id, $name, $value) {
    if (empty($value)) {
      return;
    }

    if (!isset($this->_values[$name])) {
      $this->_values[$name] = array();
      $this->_values[$name][] = array_keys($value);
    }
    $this->_values[$name][] = array_values($value);
  }

  /**
   * @param $daoName
   * @param bool $onlyKeys
   *
   * @return array
   */
  function dbFields($daoName, $onlyKeys = FALSE) {
    static $_fieldsRetrieved = array();

    if (!isset($_fieldsRetrieved[$daoName])) {
      $_fieldsRetrieved[$daoName] = array();
      $daoFile = str_replace('_',
        DIRECTORY_SEPARATOR,
        $daoName
      ) . '.php';
      include_once ($daoFile);

      $daoFields = & $daoName::fields();

      foreach ($daoFields as $key => & $value) {
        $_fieldsRetrieved[$daoName][$value['name']] = array(
          'uniqueName' => $key,
          'type' => $value['type'],
          'title' => CRM_Utils_Array::value('title', $value, NULL),
        );
      }
    }

    if ($onlyKeys) {
      return array_keys($_fieldsRetrieved[$daoName]);
    }
    else {
      return $_fieldsRetrieved[$daoName];
    }
  }

  /**
   * @param $contactIDs
   * @param $additionalContacts
   */
  function addAdditionalContacts($contactIDs, &$additionalContacts) {
    if (!$this->_discoverContacts) {
      return;
    }

    foreach ($contactIDs as $cid) {
      if ($cid &&
        !isset($this->_allContactIDs[$cid]) &&
        !isset($additionalContacts[$cid])
      ) {
        $additionalContacts[$cid] = $cid;
      }
    }
  }

  /**
   * @param $contactIDs
   */
  function export(&$contactIDs) {
    $chunks = & $this->splitContactIDs($contactIDs);

    $additionalContactIDs = array();

    foreach ($chunks as $chunk) {
      $this->getValues($chunk, $additionalContactIDs);
    }

    if (!empty($additionalContactIDs)) {
      $this->_allContactIDs = $this->_allContactIDs + $additionalContactIDs;
      $this->export($additionalContactIDs);
    }
  }

  /**
   * @param $fileName
   * @param null $lastExportTime
   * @param bool $discoverContacts
   */
  function run($fileName,
               $lastExportTime = NULL,
               $discoverContacts = FALSE
  ) {
    $this->_discoverContacts = $discoverContacts;

    if (!$lastExportTime) {
      $sql = "
SELECT id
FROM   civicrm_contact
";
    }
    else {
      $sql = "(
SELECT DISTINCT entity_id
FROM   civicrm_log
WHERE  entity_table = 'civicrm_contact'
AND    modified_date >= $lastExportTime
) UNION (
SELECT DISTINCT contact_id
FROM   civicrm_subscription_history
WHERE  date >= $lastExportTime
)
";
    }


    $dao = & CRM_Core_DAO::executeQuery($sql);

    $contactIDs = array();
    while ($dao->fetch()) {
      $contactIDs[$dao->id] = $dao->id;
    }

    $this->_allContactIDs = $contactIDs;
    $this->_values = array();

    $this->metaData();

    $this->export($contactIDs);

    $json = json_encode($this->_values, JSON_NUMERIC_CHECK);
    file_put_contents($fileName,
      $json
    );

    // print_r( json_decode( $json ) );
  }
}

