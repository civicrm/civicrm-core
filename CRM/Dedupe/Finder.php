<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */

/**
 * The CiviCRM duplicate discovery engine is based on an
 * algorithm designed by David Strauss <david@fourkitchens.com>.
 */
class CRM_Dedupe_Finder {

  /**
   * Return a contact_id-keyed array of arrays of possible dupes
   * (of the key contact_id) - limited to dupes of $cids if provided.
   *
   * @param int $rgid
   *   Rule group id.
   * @param array $cids
   *   Contact ids to limit the search to.
   *
   * @param bool $checkPermissions
   *   Respect logged in user permissions.
   *
   * @param int $limit
   *   Optional limit. This limits the number of contacts for which the code will
   *   attempt to find matches.
   *
   * @return array
   *   Array of (cid1, cid2, weight) dupe triples
   *
   * @throws CiviCRM_API3_Exception
   * @throws Exception
   */
  public static function dupes($rgid, $cids = array(), $checkPermissions = TRUE, $limit = NULL) {
    $rgBao = new CRM_Dedupe_BAO_RuleGroup();
    $rgBao->id = $rgid;
    $rgBao->contactIds = $cids;
    if (!$rgBao->find(TRUE)) {
      CRM_Core_Error::fatal("Dedupe rule not found for selected contacts");
    }
    if (empty($rgBao->contactIds) && !empty($limit)) {
      $limitedContacts = civicrm_api3('Contact', 'get', array(
        'return' => 'id',
        'contact_type' => $rgBao->contact_type,
        'options' => array('limit' => $limit),
      ));
      $rgBao->contactIds = array_keys($limitedContacts['values']);
    }

    $rgBao->fillTable();
    $dao = new CRM_Core_DAO();
    $dao->query($rgBao->thresholdQuery($checkPermissions));
    $dupes = array();
    while ($dao->fetch()) {
      $dupes[] = array($dao->id1, $dao->id2, $dao->weight);
    }
    $dao->query($rgBao->tableDropQuery());

    return $dupes;
  }

  /**
   * Return an array of possible dupes, based on the provided array of
   * params, using the default rule group for the given contact type and
   * usage.
   *
   * check_permission is a boolean flag to indicate if permission should be considered.
   * default is to always check permissioning but public pages for example might not want
   * permission to be checked for anonymous users. Refer CRM-6211. We might be beaking
   * Multi-Site dedupe for public pages.
   *
   * @param array $params
   *   Array of params of the form $params[$table][$field] == $value.
   * @param string $ctype
   *   Contact type to match against.
   * @param string $used
   *   Dedupe rule group usage ('Unsupervised' or 'Supervised' or 'General').
   * @param array $except
   *   Array of contacts that shouldn't be considered dupes.
   * @param int $ruleGroupID
   *   The id of the dedupe rule we should be using.
   *
   * @return array
   *   matching contact ids
   */
  public static function dupesByParams(
    $params,
    $ctype,
    $used = 'Unsupervised',
    $except = array(),
    $ruleGroupID = NULL
  ) {
    // If $params is empty there is zero reason to proceed.
    if (!$params) {
      return array();
    }

    $foundByID = FALSE;
    if ($ruleGroupID) {
      $rgBao = new CRM_Dedupe_BAO_RuleGroup();
      $rgBao->id = $ruleGroupID;
      $rgBao->contact_type = $ctype;
      if ($rgBao->find(TRUE)) {
        $foundByID = TRUE;
      }
    }

    if (!$foundByID) {
      $rgBao = new CRM_Dedupe_BAO_RuleGroup();
      $rgBao->contact_type = $ctype;
      $rgBao->used = $used;
      if (!$rgBao->find(TRUE)) {
        CRM_Core_Error::fatal("$used rule for $ctype does not exist");
      }
    }
    $params['check_permission'] = CRM_Utils_Array::value('check_permission', $params, TRUE);

    $rgBao->params = $params;
    $rgBao->fillTable();
    $dao = new CRM_Core_DAO();
    $dao->query($rgBao->thresholdQuery($params['check_permission']));
    $dupes = array();
    while ($dao->fetch()) {
      if (isset($dao->id) && $dao->id) {
        $dupes[] = $dao->id;
      }
    }
    $dao->query($rgBao->tableDropQuery());
    return array_diff($dupes, $except);
  }

