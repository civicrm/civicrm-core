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
class CRM_Utils_Migrate_ExportJSON {
  const CHUNK_SIZE = 128;

  protected $_contactIDs;

  protected $_allContactIDs;

  protected $_values;

  protected $_discoverContacts = FALSE;

  protected $_renameGroups = 1;

  protected $_renameTags = 1;

  protected $_sitePrefix = 'Site 1';

  /**
   * @param array $params
   */
  public function __construct(&$params) {
    foreach ($params as $name => $value) {
      $varName = '_' . $name;
      $this->$varName = $value;
    }
  }

  /**
   * Split a large array of contactIDs into more manageable smaller chunks.
   *
   * @param array $contactIDs
   *
   * @return array
   */
  public function &splitContactIDs(&$contactIDs) {
    // contactIDs could be a real large array, so we split it up into
    // smaller chunks and then general xml for each chunk
    $chunks = [];
    $current = 0;
    $chunks[$current] = [];
    $count = 0;

    foreach ($contactIDs as $k => $v) {
      $chunks[$current][$k] = $v;
      $count++;

      if ($count == self::CHUNK_SIZE) {
        $current++;
        $chunks[$current] = [];
        $count = 0;
      }
    }

    if (empty($chunks[$current])) {
      unset($chunks[$current]);
    }

    return $chunks;
  }

