<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This code is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
  $chunks = [];
  $current = 0;
  $chunks[$current] = [];
  $count = 0;

  foreach ($contactIDs as $k => $v) {
    $chunks[$current][$k] = $v;
    $count++;

    if ($count == CHUNK_SIZE) {
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
 * Given a set of contact IDs get the values
 * @param $contactIDs
 * @param $values
 * @param $allContactIDs
 * @param $addditionalContactIDs
 * @return array
 */
function getValues(&$contactIDs, &$values, &$allContactIDs, &$addditionalContactIDs) {
  $values = [];

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
 * @param string|null $additionalWhereCond
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
    $contact = [];
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
}

/**
 * @param $contactIDs
 * @param $values
 */
function getContactInfo(&$contactIDs, &$values) {
  $fields = [
    'id' => NULL,
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
  ];
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
    $note = [
      'id' => $dao->id,
      'contact_id' => $dao->contact_id,
      'subject' => empty($dao->subject) ? NULL : $dao->subject,
      'note' => empty($dao->note) ? NULL : $dao->note,
    ];

    appendValue($values, $dao->id, 'note', $note);
  }
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
    $phone = [
      'id' => $dao->id,
      'contact_id' => $dao->contact_id,
      'location_type' => empty($dao->location_type) ? NULL : $dao->location_type,
      'phone' => $dao->phone,
      'phone_type' => empty($dao->phone_type) ? NULL : $dao->phone_type,
    ];

    appendValue($values, $dao->id, 'phone', $phone);
  }
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
    $email = [
      'id' => $dao->id,
      'contact_id' => $dao->contact_id,
      'location_type' => empty($dao->location_type) ? NULL : $dao->location_type,
      'email' => $dao->email,
    ];
    appendValue($values, $dao->id, 'email', $email);
  }
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

  $fields = [
    'id',
    'contact_id',
    'location_type',
    'street_address',
    'supplemental_address_1',
    'supplemental_address_2',
    'supplemental_address_3',
    'city',
    'postal_code',
    'state',
    'country',
  ];
  $dao = &CRM_Core_DAO::executeQuery($sql);
  while ($dao->fetch()) {
    $address = [];
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
}

/**
 * @param $contactIDs
 * @param $values
 * @param $allContactIDs
 * @param $additionalContacts
 */
function getRelationshipInfo(&$contactIDs, &$values, &$allContactIDs, &$additionalContacts) {
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

  $relationshipFields = getDBFields('CRM_Contact_DAO_Relationship');
  $fields = array_keys($relationshipFields);
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
    appendValue($values, $dao->id, 'relationship', $relationship);

    addAdditionalContacts([
      $dao->contact_id_a,
      $dao->contact_id_b,
    ],
      $allContactIDs, $additionalContacts
    );
  }
}

/**
 * @param $contactIDs
 * @param $values
 * @param $allContactIDs
 * @param $additionalContacts
 */
function getActivityInfo(&$contactIDs, &$values, &$allContactIDs, &$additionalContacts) {
  static $_activitiesHandled = [];

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

  $activityIDs = [];
  $dao = &CRM_Core_DAO::executeQuery($sql);
  while ($dao->fetch()) {
    if (isset($_activitiesHandled[$dao->id])) {
      continue;
    }
    $_activitiesHandled[$dao->id] = $dao->id;
    $activityIDs[] = $dao->id;

    $activity = [];
    foreach ($fields as $fld) {
      if (empty($dao->$fld)) {
        $activity[$fld] = NULL;
      }
      else {
        $activity[$fld] = $dao->$fld;
      }
    }

    appendValue($values, $dao->id, 'activity', $activity);
    addAdditionalContacts([$dao->source_contact_id],
      $allContactIDs, $additionalContacts
    );
  }

  if (empty($activityIDs)) {
    return;
  }

  $activityIDString = implode(",", $activityIDs);

  // now get all assignee contact ids and target contact ids for this activity
  $sql = "SELECT * FROM civicrm_activity_assignment WHERE activity_id IN ($activityIDString)";
  $aaDAO = &CRM_Core_DAO::executeQuery($sql);
  $activityContacts = [];
  while ($aaDAO->fetch()) {
    $activityAssignee = [
      'id' => $aaDAO->id,
      'assignee_contact_id' => $aaDAO->assignee_contact_id,
      'activity_id' => $aaDAO->activity_id,
    ];
    appendValue($values, $aaDAO->id, 'activity_assignment', $activityAssignee);
    $activityContacts[] = $aaDAO->assignee_contact_id;
  }

  $sql = "SELECT * FROM civicrm_activity_target WHERE activity_id IN ($activityIDString)";
  $atDAO = &CRM_Core_DAO::executeQuery($sql);
  while ($atDAO->fetch()) {
    $activityTarget = [
      'id' => $atDAO->id,
      'target_contact_id' => $atDAO->target_contact_id,
      'activity_id' => $atDAO->activity_id,
    ];
    appendValue($values, $atDAO->id, 'activity_target', $activityTarget);
    $activityContacts[] = $atDAO->target_contact_id;
  }

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
    $values[$name] = [];
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
  static $_fieldsRetrieved = [];

  if (!isset($_fieldsRetrieved[$daoName])) {
    $_fieldsRetrieved[$daoName] = [];
    $daoFile = str_replace('_',
        DIRECTORY_SEPARATOR,
        $daoName
      ) . '.php';
    include_once($daoFile);

    $daoFields = &$daoName::fields();
    require_once 'CRM/Utils/Array.php';

    foreach ($daoFields as $key => & $value) {
      $_fieldsRetrieved[$daoName][$value['name']] = [
        'uniqueName' => $key,
        'type' => $value['type'],
        'title' => $value['title'] ?? NULL,
      ];
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

  $additionalContactIDs = [];

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


$contactIDs = [];
while ($dao->fetch()) {
  $contactIDs[$dao->id] = $dao->id;
}

$values = [];
run($values, $contactIDs, $contactIDs);

$json = json_encode($values);
echo $json;
// print_r( json_decode( $json ) );

