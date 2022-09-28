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

require_once '../../civicrm.config.php';
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
 * Given an array of values, generate the XML in the Solr format
 * @param $values
 * @return string
 */
function &generateSolrXML($values) {
  $result = "<add>\n";
  foreach ($values as $cid => $tokens) {
    if (empty($tokens)) {
      continue;
    }

    $result .= <<<EOT
  <doc>
    <field name="id">$cid</field>\n
EOT;

    foreach ($tokens as $t) {
      $result .= <<<EOT
    <field name="$t[0]">$t[1]</field>\n
EOT;
    }

    $result .= "  </doc>\n";
  }
  $result .= "</add>\n";


  return $result;
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
  getLocationInfo($contactIDs, $values);

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

  $dao = CRM_Core_DAO::executeQuery($sql);
  while ($dao->fetch()) {
    foreach ($fields as $fld => $name) {
      if (empty($dao->$fld)) {
        continue;
      }
      if (!$name) {
        $name = $fld;
      }
      $values[$dao->contact_id][] = [$name, $dao->$fld];
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
    'source' => 'contact_source',
  ];
  getTableInfo($contactIDs, $values, 'civicrm_contact', $fields, 'id');

  $fields = [
    'first_name' => NULL,
    'last_name' => NULL,
    'middle_name' => NULL,
    'job_title' => NULL,
  ];
  getTableInfo($contactIDs, $values, 'civicrm_individual', $fields, 'contact_id');

  $fields = ['household_name' => NULL];
  getTableInfo($contactIDs, $values, 'civicrm_household', $fields, 'contact_id');

  $fields = [
    'organization_name' => NULL,
    'legal_name' => NULL,
    'sic_code' => NULL,
  ];
  getTableInfo($contactIDs, $values, 'civicrm_organization', $fields, 'contact_id');

  $fields = [
    'note' => 'note_body',
    'subject' => 'note_subject',
  ];
  getTableInfo($contactIDs, $values, 'civicrm_note', $fields, 'entity_id', "entity_table = 'civicrm_contact'");
}

/**
 * @param $contactIDs
 * @param $values
 */
function getLocationInfo(&$contactIDs, &$values) {
  $ids = implode(',', $contactIDs);

  $sql = "
SELECT
  l.entity_id as contact_id, l.name as location_name,
  a.street_address, a.supplemental_address_1, a.supplemental_address_2,
  a.supplemental_address_3,
  a.city, a.postal_code,
  co.name as county, s.name as state, c.name as country,
  e.email, p.phone, i.name as im
FROM
  civicrm_location l
LEFT JOIN civicrm_address        a  ON a.location_id       = l.id
LEFT JOIN civicrm_email          e  ON e.location_id       = l.id
LEFT JOIN civicrm_phone          p  ON p.location_id       = l.id
LEFT JOIN civicrm_im             i  ON i.location_id       = l.id
LEFT JOIN civicrm_state_province s  ON a.state_province_id = s.id
LEFT JOIN civicrm_country        c  ON a.country_id        = c.id
LEFT JOIN civicrm_county         co ON a.county_id         = co.id
WHERE l.entity_table = 'civicrm_contact'
  AND l.entity_id IN ( $ids )
";

  $fields = [
    'location_name',
    'street_address',
    'supplemental_address_1',
    'supplemental_address_2',
    'supplemental_address_3',
    'city',
    'postal_code',
    'county',
    'state',
    'country',
    'email',
    'phone',
    'im',
  ];
  $dao = CRM_Core_DAO::executeQuery($sql);
  while ($dao->fetch()) {
    foreach ($fields as $fld) {
      if (empty($dao->$fld)) {
        continue;
      }
      $values[$dao->contact_id][] = [$fld, $dao->$fld];
    }
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
    $xml = &generateSolrXML($values);
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
$dao = CRM_Core_DAO::executeQuery($sql);

$contactIDs = [];
while ($dao->fetch()) {
  $contactIDs[] = $dao->id;
}

run($contactIDs);

