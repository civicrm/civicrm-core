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
class CRM_Utils_Migrate_ImportJSON {

  protected $_lookupCache;

  protected $_saveMapping;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->_lookupCache = [];
    $this->_saveMapping = [];
  }

  /**
   * Run import.
   *
   * @param string $file
   */
  public function run($file) {
    $json = file_get_contents($file);

    $decodedContacts = json_decode($json);

    // migrate contact data
    $this->contact($decodedContacts->civicrm_contact);
    $this->email($decodedContacts->civicrm_email);
    $this->phone($decodedContacts->civicrm_phone);
    $this->address($decodedContacts->civicrm_address);
    $this->note($decodedContacts->civicrm_note);
    $this->relationship($decodedContacts->civicrm_relationship);
    $this->activity($decodedContacts->civicrm_activity,
      $decodedContacts->civicrm_activity_contact
    );
    $this->group($decodedContacts->civicrm_group,
      $decodedContacts->civicrm_group_contact
    );
    $this->tag($decodedContacts->civicrm_tag,
      $decodedContacts->civicrm_entity_tag
    );

    // clean up all caches etc
    CRM_Core_Config::clearDBCache();
  }

  /**
   * @param $contact
   */
  public function contact(&$contact) {
    $this->restore($contact,
      'CRM_Contact_DAO_Contact',
      ['id' => 'civicrm_contact'],
      ['birth_date', 'deceased_date', 'created_date', 'modified_date']
    );
  }

  /**
   * @param $email
   */
  public function email(&$email) {
    $this->restore($email,
      'CRM_Core_DAO_Email',
      ['contact_id' => 'civicrm_contact']
    );
  }

  /**
   * @param $phone
   */
  public function phone(&$phone) {
    $this->restore($phone,
      'CRM_Core_DAO_Phone',
      ['contact_id' => 'civicrm_contact']
    );
  }

  /**
   * @param $address
   */
  public function address(&$address) {
    $this->restore($address,
      'CRM_Core_DAO_Address',
      ['contact_id' => 'civicrm_contact']
    );
  }

  /**
   * @param $note
   */
  public function note(&$note) {
    $this->restore($note,
      'CRM_Core_DAO_Note',
      ['contact_id' => 'civicrm_contact'],
      ['modified_date']
    );
  }

  /**
   * @param $relationship
   */
  public function relationship(&$relationship) {
    $this->restore($relationship,
      'CRM_Contact_DAO_Relationship',
      [
        'contact_id_a' => 'civicrm_contact',
        'contact_id_b' => 'civicrm_contact',
      ]
    );
  }

  /**
   * @param $activity
   * @param $activityContacts
   */
  public function activity($activity, $activityContacts) {
    $this->restore($activity,
      'CRM_Activity_DAO_Activity',
      NULL,
      ['activity_date_time']
    );

    $this->restore($activityContacts,
      'CRM_Activity_DAO_ActivityContact',
      [
        'contact_id' => 'civicrm_contact',
        'activity_id' => 'civicrm_activity',
      ]
    );
  }

  /**
   * @param $group
   * @param $groupContact
   */
  public function group($group, $groupContact) {
    $this->restore($group,
      'CRM_Contact_DAO_Group',
      NULL,
      ['cache_date', 'refresh_date']
    );

    $this->restore($groupContact,
      'CRM_Contact_DAO_GroupContact',
      [
        'group_id' => 'civicrm_group',
        'contact_id' => 'civicrm_contact',
      ]
    );
  }

  /**
   * @param $tag
   * @param $entityTag
   */
  public function tag($tag, $entityTag) {
    $this->restore($tag,
      'CRM_Core_DAO_Tag',
      [
        'created_id' => 'civicrm_contact',
        'parent_id' => 'civicrm_tag',
      ]
    );

    $this->restore($entityTag,
      'CRM_Core_DAO_EntityTag',
      [
        'entity_id' => 'civicrm_contact',
        'tag_id' => 'civicrm_tag',
      ]
    );
  }

  /**
   * @param $chunk
   * @param string $daoName
   * @param array|null $lookUpMapping
   * @param array|null $dateFields
   */
  public function restore(&$chunk, $daoName, $lookUpMapping = NULL, $dateFields = NULL) {
    $object = new $daoName();
    $tableName = $object->__table;

    if (is_array($lookUpMapping)) {
      $lookUpMapping['id'] = $tableName;
    }
    else {
      $lookUpMapping = ['id' => $tableName];
    }

    foreach ($lookUpMapping as $columnName => $tableName) {
      $this->populateCache($tableName);
    }

    $saveMapping = FALSE;
    $columns = $chunk[0];
    foreach ($chunk as $key => $value) {
      if ($key) {
        $object = new $daoName();
        foreach ($columns as $k => $column) {
          if ($column == 'id') {
            $childID = $value[$k];
            $masterID = CRM_Utils_Array::value($value[$k],
              $this->_lookupCache[$tableName],
              NULL
            );
            if ($masterID) {
              $object->id = $masterID;
            }
          }
          else {
            if (array_key_exists($column, $lookUpMapping)) {
              $object->$column = $this->_lookupCache[$lookUpMapping[$column]][$value[$k]];
            }
            elseif (!empty($dateFields) && in_array($column, $dateFields)) {
              $object->$column = CRM_Utils_Date::isoToMysql($value[$k]);
            }
            else {
              $object->$column = $value[$k];
            }
          }
        }

        $object->save();
        if (!$masterID) {
          $this->_lookupCache[$tableName][$childID] = $object->id;
          $this->_saveMapping[$tableName] = TRUE;
        }
      }
    }
  }

  public function saveCache() {
    $sql = "INSERT INTO civicrm_migration_mapping (master_id, slave_id, entity_table ) VALUES ";

    foreach ($this->_lookupCache as $tableName => & $values) {
      if (!$this->_saveMapping[$tableName]) {
        continue;
      }

      $mapValues = [];
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_migration_mapping where entity_table = '$tableName'");
      foreach ($values as $childID => $masterID) {
        $mapValues[] = "($masterID,$childID,'$tableName' )";
      }
      $insertSQL = $sql . implode(",\n", $mapValues);
      CRM_Core_DAO::executeQuery($insertSQL);
    }
  }

  /**
   * @param string $tableName
   */
  public function populateCache($tableName) {
    if (isset($this->_lookupCache[$tableName])) {
      return;
    }

    $this->_lookupCache[$tableName] = [];
    $this->_saveMapping[$tableName] = FALSE;

    $query = "SELECT master_id, slave_id
FROM civicrm_migration_mapping
WHERE entity_table = '{$tableName}'
";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $this->_lookupCache[$dao->slave_id] = $dao->master_id;
    }
  }

}
