<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 * Create a xml file for a set of contact ID's in a format digestible
 * by our Sync scripts
 */

require_once '../../../civicrm.settings.php';
require_once 'CRM/Core/Config.php';

define('CHUNK_SIZE', 128);

/**
 * Split a large array of contactIDs into more manageable smaller chunks
 * @param $contactIDs
 * @return array
 */
function &splitContactIDs(&$contactIDs) {
  // contactIDs could be a real large array, so we split it up into
  // smaller chunks and then general xml for each chunk
  $chunks           = array();
  $current          = 0;
  $chunks[$current] = array();
  $count            = 0;

  foreach ($contactIDs as $k => $v) {
    $chunks[$current][$k] = $v;
    $count++;

    if ($count == CHUNK_SIZE) {
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
 * @param $contactIDs
 * @param $values
 * @param $allContactIDs
 * @param $addditionalContactIDs
 * @return array
 */
function getValues(&$contactIDs, &$values, &$allContactIDs, &$addditionalContactIDs) {
  $values = array();

  getContactInfo($contactIDs, $values);
  getAddressInfo($contactIDs, $values);
  getPhoneInfo($contactIDs, $values);
  getEmailInfo($contactIDs, $values);
  getNoteInfo($contactIDs, $values);

  getRelationshipInfo($contactIDs, $values, $allContactIDs, $addditionalContactIDs);

  getActivityInfo($contactIDs, $values, $allContactIDs, $addditionalContactIDs);

  // got to do groups, tags

  // got to do meta data

  return $values;
}

/**
 * @param $contactIDs
 * @param $values
 * @param $tableName
 * @param $fields
 * @param $whereField
 * @param null $additionalWhereCond
 * @param bool $flat
 */
function getTableInfo(&$contactIDs, &$values, $tableName, &$fields,
  $whereField, $additionalWhereCond = NULL,
  $flat = FALSE
) {
  $selectString = implode(',', array_keys($fields));
  $idString = implode(',', $contactIDs);

  $sql = "
SELECT $selectString, $whereField as contact_id
  FROM $tableName
 WHERE $whereField IN ( $idString )
";

  if ($additionalWhereCond) {
    $sql .= " AND $additionalWhereCond";
  }

  $dao = &CRM_Core_DAO::executeQuery($sql);
  while ($dao->fetch()) {
    $contact = array();
    foreach ($fields as $fld => $name) {
      $name = $name ? $name : $fld;
      if (empty($dao->$fld)) {
        $contact[$name] = NULL;
      }
      else {
        $contact[$name] = $dao->$fld;
      }
    }
    appendValue($values, $dao->contact_id, 'contact', $contact, $flat);
  }
  $dao->free();
}

/**
 * @param $contactIDs
 * @param $values
 */
function getContactInfo(&$contactIDs, &$values) {
  $fields = array('id' => NULL,
    'sort_name' => NULL,
    'display_name' => NULL,
    'contact_type' => NULL,
    'legal_identifier' => NULL,
    'external_identifier' => NULL,
    'first_name' => NULL,
    'last_name' => NULL,
    'middle_name' => NULL,
    'household_name' => NULL,
    'organization_name' => NULL,
    'legal_name' => NULL,
    'job_title' => NULL,
  );
  getTableInfo($contactIDs, $values, 'civicrm_contact', $fields, 'id', NULL, TRUE);
}

/**
 * @param $contactIDs
 * @param $values
 */
function getNoteInfo(&$contactIDs, &$values) {
  $ids = implode(',', $contactIDs);

  $sql = "
SELECT
      id,
      entity_id as contact_id,
      note as note, subject as subject
FROM  civicrm_note
WHERE entity_id IN ( $ids )
AND   entity_table = 'civicrm_contact'
";

  $dao = &CRM_Core_DAO::executeQuery($sql);
  while ($dao->fetch()) {
    $note = array('id' => $dao->id,
      'contact_id' => $dao->contact_id,
      'subject' => empty($dao->subject) ? NULL : $dao->subject,
      'note' => empty($dao->note) ? NULL : $dao->note,
    );

    appendValue($values, $dao->id, 'note', $note);
  }
  $dao->free();
}

/**
 * @param $contactIDs
 * @param $values
 */
function getPhoneInfo(&$contactIDs, &$values) {
  $ids = implode(',', $contactIDs);

  $sql = "
SELECT
  p.id as id,
  c.id as contact_id,
  l.name as location_type,
  p.phone as phone,
  v.label as phone_type
FROM      civicrm_contact c
INNER JOIN civicrm_phone          p  ON p.contact_id        = c.id
LEFT  JOIN civicrm_location_type  l  ON p.location_type_id  = l.id
LEFT  JOIN civicrm_option_group   g  ON g.name = 'phone_type'
LEFT  JOIN civicrm_option_value   v  ON v.option_group_id = g.id AND p.phone_type_id = v.value
WHERE      c.id IN ( $ids )
AND        p.phone IS NOT NULL
";

  $dao = &CRM_Core_DAO::executeQuery($sql);
  while ($dao->fetch()) {
    $phone = array('id' => $dao->id,
      'contact_id' => $dao->contact_id,
      'location_type' => empty($dao->location_type) ? NULL : $dao->location_type,
      'phone' => $dao->phone,
      'phone_type' => empty($dao->phone_type) ? NULL : $dao->phone_type,
    );

    appendValue($values, $dao->id, 'phone', $phone);
  }
  $dao->free();
}

/**
 * @param $contactIDs
 * @param $values
 */
function getEmailInfo(&$contactIDs, &$values) {
  $ids = implode(',', $contactIDs);

  $sql = "
SELECT
  e.id as id,
  c.id as contact_id,
  l.name as location_type,
  e.email as email
FROM      civicrm_contact c
INNER JOIN civicrm_email          e  ON e.contact_id        = c.id
LEFT  JOIN civicrm_location_type  l  ON e.location_type_id  = l.id
WHERE      c.id IN ( $ids )
AND        e.email IS NOT NULL
";

  $dao = &CRM_Core_DAO::executeQuery($sql);
  while ($dao->fetch()) {
    $email = array('id' => $dao->id,
      'contact_id' => $dao->contact_id,
      'location_type' => empty($dao->location_type) ? NULL : $dao->location_type,
      'email' => $dao->email,
    );
    appendValue($values, $dao->id, 'email', $email);
  }
  $dao->free();
}

/**
 * @param $contactIDs
 * @param $values
 */
function getAddressInfo(&$contactIDs, &$values) {
  $ids = implode(',', $contactIDs);

  $sql = "
SELECT     a.id as id,
           c.id as contact_id, l.name as location_type,
           a.street_address, a.supplemental_address_1, a.supplemental_address_2,
           a.supplemental_address_3,
           a.city, a.postal_code,
           s.name as state, co.name as country
FROM       civicrm_contact c
INNER JOIN civicrm_address        a  ON a.contact_id        = c.id
LEFT  JOIN civicrm_location_type  l  ON a.location_type_id  = l.id
LEFT  JOIN civicrm_state_province s  ON a.state_province_id = s.id
LEFT  JOIN civicrm_country        co ON a.country_id        = co.id
WHERE c.id IN ( $ids )
";

  $fields = array('id', 'contact_id',
    'location_type', 'street_address', 'supplemental_address_1',
    'supplemental_address_2', 'supplemental_address_3', 'city', 'postal_code',
    'state', 'country',
  );
  $dao = &CRM_Core_DAO::executeQuery($sql);
  while ($dao->fetch()) {
    $address = array();
    foreach ($fields as $fld) {
      if (empty($dao->$fld)) {
        $address[$fld] = NULL;
      }
      else {
        $address[$fld] = $dao->$fld;
      }
    }
    appendValue($values, $dao->id, 'address', $address);
  }
  $dao->free();
}

/**
 * @param $contactIDs
 * @param $values
 * @param $allContactIDs
 * @param $additionalContacts
 */
function getRelationshipInfo(&$contactIDs, &$values, &$allContactIDs, &$additionalContacts) {
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

  $relationshipFields = getDBFields('CRM_Contact_DAO_Relationship');
  $fields             = array_keys($relationshipFields);
  $dao                = &CRM_Core_DAO::executeQuery($sql);
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
    appendValue($values, $dao->id, 'relationship', $relationship);

    addAdditionalContacts(array($dao->contact_id_a,
        $dao->contact_id_b,
      ),
      $allContactIDs, $additionalContacts
    );
  }
  $dao->free();
}

/**
 * @param $contactIDs
 * @param $values
 * @param $allContactIDs
 * @param $additionalContacts
 */
function getActivityInfo(&$contactIDs, &$values, &$allContactIDs, &$additionalContacts) {
  static $_activitiesHandled = array();

  $ids = implode(',', $contactIDs);

  $sql = "(
  SELECT     a.*
  FROM       civicrm_activity a
  INNER JOIN civicrm_activity_assignment aa ON aa.activity_id = a.id
  WHERE      aa.assignee_contact_id IN ( $ids )
    AND      ( a.activity_type_id != 3 AND a.activity_type_id != 20 )
) UNION (
  SELECT     a.*
  FROM       civicrm_activity a
  INNER JOIN civicrm_activity_target at ON at.activity_id = a.id
  WHERE      at.target_contact_id IN ( $ids )
    AND      ( a.activity_type_id != 3 AND a.activity_type_id != 20 )
)
";

  $activityFields = &getDBFields('CRM_Activity_DAO_Activity');
  $fields = array_keys($activityFields);

  $activityIDs = array();
  $dao = &CRM_Core_DAO::executeQuery($sql);
  while ($dao->fetch()) {
    if (isset($_activitiesHandled[$dao->id])) {
      continue;
    }
    $_activitiesHandled[$dao->id] = $dao->id;
    $activityIDs[] = $dao->id;

    $activity = array();
    foreach ($fields as $fld) {
      if (empty($dao->$fld)) {
        $activity[$fld] = NULL;
      }
      else {
        $activity[$fld] = $dao->$fld;
      }
    }

    appendValue($values, $dao->id, 'activity', $activity);
    addAdditionalContacts(array($dao->source_contact_id),
      $allContactIDs, $additionalContacts
    );
  }
  $dao->free();

  if (empty($activityIDs)) {
    return;
  }

  $activityIDString = implode(",", $activityIDs);

  // now get all assignee contact ids and target contact ids for this activity
  $sql              = "SELECT * FROM civicrm_activity_assignment WHERE activity_id IN ($activityIDString)";
  $aaDAO            = &CRM_Core_DAO::executeQuery($sql);
  $activityContacts = array();
  while ($aaDAO->fetch()) {
    $activityAssignee = array('id' => $aaDAO->id,
      'assignee_contact_id' => $aaDAO->assignee_contact_id,
      'activity_id' => $aaDAO->activity_id,
    );
    appendValue($values, $aaDAO->id, 'activity_assignment', $activityAssignee);
    $activityContacts[] = $aaDAO->assignee_contact_id;
  }
  $aaDAO->free();

  $sql = "SELECT * FROM civicrm_activity_target WHERE activity_id IN ($activityIDString)";
  $atDAO = &CRM_Core_DAO::executeQuery($sql);
  while ($atDAO->fetch()) {
    $activityTarget = array('id' => $atDAO->id,
      'target_contact_id' => $atDAO->target_contact_id,
      'activity_id' => $atDAO->activity_id,
    );
    appendValue($values, $atDAO->id, 'activity_target', $activityTarget);
    $activityContacts[] = $atDAO->target_contact_id;
  }
  $atDAO->free();

  addAdditionalContacts($activityContacts, $allContactIDs, $additionalContacts);
}

/**
 * @param $values
 * @param $id
 * @param $name
 * @param $value
 * @param bool $ignored
 */
function appendValue(&$values, $id, $name, $value, $ignored = FALSE) {
  if (empty($value)) {
    return;
  }

  if (!isset($values[$name])) {
    $values[$name] = array();
    $values[$name][] = array_keys($value);
  }
  $values[$name][] = array_values($value);
}

/**
 * @param string $daoName
 *
 * @return mixed
 */
function getDBFields($daoName) {
  static $_fieldsRetrieved = array();

  if (!isset($_fieldsRetrieved[$daoName])) {
    $_fieldsRetrieved[$daoName] = array();
    $daoFile = str_replace('_',
      DIRECTORY_SEPARATOR,
      $daoName
    ) . '.php';
    include_once ($daoFile);

    $daoFields = &$daoName::fields();
    require_once 'CRM/Utils/Array.php';

    foreach ($daoFields as $key => & $value) {
      $_fieldsRetrieved[$daoName][$value['name']] = array('uniqueName' => $key,
        'type' => $value['type'],
        'title' => CRM_Utils_Array::value('title', $value, NULL),
      );
    }
  }
  return $_fieldsRetrieved[$daoName];
}

/**
 * @param $contactIDs
 * @param $allContactIDs
 * @param $additionalContacts
 */
function addAdditionalContacts($contactIDs, &$allContactIDs, &$additionalContacts) {
  foreach ($contactIDs as $cid) {
    if ($cid &&
      !isset($allContactIDs[$cid]) &&
      !isset($additionalContacts[$cid])
    ) {
      $additionalContacts[$cid] = $cid;
    }
  }
}

/**
 * @param $values
 * @param $contactIDs
 * @param $allContactIDs
 */
function run(&$values, &$contactIDs, &$allContactIDs) {
  $chunks = &splitContactIDs($contactIDs);

  $additionalContactIDs = array();

  foreach ($chunks as $chunk) {
    getValues($chunk, $values, $allContactIDs, $additionalContactIDs);
  }

  if (!empty($additionalContactIDs)) {
    $allContactIDs = $allContactIDs + $additionalContactIDs;
    run($values, $additionalContactIDs, $allContactIDs);
  }
}

$config = CRM_Core_Config::singleton();
$config->userFramework = 'Soap';
$config->userFrameworkClass = 'CRM_Utils_System_Soap';
$config->userHookClass = 'CRM_Utils_Hook_Soap';

$sql = "
SELECT id
FROM civicrm_contact
LIMIT 10
";
$dao = &CRM_Core_DAO::executeQuery($sql);


$contactIDs = array();
while ($dao->fetch()) {
  $contactIDs[$dao->id] = $dao->id;
}

$values = array();
run($values, $contactIDs, $contactIDs);

$json = json_encode($values);
echo $json;
// print_r( json_decode( $json ) );

