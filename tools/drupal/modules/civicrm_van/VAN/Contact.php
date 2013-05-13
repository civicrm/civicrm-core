<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */
class VAN_Contact {
  function findContact(&$person) {
    require_once 'CRM/Core/DAO.php';

    // first see if there is a VAN ID match
    $sql = "
SELECT entity_id
FROM   civicrm_value_van
WHERE  van_id = %1
";
    $params = array(1 => array($person['custom']['van_id'], 'Integer'));
    $cid = CRM_Core_DAO::singleValueQuery($sql, $params);
    if ($cid) {
      return array($cid);
    }

    // ok, how about we search on
    // first_name AND last_name AND street_address AND zip_code
    $sql = "
SELECT  contact_id
FROM    civicrm_contact c,
        civicrm_address a
WHERE   a.contact_id = c.id
AND     c.first_name     = %1
AND     c.last_name      = %2
AND     a.postal_code    = %3
AND     a.street_address = %4
";
    $params = array(1 => array($person['first_name'], 'String'),
      2 => array($person['last_name'], 'String'),
      3 => array($person['address'][0]['street_address'], 'String'),
      4 => array($person['address'][0]['postal_code'], 'String'),
    );
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    if ($dao->N == 1) {
      $dao->fetch();
      return array($dao->contact_id);
    }
    elseif ($dao->N > 1) {
      $cids = array();
      while ($dao->fetch()) {
        $cids[] = $dao->contact_id;
      }
      return $cids;
    }

    // ok did not find it
    // run a simpler query
    // first_name AND last_name AND street_address AND zip_code
    $sql = "
SELECT  contact_id
FROM    civicrm_contact c,
        civicrm_address a
WHERE   a.contact_id = c.id
AND     c.first_name     = %1
AND     c.last_name      = %2
AND     a.postal_code    = %3
";
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    if ($dao->N == 1) {
      $dao->fetch();
      return array($dao->contact_id);
    }
    elseif ($dao->N > 1) {
      $cids = array();
      while ($dao->fetch()) {
        $cids[] = $dao->contact_id;
      }
      return $cids;
    }

    return NULL;
  }

  function createOrUpdateContact(&$person, $cid = NULL) {
    if (!$cid) {
      $cids = self::findContact($person);
      if (is_array($cids) &&
        count($cids) == 1
      ) {
        $cid = $cids[0];
      }
      // did not find a contact or too many matching contacts
      // lets create a new one :)
    }

    $person['contact_type'] = 'Individual';

    // format address params
    if (isset($person['address'])) {
      $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
      foreach ($person['address'] as $key => $value) {
        $person['address'][$key]['location_type_id'] = array_search($person['address'][$key]['location_type'], $locationTypes);
        unset($person['address'][$key]['location_type']);
        if ($cid) {
          $person['address'][$key]['contact_id'] = $cid;
        }
      }
    }

    // format phone params
    if (isset($person['phone'])) {
      $phoneTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id');
      foreach ($person['phone'] as $key => $value) {
        $person['phone'][$key]['phone'] = $person['phone'][$key]['phone_number'];
        unset($person['phone'][$key]['phone_number']);
        $person['phone'][$key]['location_type_id'] = $person['address'][0]['location_type_id'];
        $person['phone'][$key]['phone_type_id'] = array_search($person['phone'][$key]['phone_type'], $phoneTypes);
        unset($person['phone'][$key]['phone_type']);
        if ($cid) {
          $person['phone'][$key]['contact_id'] = $cid;
        }
      }
    }

    require_once 'api/v2/Contact.php';
    if (!$cid) {
      $params = $person;
      $contact = civicrm_contact_create($params);
      if ($contact['is_error']) {
        CRM_Core_Error::fatal();
      }

      $cid = $contact['contact_id'];
    }
    else {
      $person['contact_id'] = $cid;
      $params = $person;
      $contact = civicrm_contact_update($params);
      if ($contact['is_error']) {
        CRM_Core_Error::fatal();
      }
    }

    // now add all the custom fields
    $sql = "
REPLACE INTO civicrm_value_van
       ( entity_id, van_id, party, county, precinct, congressional,
         assembly, state_senate, municipality, ward, supervisor_dist,
         portion, zip5 )
VALUES
       ( %1, %2, %3, %4, %5, %6,
         %7, %8, %9, %10, %11,
         %12, %13 )
";

    $custom = &$person['custom'];
    $params = array(1 => array($cid, 'Integer'),
      2 => array($custom['van_id'], 'Integer'),
      3 => array($custom['party'], 'String'),
      4 => array($custom['county'], 'String'),
      5 => array($custom['precinct'], 'Integer'),
      6 => array($custom['congressional'], 'Integer'),
      7 => array($custom['assembly'], 'Integer'),
      8 => array($custom['state_senate'], 'Integer'),
      9 => array($custom['municipality'], 'String'),
      10 => array($custom['ward'], 'Integer'),
      11 => array($custom['supervisor_dist'], 'Integer'),
      12 => array($custom['portion'], 'String'),
      13 => array($custom['zip5'], 'String'),
    );
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
  }
}

