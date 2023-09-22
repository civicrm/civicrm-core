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
 * by Solr
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

  foreach ($contactIDs as $cid) {
    $chunks[$current][] = $cid;
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
 * Given an array of values, generate the JSON in the Solr format
 * @param $values
 * @return string
 */
function &generateSolrJSON($values) {
  $result = "[";
  foreach ($values as $cid => $tokens) {
    if (empty($tokens)) {
      continue;
    }

    $result .= "\n  {\n    \"contact_id\" : \"$cid\",";

    foreach ($tokens as $n => $v) {
      if (is_array($v)) {
        $str = [];
        foreach ($v as $el) {
          $el = escapeJsonString($el);
          $str[] = "\"$el\"";
        }
        $string = implode(",", $str);
        $result .= "\n    \"{$n}\" : [$string],";
      }
      else {
        $v = escapeJsonString($v);
        $result .= "\n    \"{$n}\" : \"{$v}\",";
      }
    }

    // remove the last comma
    $result = rtrim($result, ",");

    $result .= "\n  },";
  }
  // remove the last comma
  $result = rtrim($result, ",");

  $result .= "\n]\n";


  return $result;
}

/**
 * @param $value
 *
 * @return mixed
 */
function escapeJsonString($value) {
  $escapers = ["\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c"];
  $replacements = ["\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b"];
  return str_replace($escapers, $replacements, $value);
}

/**
 * Given a set of contact IDs get the values
 * @param $contactIDs
 * @param $values
 * @return array
 */
function getValues(&$contactIDs, &$values) {
  $values = [];

  foreach ($contactIDs as $cid) {
    $values[$cid] = [];
  }

  getContactInfo($contactIDs, $values);
  getAddressInfo($contactIDs, $values);
  getPhoneInfo($contactIDs, $values);
  getEmailInfo($contactIDs, $values);
  getNoteInfo($contactIDs, $values);

  return $values;
}

/**
 * @param $contactIDs
 * @param $values
 * @param $tableName
 * @param $fields
 * @param $whereField
 * @param string|null $additionalWhereCond
 */
function getTableInfo(&$contactIDs, &$values, $tableName, &$fields, $whereField, $additionalWhereCond = NULL) {
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
    foreach ($fields as $fld => $name) {
      $name = $name ? $name : $fld;
      appendValue($values, $dao->contact_id, $name, $dao->$fld);
    }
  }
}

/**
 * @param $contactIDs
 * @param $values
 */
function getContactInfo(&$contactIDs, &$values) {
  $fields = [
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
  getTableInfo($contactIDs, $values, 'civicrm_contact', $fields, 'id');
}

/**
 * @param $contactIDs
 * @param $values
 */
function getNoteInfo(&$contactIDs, &$values) {
  $ids = implode(',', $contactIDs);

  $sql = "
SELECT
      entity_id as contact_id,
      note as note, subject as subject
FROM  civicrm_note
WHERE entity_id IN ( $ids )
AND   entity_table = 'civicrm_contact'
";

  $dao = &CRM_Core_DAO::executeQuery($sql);
  while ($dao->fetch()) {
    $note = empty($dao->subject) ? '' : "{$dao->subject}: ";
    $note .= empty($dao->note) ? '' : $dao->note;

    appendValue($values, $dao->contact_id, 'note', $note);
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
    $phone = '';

    if (!empty($dao->location_type)) {
      $phone = "{$dao->location_type}: ";
    }

    $phone .= $dao->phone;

    if (!empty($dao->phone_type)) {
      $phone .= " ({$dao->phone_type})";
    }

    appendValue($values, $dao->contact_id, 'phone', $phone);
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
    $email = '';

    if (!empty($dao->location_type)) {
      $email = "{$dao->location_type}: ";
    }

    $email .= $dao->email;
    appendValue($values, $dao->contact_id, 'email', $email);
  }
}

/**
 * @param $contactIDs
 * @param $values
 */
function getAddressInfo(&$contactIDs, &$values) {
  $ids = implode(',', $contactIDs);

  $sql = "
SELECT     c.id as contact_id, l.name as location_type,
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
    $address = '';
    foreach ($fields as $fld) {
      if (empty($dao->$fld)) {
        continue;
      }

      $address .= ($fld == 'location_type') ? "{$dao->$fld}: " : " {$dao->$fld},";
      appendValue($values, $dao->contact_id, $fld, $dao->$fld);
    }

    if (!empty($address)) {
      $address = rtrim($address, ",");
      appendValue($values, $dao->contact_id, 'address', $address);
    }
  }
}

/**
 * @param $values
 * @param $contactID
 * @param $name
 * @param $value
 */
function appendValue(&$values, $contactID, $name, $value) {
  if (empty($value)) {
    return;
  }

  if (!isset($values[$contactID][$name])) {
    $values[$contactID][$name] = $value;
  }
  else {
    if (!is_array($values[$contactID][$name])) {
      $save = $values[$contactID][$name];
      $values[$contactID][$name] = [];
      $values[$contactID][$name][] = $save;
    }
    $values[$contactID][$name][] = $value;
  }
}

/**
 * @param $contactIDs
 */
function run(&$contactIDs) {
  $chunks = &splitContactIDs($contactIDs);

  foreach ($chunks as $chunk) {
    $values = [];
    getValues($chunk, $values);
    $xml = &generateSolrJSON($values);
    echo $xml;
  }
}

$config = CRM_Core_Config::singleton();
$config->userFramework = 'Soap';
$config->userFrameworkClass = 'CRM_Utils_System_Soap';
$config->userHookClass = 'CRM_Utils_Hook_Soap';

$sql = <<<EOT
SELECT id
FROM civicrm_contact
EOT;
$dao = &CRM_Core_DAO::executeQuery($sql);


$contactIDs = [];
while ($dao->fetch()) {
  $contactIDs[] = $dao->id;
}

run($contactIDs);