  /**
   * Given a set of contact IDs get the values.
   *
   * @param array $contactIDs
   * @param array $additionalContactIDs
   */
  public function getValues(&$contactIDs, &$additionalContactIDs) {

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

  public function metaData() {
    $optionGroupVars = [
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
    ];
    $this->optionGroup($optionGroupVars);

    $auxilaryTables = [
      'civicrm_location_type' => 'CRM_Core_DAO_LocationType',
      'civicrm_relationship_type' => 'CRM_Contact_DAO_RelationshipType',
    ];
    $this->auxTable($auxilaryTables);
  }

  /**
   * @param $tables
   */
  public function auxTable($tables) {
    foreach ($tables as $tableName => $daoName) {
      $fields = &$this->dbFields($daoName, TRUE);

      $sql = "SELECT * from $tableName";
      $this->sql($sql, $tableName, $fields);
    }
  }

  /**
   * @param $optionGroupVars
   */
  public function optionGroup($optionGroupVars) {
    $names = array_values($optionGroupVars);
    $str = [];
    foreach ($names as $name) {
      $str[] = "'$name'";
    }
    $nameString = implode(",", $str);

    $sql = "
SELECT *
FROM   civicrm_option_group
WHERE  name IN ( $nameString )
";
    $fields = &$this->dbFields('CRM_Core_DAO_OptionGroup', TRUE);
    $this->sql($sql, 'civicrm_option_group', $fields);

    $sql = "
SELECT     v.*
FROM       civicrm_option_value v
INNER JOIN civicrm_option_group g ON v.option_group_id = g.id
WHERE      g.name IN ( $nameString )
";
    $fields = &$this->dbFields('CRM_Core_DAO_OptionValue', TRUE);
    $this->sql($sql, 'civicrm_option_value', $fields);
  }

  /**
   * @param $ids
   * @param string $tableName
   * @param $fields
   * @param $whereField
   * @param null $additionalWhereCond
   */
  public function table(
    &$ids,
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
   * @param string $tableName
   * @param $fields
   */
  public function sql($sql, $tableName, &$fields) {
    $dao = &CRM_Core_DAO::executeQuery($sql);

    while ($dao->fetch()) {
      $value = [];
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
  }

  /**
   * @param $contactIDs
   */
  public function contact(&$contactIDs) {
    $fields = &$this->dbFields('CRM_Contact_DAO_Contact', TRUE);
    $this->table($contactIDs, 'civicrm_contact', $fields, 'id', NULL);
  }

  /**
   * @param $contactIDs
   */
  public function note(&$contactIDs) {
    $fields = &$this->dbFields('CRM_Core_DAO_Note', TRUE);
    $this->table($contactIDs, 'civicrm_note', $fields, 'entity_id', "entity_table = 'civicrm_contact'");
  }

  /**
   * @param $contactIDs
   */
  public function phone(&$contactIDs) {
    $fields = &$this->dbFields('CRM_Core_DAO_Phone', TRUE);
    $this->table($contactIDs, 'civicrm_phone', $fields, 'contact_id', NULL);
  }

  /**
   * @param $contactIDs
   */
  public function email(&$contactIDs) {
    $fields = &$this->dbFields('CRM_Core_DAO_Email', TRUE);
    $this->table($contactIDs, 'civicrm_email', $fields, 'contact_id', NULL);
  }

  /**
   * @param $contactIDs
   */
  public function im(&$contactIDs) {
    $fields = &$this->dbFields('CRM_Core_DAO_IM', TRUE);
    $this->table($contactIDs, 'civicrm_im', $fields, 'contact_id', NULL);
  }

  /**
   * @param $contactIDs
   */
  public function website(&$contactIDs) {
    $fields = &$this->dbFields('CRM_Core_DAO_Website', TRUE);
    $this->table($contactIDs, 'civicrm_website', $fields, 'contact_id', NULL);
  }

  /**
   * @param $contactIDs
   */
  public function address(&$contactIDs) {
    $fields = &$this->dbFields('CRM_Core_DAO_Email', TRUE);
    $this->table($contactIDs, 'civicrm_address', $fields, 'contact_id', NULL);
  }

  /**
   * @param $contactIDs
   */
  public function groupContact(&$contactIDs) {
    $fields = &$this->dbFields('CRM_Contact_DAO_GroupContact', TRUE);
    $this->table($contactIDs, 'civicrm_group_contact', $fields, 'contact_id', NULL);
  }

  /**
   * @todo support group inheritance
   *
   * Parent child group ids are encoded in a text string
   *
   * @param $contactIDs
   */
  public function group(&$contactIDs) {
    // handle groups only once
    static $_groupsHandled = [];

    $ids = implode(',', $contactIDs);

    $sql = "
SELECT DISTINCT group_id
FROM   civicrm_group_contact
WHERE  contact_id IN ( $ids )
";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $groupIDs = [];
    while ($dao->fetch()) {
      if (!isset($_groupsHandled[$dao->group_id])) {
        $groupIDs[] = $dao->group_id;
        $_groupsHandled[$dao->group_id] = 1;
      }
    }

    $fields = &$this->dbFields('CRM_Contact_DAO_Group', TRUE);
    $this->table($groupIDs, 'civicrm_group', $fields, 'id');

    $this->savedSearch($groupIDs);
  }

  /**
   * @todo support search builder and custom saved searches
   * @param $groupIDs
   */
  public function savedSearch(&$groupIDs) {
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

    $fields = &$this->dbFields('CRM_Contact_DAO_SavedSearch', TRUE);
    $this->sql($sql, 'civicrm_saved_search', $fields);
  }

  /**
   * @param $contactIDs
   */
  public function entityTag(&$contactIDs) {
    $fields = &$this->dbFields('CRM_Core_DAO_EntityTag', TRUE);
    $this->table($contactIDs, 'civicrm_entity_tag', $fields, 'entity_id', "entity_table = 'civicrm_contact'");
  }

  /**
   * @param $contactIDs
   */
  public function tag(&$contactIDs) {
    // handle tags only once
    static $_tagsHandled = [];

    $ids = implode(',', $contactIDs);

    $sql = "
SELECT DISTINCT tag_id
FROM   civicrm_entity_tag
WHERE  entity_id IN ( $ids )
AND    entity_table = 'civicrm_contact'
";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $tagIDs = [];
    while ($dao->fetch()) {
      if (!isset($_tagsHandled[$dao->tag_id])) {
        $tagIDs[] = $dao->tag_id;
        $_tagsHandled[$dao->tag_id] = 1;
      }
    }

    $fields = &$this->dbFields('CRM_Core_DAO_Tag', TRUE);
    $this->table($tagIDs, 'civicrm_tag', $fields, 'id');
  }

  /**
   * @param $contactIDs
   * @param $additionalContacts
   */
  public function relationship(&$contactIDs, &$additionalContacts) {
    // handle relationships only once
    static $_relationshipsHandled = [];

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
    $dao = &CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      if (isset($_relationshipsHandled[$dao->id])) {
        continue;
      }
      $_relationshipsHandled[$dao->id] = $dao->id;

      $relationship = [];
      foreach ($fields as $fld) {
        if (empty($dao->$fld)) {
          $relationship[$fld] = NULL;
        }
        else {
          $relationship[$fld] = $dao->$fld;
        }
      }
      $this->appendValue($dao->id, 'civicrm_relationship', $relationship);

      $this->addAdditionalContacts([
        $dao->contact_id_a,
        $dao->contact_id_b,
      ],
        $additionalContacts
      );
    }
  }

  /**
   * @param $contactIDs
   * @param $additionalContacts
   */
  public function activity(&$contactIDs, &$additionalContacts) {
    static $_activitiesHandled = [];
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
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

    $fields = &$this->dbFields('CRM_Activity_DAO_Activity', TRUE);

    $dao = &CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      // adding source, target and assignee contacts in additional contacts array
      $this->addAdditionalContacts([$dao->contact_id],
        $additionalContacts
      );

      // append values of activity contacts
      $activityContacts = [
        'id' => $dao->acID,
        'contact_id' => $dao->contact_id,
        'activity_id' => $dao->activity_id,
        'record_type_id' => $dao->record_type_id,
      ];
      $this->appendValue($dao->acID, 'civicrm_activity_contact', $activityContacts);

      if (isset($_activitiesHandled[$dao->id])) {
        continue;
      }
      $_activitiesHandled[$dao->id] = $dao->id;

      $activity = [];
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
  }

  /**
   * @param int $id
   * @param string $name
   * @param $value
   */
  public function appendValue($id, $name, $value) {
    if (empty($value)) {
      return;
    }

    if (!isset($this->_values[$name])) {
      $this->_values[$name] = [];
      $this->_values[$name][] = array_keys($value);
    }
    $this->_values[$name][] = array_values($value);
  }

  /**
   * @param string $daoName
   * @param bool $onlyKeys
   *
   * @return array
   */
  public function dbFields($daoName, $onlyKeys = FALSE) {
    static $_fieldsRetrieved = [];

    if (!isset($_fieldsRetrieved[$daoName])) {
      $_fieldsRetrieved[$daoName] = [];
      $daoFile = str_replace('_',
          DIRECTORY_SEPARATOR,
          $daoName
        ) . '.php';
      include_once $daoFile;

      $daoFields = &$daoName::fields();

      foreach ($daoFields as $key => & $value) {
        $_fieldsRetrieved[$daoName][$value['name']] = [
          'uniqueName' => $key,
          'type' => $value['type'],
          'title' => CRM_Utils_Array::value('title', $value, NULL),
        ];
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
  public function addAdditionalContacts($contactIDs, &$additionalContacts) {
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
  public function export(&$contactIDs) {
    $chunks = &$this->splitContactIDs($contactIDs);

    $additionalContactIDs = [];

    foreach ($chunks as $chunk) {
      $this->getValues($chunk, $additionalContactIDs);
    }

    if (!empty($additionalContactIDs)) {
      $this->_allContactIDs = $this->_allContactIDs + $additionalContactIDs;
      $this->export($additionalContactIDs);
    }
  }

  /**
   * @param string $fileName
   * @param null $lastExportTime
   * @param bool $discoverContacts
   */
  public function run(
    $fileName,
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

    $dao = &CRM_Core_DAO::executeQuery($sql);

    $contactIDs = [];
    while ($dao->fetch()) {
      $contactIDs[$dao->id] = $dao->id;
    }

    $this->_allContactIDs = $contactIDs;
    $this->_values = [];

    $this->metaData();

    $this->export($contactIDs);

    $json = json_encode($this->_values, JSON_NUMERIC_CHECK);
    file_put_contents($fileName,
      $json
    );

    // print_r( json_decode( $json ) );
  }

}