  /**
   * Return a contact_id-keyed array of arrays of possible dupes in the given group.
   *
   * @param int $rgid
   *   Rule group id.
   * @param int $gid
   *   Contact group id (currently, works only with non-smart groups).
   *
   * @param int $limit
   * @return array
   *   array of (cid1, cid2, weight) dupe triples
   */
  public static function dupesInGroup($rgid, $gid, $limit = NULL) {
    $cids = array_keys(CRM_Contact_BAO_Group::getMember($gid, $limit));
    if (!empty($cids)) {
      return self::dupes($rgid, $cids);
    }
    return array();
  }

  /**
   * Return dupes of a given contact, using the default rule group (of a provided usage).
   *
   * @param int $cid
   *   Contact id of the given contact.
   * @param string $used
   *   Dedupe rule group usage ('Unsupervised' or 'Supervised' or 'General').
   * @param string $ctype
   *   Contact type of the given contact.
   *
   * @return array
   *   array of dupe contact_ids
   */
  public static function dupesOfContact($cid, $used = 'Unsupervised', $ctype = NULL) {
    // if not provided, fetch the contact type from the database
    if (!$ctype) {
      $dao = new CRM_Contact_DAO_Contact();
      $dao->id = $cid;
      if (!$dao->find(TRUE)) {
        CRM_Core_Error::fatal("contact id of $cid does not exist");
      }
      $ctype = $dao->contact_type;
    }
    $rgBao = new CRM_Dedupe_BAO_RuleGroup();
    $rgBao->used = $used;
    $rgBao->contact_type = $ctype;
    if (!$rgBao->find(TRUE)) {
      CRM_Core_Error::fatal("$used rule for $ctype does not exist");
    }
    $dupes = self::dupes($rgBao->id, array($cid));

    // get the dupes for this cid
    $result = array();
    foreach ($dupes as $dupe) {
      if ($dupe[0] == $cid) {
        $result[] = $dupe[1];
      }
      elseif ($dupe[1] == $cid) {
        $result[] = $dupe[0];
      }
    }
    return $result;
  }

  /**
   * A hackish function needed to massage CRM_Contact_Form_$ctype::formRule()
   * object into a valid $params array for dedupe
   *
   * @param array $fields
   *   Contact structure from formRule().
   * @param string $ctype
   *   Contact type of the given contact.
   *
   * @return array
   *   valid $params array for dedupe
   */
  public static function formatParams($fields, $ctype) {
    $flat = array();
    CRM_Utils_Array::flatten($fields, $flat);

    // FIXME: This may no longer be necessary - check inputs
    $replace_these = array(
      'individual_prefix' => 'prefix_id',
      'individual_suffix' => 'suffix_id',
      'gender' => 'gender_id',
    );
    foreach (array('individual_suffix', 'individual_prefix', 'gender') as $name) {
      if (!empty($fields[$name])) {
        $flat[$replace_these[$name]] = $flat[$name];
        unset($flat[$name]);
      }
    }

    // handle {birth,deceased}_date
    foreach (array(
      'birth_date',
      'deceased_date',
    ) as $date) {
      if (!empty($fields[$date])) {
        $flat[$date] = $fields[$date];
        if (is_array($flat[$date])) {
          $flat[$date] = CRM_Utils_Date::format($flat[$date]);
        }
        $flat[$date] = CRM_Utils_Date::processDate($flat[$date]);
      }
    }

    if (!empty($flat['contact_source'])) {
      $flat['source'] = $flat['contact_source'];
      unset($flat['contact_source']);
    }

    // handle preferred_communication_method
    if (!empty($fields['preferred_communication_method'])) {
      $methods = array_intersect($fields['preferred_communication_method'], array('1'));
      $methods = array_keys($methods);
      sort($methods);
      if ($methods) {
        $flat['preferred_communication_method'] = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR, $methods) . CRM_Core_DAO::VALUE_SEPARATOR;
      }
    }

    // handle custom data
    $tree = CRM_Core_BAO_CustomGroup::getTree($ctype, CRM_Core_DAO::$_nullObject, NULL, -1);
    CRM_Core_BAO_CustomGroup::postProcess($tree, $fields, TRUE);
    foreach ($tree as $key => $cg) {
      if (!is_int($key)) {
        continue;
      }
      foreach ($cg['fields'] as $cf) {
        $flat[$cf['column_name']] = CRM_Utils_Array::value('data', $cf['customValue']);
      }
    }

    // if the key is dotted, keep just the last part of it
    foreach ($flat as $key => $value) {
      if (substr_count($key, '.')) {
        $last = explode('.', $key);
        $last = array_pop($last);
        // make sure the first occurrence is kept, not the last
        if (!isset($flat[$last])) {
          $flat[$last] = $value;
        }
        unset($flat[$key]);
      }
    }

    // drop the -digit (and -Primary, for CRM-3902) postfixes (so event registration's $flat['email-5'] becomes $flat['email'])
    // FIXME: CRM-5026 should be fixed here; the below clobbers all address info; we should split off address fields and match
    // the -digit to civicrm_address.location_type_id and -Primary to civicrm_address.is_primary
    foreach ($flat as $key => $value) {
      $matches = array();
      if (preg_match('/(.*)-(Primary-[\d+])$|(.*)-(\d+|Primary)$/', $key, $matches)) {
        $return = array_values(array_filter($matches));
        $flat[$return[1]] = $value;
        unset($flat[$key]);
      }
    }

    $params = array();
    $supportedFields = CRM_Dedupe_BAO_RuleGroup::supportedFields($ctype);
    if (is_array($supportedFields)) {
      foreach ($supportedFields as $table => $fields) {
        if ($table == 'civicrm_address') {
          // for matching on civicrm_address fields, we also need the location_type_id
          $fields['location_type_id'] = '';
          // FIXME: we also need to do some hacking for id and name fields, see CRM-3902’s comments
          $fixes = array(
            'address_name' => 'name',
            'country' => 'country_id',
            'state_province' => 'state_province_id',
            'county' => 'county_id',
          );
          foreach ($fixes as $orig => $target) {
            if (!empty($flat[$orig])) {
              $params[$table][$target] = $flat[$orig];
            }
          }
        }
        foreach ($fields as $field => $title) {
          if (!empty($flat[$field])) {
            $params[$table][$field] = $flat[$field];
          }
        }
      }
    }
    return $params;
  }

  /**
   * Parse duplicate pairs into a standardised array and store in the prev_next_cache.
   *
   * @param array $foundDupes
   * @param string $cacheKeyString
   *
   * @return array Dupe pairs with the keys
   *   Dupe pairs with the keys
   *   -srcID
   *   -srcName
   *   -dstID
   *   -dstName
   *   -weight
   *   -canMerge
   *
   * @throws CRM_Core_Exception
   */
  public static function parseAndStoreDupePairs($foundDupes, $cacheKeyString) {
    $cids = array();
    foreach ($foundDupes as $dupe) {
      $cids[$dupe[0]] = 1;
      $cids[$dupe[1]] = 1;
    }
    $cidString = implode(', ', array_keys($cids));

    $dao = CRM_Core_DAO::executeQuery("SELECT id, display_name FROM civicrm_contact WHERE id IN ($cidString) ORDER BY sort_name");
    $displayNames = array();
    while ($dao->fetch()) {
      $displayNames[$dao->id] = $dao->display_name;
    }

    $userId = CRM_Core_Session::singleton()->getLoggedInContactID();
    foreach ($foundDupes as $dupes) {
      $srcID = $dupes[1];
      $dstID = $dupes[0];
      // The logged in user should never be the src (ie. the contact to be removed).
      if ($srcID == $userId) {
        $srcID = $dstID;
        $dstID = $userId;
      }

      $mainContacts[] = $row = array(
        'dstID' => $dstID,
        'dstName' => $displayNames[$dstID],
        'srcID' => $srcID,
        'srcName' => $displayNames[$srcID],
        'weight' => $dupes[2],
        'canMerge' => TRUE,
      );

      $data = CRM_Core_DAO::escapeString(serialize($row));
      $values[] = " ( 'civicrm_contact', $dstID, $srcID, '$cacheKeyString', '$data' ) ";
    }
    CRM_Core_BAO_PrevNextCache::setItem($values);
    return $mainContacts;
  }

}
