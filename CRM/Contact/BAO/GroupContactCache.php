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
class CRM_Contact_BAO_GroupContactCache extends CRM_Contact_DAO_GroupContactCache {

  static $_alreadyLoaded = array();

  /**
   * Check to see if we have cache entries for this group
   * if not, regenerate, else return
   *
   * @param $groupIDs
   *
   * @internal param int $groupID groupID of group that we are checking against
   *
   * @return boolean true if we did not regenerate, false if we did
   */
  static function check($groupIDs) {
    if (empty($groupIDs)) {
      return TRUE;
    }

    return self::loadAll($groupIDs);
  }

  /**
   * Common function that formulates the query to see which groups needs to be refreshed
   * based on their cache date and the smartGroupCacheTimeOut
   *
   * @param string $groupIDClause the clause which limits which groups we need to evaluate
   * @param boolean $includeHiddenGroups hidden groups are excluded by default
   *
   * @return string the sql query which lists the groups that need to be refreshed
   * @static
   * @public
   */
  static function groupRefreshedClause($groupIDClause = null, $includeHiddenGroups = FALSE) {
    $smartGroupCacheTimeout = self::smartGroupCacheTimeout();
    $now = CRM_Utils_Date::getUTCTime();

    $query = "
SELECT  g.id
FROM    civicrm_group g
WHERE   ( g.saved_search_id IS NOT NULL OR g.children IS NOT NULL )
AND     g.is_active = 1
AND     ( g.cache_date IS NULL OR
          ( TIMESTAMPDIFF(MINUTE, g.cache_date, $now) >= $smartGroupCacheTimeout ) OR
          ( $now >= g.refresh_date )
        )
";

    if (!$includeHiddenGroups) {
      $query .= "AND (g.is_hidden = 0 OR g.is_hidden IS NULL)";
    }

    if (!empty($groupIDClause)) {
      $query .= " AND ( $groupIDClause ) ";
    }

    return $query;
  }

  /**
   * Checks to see if a group has been refreshed recently. This is primarily used
   * in a locking scenario when some other process might have refreshed things underneath
   * this process
   *
   * @param int $groupID the group ID
   * @param boolean $includeHiddenGroups hidden groups are excluded by default
   *
   * @return string the sql query which lists the groups that need to be refreshed
   * @static
   * @public
   */
  static function shouldGroupBeRefreshed($groupID, $includeHiddenGroups = FALSE) {
    $query = self::groupRefreshedClause("g.id = %1", $includeHiddenGroups);
    $params = array(1 => array($groupID, 'Integer'));

    // if the query returns the group ID, it means the group is a valid candidate for refreshing
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * Check to see if we have cache entries for this group
   * if not, regenerate, else return
   *
   * @param int/array $groupID groupID of group that we are checking against
   *                           if empty, all groups are checked
   * @param int       $limit   limits the number of groups we evaluate
   *
   * @return boolean true if we did not regenerate, false if we did
   */
  static function loadAll($groupIDs = null, $limit = 0) {
    // ensure that all the smart groups are loaded
    // this function is expensive and should be sparingly used if groupIDs is empty
    if (empty($groupIDs)) {
      $groupIDClause = null;
      $groupIDs = array( );
    }
    else {
      if (!is_array($groupIDs)) {
        $groupIDs = array($groupIDs);
      }

      // note escapeString is a must here and we can't send the imploded value as second arguement to
      // the executeQuery(), since that would put single quote around the string and such a string
      // of comma separated integers would not work.
      $groupIDString = CRM_Core_DAO::escapeString(implode(', ', $groupIDs));

      $groupIDClause = "g.id IN ({$groupIDString})";
    }

    $query = self::groupRefreshedClause($groupIDClause);

    $limitClause = $orderClause = NULL;
    if ($limit > 0) {
      $limitClause = " LIMIT 0, $limit";
      $orderClause = " ORDER BY g.cache_date, g.refresh_date";
    }
    // We ignore hidden groups and disabled groups
    $query .= "
        $orderClause
        $limitClause
";

    $dao = CRM_Core_DAO::executeQuery($query);
    $processGroupIDs = array();
    $refreshGroupIDs = $groupIDs;
    while ($dao->fetch()) {
      $processGroupIDs[] = $dao->id;

      // remove this id from refreshGroupIDs
      foreach ($refreshGroupIDs as $idx => $gid) {
        if ($gid == $dao->id) {
          unset($refreshGroupIDs[$idx]);
          break;
        }
      }
    }

    if (!empty($refreshGroupIDs)) {
      $refreshGroupIDString = CRM_Core_DAO::escapeString(implode(', ', $refreshGroupIDs));
      $time  = CRM_Utils_Date::getUTCTime(self::smartGroupCacheTimeout() * 60);
      $query = "
UPDATE civicrm_group g
SET    g.refresh_date = $time
WHERE  g.id IN ( {$refreshGroupIDString} )
AND    g.refresh_date IS NULL
";
      CRM_Core_DAO::executeQuery($query);
    }

    if (empty($processGroupIDs)) {
      return TRUE;
    }
    else {
      self::add($processGroupIDs);
      return FALSE;
    }
  }

  /**
   * FIXME: This function should not be needed, because the cache table should not be getting truncated
   */
  static function fillIfEmpty() {
    if (!CRM_Core_DAO::singleValueQuery("SELECT COUNT(id) FROM civicrm_group_contact_cache")) {
      self::loadAll();
    }
  }

  /**
   * @param $groupID
   */
  static function add($groupID) {
    // first delete the current cache
    self::remove($groupID);
    if (!is_array($groupID)) {
      $groupID = array($groupID);
    }

    $returnProperties = array('contact_id');
    foreach ($groupID as $gid) {
      $params = array(array('group', 'IN', array($gid => 1), 0, 0));
      // the below call updates the cache table as a byproduct of the query
      CRM_Contact_BAO_Query::apiQuery($params, $returnProperties, NULL, NULL, 0, 0, FALSE);
    }
  }

  /**
   * @param $groupID
   * @param $values
   */
  static function store(&$groupID, &$values) {
    $processed = FALSE;

    // sort the values so we put group IDs in front and hence optimize
    // mysql storage (or so we think) CRM-9493
    sort($values);

    // to avoid long strings, lets do BULK_INSERT_COUNT values at a time
    while (!empty($values)) {
      $processed = TRUE;
      $input     = array_splice($values, 0, CRM_Core_DAO::BULK_INSERT_COUNT);
      $str       = implode(',', $input);
      $sql       = "INSERT IGNORE INTO civicrm_group_contact_cache (group_id,contact_id) VALUES $str;";
      CRM_Core_DAO::executeQuery($sql);
    }
    self::updateCacheTime($groupID, $processed);
  }

  /**
   * Change the cache_date
   *
   * @param $groupID array(int)
   * @param $processed bool, whether the cache data was recently modified
   */
  static function updateCacheTime($groupID, $processed) {
    // only update cache entry if we had any values
    if ($processed) {
      // also update the group with cache date information
      //make sure to give original timezone settings again.
      $now     = CRM_Utils_Date::getUTCTime();
      $refresh = 'null';
    }
    else {
      $now     = 'null';
      $refresh = 'null';
    }

    $groupIDs = implode(',', $groupID);
    $sql = "
UPDATE civicrm_group
SET    cache_date = $now, refresh_date = $refresh
WHERE  id IN ( $groupIDs )
";
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Removes all the cache entries pertaining to a specific group
   * If no groupID is passed in, removes cache entries for all groups
   * Has an optimization to bypass repeated invocations of this function.
   * Note that this function is an advisory, i.e. the removal respects the
   * cache date, i.e. the removal is not done if the group was recently
   * loaded into the cache.
   *
   * @param $groupID  int the groupID to delete cache entries, NULL for all groups
   * @param $onceOnly boolean run the function exactly once for all groups.
   *
   * @public
   * @return void
   * @static
   */
  static function remove($groupID = NULL, $onceOnly = TRUE) {
    static $invoked = FALSE;

    // typically this needs to happy only once per instance
    // this is especially true in import, where we dont need
    // to do this all the time
    // this optimization is done only when no groupID is passed
    // i.e. cache is reset for all groups
    if (
      $onceOnly &&
      $invoked &&
      $groupID == NULL
    ) {
      return;
    }

    if ($groupID == NULL) {
      $invoked = TRUE;
    } else if (is_array($groupID)) {
      foreach ($groupID as $gid) {
        unset(self::$_alreadyLoaded[$gid]);
      }
    } else if ($groupID && array_key_exists($groupID, self::$_alreadyLoaded)) {
      unset(self::$_alreadyLoaded[$groupID]);
    }

    $refresh = null;
    $params  = array();
    $smartGroupCacheTimeout = self::smartGroupCacheTimeout();

    $now         = CRM_Utils_Date::getUTCTime();
    $refreshTime = CRM_Utils_Date::getUTCTime($smartGroupCacheTimeout * 60);

    if (!isset($groupID)) {
      if ($smartGroupCacheTimeout == 0) {
        $query = "
TRUNCATE civicrm_group_contact_cache
";
        $update = "
UPDATE civicrm_group g
SET    cache_date = null,
       refresh_date = null
";
      }
      else {
        $query = "
DELETE     gc
FROM       civicrm_group_contact_cache gc
INNER JOIN civicrm_group g ON g.id = gc.group_id
WHERE      TIMESTAMPDIFF(MINUTE, g.cache_date, $now) >= $smartGroupCacheTimeout
";
        $update = "
UPDATE civicrm_group g
SET    cache_date = null,
       refresh_date = null
WHERE  TIMESTAMPDIFF(MINUTE, cache_date, $now) >= $smartGroupCacheTimeout
";
        $refresh = "
UPDATE civicrm_group g
SET    refresh_date = $refreshTime
WHERE  TIMESTAMPDIFF(MINUTE, cache_date, $now) < $smartGroupCacheTimeout
AND    refresh_date IS NULL
";
      }
    }
    elseif (is_array($groupID)) {
      $groupIDs = implode(', ', $groupID);
      $query = "
DELETE     g
FROM       civicrm_group_contact_cache g
WHERE      g.group_id IN ( $groupIDs )
";
      $update = "
UPDATE civicrm_group g
SET    cache_date = null,
       refresh_date = null
WHERE  id IN ( $groupIDs )
";
    }
    else {
      $query = "
DELETE     g
FROM       civicrm_group_contact_cache g
WHERE      g.group_id = %1
";
      $update = "
UPDATE civicrm_group g
SET    cache_date = null,
       refresh_date = null
WHERE  id = %1
";
      $params = array(1 => array($groupID, 'Integer'));
    }

    CRM_Core_DAO::executeQuery($query, $params);

    if ($refresh) {
      CRM_Core_DAO::executeQuery($refresh, $params);
    }

    // also update the cache_date for these groups
    CRM_Core_DAO::executeQuery($update, $params);
  }

  /**
   * load the smart group cache for a saved search
   *
   * @param object  $group - the smart group that needs to be loaded
   * @param boolean $force - should we force a search through
   *
   */
  static function load(&$group, $force = FALSE) {
    $groupID = $group->id;
    $savedSearchID = $group->saved_search_id;
    if (array_key_exists($groupID, self::$_alreadyLoaded) && !$force) {
      return;
    }

    // grab a lock so other processes dont compete and do the same query
    $lockName = "civicrm.group.{$groupID}";
    $lock = new CRM_Core_Lock($lockName);
    if (!$lock->isAcquired()) {
      // this can cause inconsistent results since we dont know if the other process
      // will fill up the cache before our calling routine needs it.
      // however this routine does not return the status either, so basically
      // its a "lets return and hope for the best"
      return;
    }

    self::$_alreadyLoaded[$groupID] = 1;

    // we now have the lock, but some other proces could have actually done the work
    // before we got here, so before we do any work, lets ensure that work needs to be
    // done
    // we allow hidden groups here since we dont know if the caller wants to evaluate an
    // hidden group
    if (!$force && !self::shouldGroupBeRefreshed($groupID, TRUE)) {
      $lock->release();
      return;
    }

    $sql         = NULL;
    $idName      = 'id';
    $customClass = NULL;
    if ($savedSearchID) {
      $ssParams = CRM_Contact_BAO_SavedSearch::getSearchParams($savedSearchID);

      // rectify params to what proximity search expects if there is a value for prox_distance
      // CRM-7021
      if (!empty($ssParams)) {
        CRM_Contact_BAO_ProximityQuery::fixInputParams($ssParams);
      }


      $returnProperties = array();
      if (CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch', $savedSearchID, 'mapping_id')) {
        $fv = CRM_Contact_BAO_SavedSearch::getFormValues($savedSearchID);
        $returnProperties = CRM_Core_BAO_Mapping::returnProperties($fv);
      }

      if (isset($ssParams['customSearchID'])) {
        // if custom search

        // we split it up and store custom class
        // so temp tables are not destroyed if they are used
        // hence customClass is defined above at top of function
        $customClass =
          CRM_Contact_BAO_SearchCustom::customClass($ssParams['customSearchID'], $savedSearchID);
        $searchSQL = $customClass->contactIDs();
        $searchSQL = str_replace('ORDER BY contact_a.id ASC', '', $searchSQL);
        $idName = 'contact_id';
      }
      else {
        $formValues = CRM_Contact_BAO_SavedSearch::getFormValues($savedSearchID);

        $query =
          new CRM_Contact_BAO_Query(
            $ssParams, $returnProperties, NULL,
            FALSE, FALSE, 1,
            TRUE, TRUE,
            FALSE,
            CRM_Utils_Array::value('display_relationship_type', $formValues),
            CRM_Utils_Array::value('operator', $formValues, 'AND')
        );
        $query->_useDistinct = FALSE;
        $query->_useGroupBy  = FALSE;
        $searchSQL           =
          $query->searchQuery(
            0, 0, NULL,
          FALSE, FALSE,
          FALSE, TRUE,
          TRUE,
          NULL, NULL, NULL,
          TRUE
        );
      }
      $groupID = CRM_Utils_Type::escape($groupID, 'Integer');
      $sql = $searchSQL . " AND contact_a.id NOT IN (
                              SELECT contact_id FROM civicrm_group_contact
                              WHERE civicrm_group_contact.status = 'Removed'
                              AND   civicrm_group_contact.group_id = $groupID ) ";
    }

    if ($sql) {
      $sql = preg_replace("/^\s*SELECT/", "SELECT $groupID as group_id, ", $sql);
    }

    // lets also store the records that are explicitly added to the group
    // this allows us to skip the group contact LEFT JOIN
    $sqlB = "
SELECT $groupID as group_id, contact_id as $idName
FROM   civicrm_group_contact
WHERE  civicrm_group_contact.status = 'Added'
  AND  civicrm_group_contact.group_id = $groupID ";

    $groupIDs = array($groupID);
    self::remove($groupIDs);
    $processed = FALSE;
    $tempTable = 'civicrm_temp_group_contact_cache' . rand(0,2000);
    foreach (array($sql, $sqlB) as $selectSql) {
      if (!$selectSql) {
        continue;
      }
      $insertSql = "CREATE TEMPORARY TABLE $tempTable ($selectSql);";
      $processed = TRUE;
      $result = CRM_Core_DAO::executeQuery($insertSql);
      CRM_Core_DAO::executeQuery(
        "INSERT IGNORE INTO civicrm_group_contact_cache (contact_id, group_id)
        SELECT DISTINCT $idName, group_id FROM $tempTable
      ");
      CRM_Core_DAO::executeQuery(" DROP TABLE $tempTable");
    }

    self::updateCacheTime($groupIDs, $processed);

    if ($group->children) {

      //Store a list of contacts who are removed from the parent group
      $sql = "
SELECT contact_id
FROM civicrm_group_contact
WHERE  civicrm_group_contact.status = 'Removed'
AND  civicrm_group_contact.group_id = $groupID ";
      $dao = CRM_Core_DAO::executeQuery($sql);
      $removed_contacts = array();
      while ($dao->fetch()) {
        $removed_contacts[] = $dao->contact_id;
      }

      $childrenIDs = explode(',', $group->children);
      foreach ($childrenIDs as $childID) {
        $contactIDs = CRM_Contact_BAO_Group::getMember($childID, FALSE);
        //Unset each contact that is removed from the parent group
        foreach ($removed_contacts as $removed_contact) {
          unset($contactIDs[$removed_contact]);
        }
        $values = array();
        foreach ($contactIDs as $contactID => $dontCare) {
          $values[] = "({$groupID},{$contactID})";
        }

        self::store($groupIDs, $values);
      }
    }

    $lock->release();
  }

  /**
   * @return int
   */
  static function smartGroupCacheTimeout() {
    $config = CRM_Core_Config::singleton();

    if (
      isset($config->smartGroupCacheTimeout) &&
      is_numeric($config->smartGroupCacheTimeout) &&
      $config->smartGroupCacheTimeout > 0) {
      return $config->smartGroupCacheTimeout;
    }

    // lets have a min cache time of 5 mins if not set
    return 5;
  }

  /**
   * Get all the smart groups that this contact belongs to
   * Note that this could potentially be a super slow function since
   * it ensure that all contact groups are loaded in the cache
   *
   * @param int     $contactID
   * @param boolean $showHidden - hidden groups are shown only if this flag is set
   *
   * @return array an array of groups that this contact belongs to
   */
  static function contactGroup($contactID, $showHidden = FALSE) {
    if (empty($contactID)) {
      return;
    }

    if (is_array($contactID)) {
      $contactIDs = $contactID;
    }
    else {
      $contactIDs = array($contactID);
    }

    self::loadAll();

    $hiddenClause = '';
    if (!$showHidden) {
      $hiddenClause = ' AND (g.is_hidden = 0 OR g.is_hidden IS NULL) ';
    }

    $contactIDString = CRM_Core_DAO::escapeString(implode(', ', $contactIDs));
    $sql = "
SELECT     gc.group_id, gc.contact_id, g.title, g.children, g.description
FROM       civicrm_group_contact_cache gc
INNER JOIN civicrm_group g ON g.id = gc.group_id
WHERE      gc.contact_id IN ($contactIDString)
           $hiddenClause
ORDER BY   gc.contact_id, g.children
";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $contactGroup = array();
    $prevContactID = null;
    while ($dao->fetch()) {
      if (
        $prevContactID &&
        $prevContactID != $dao->contact_id
      ) {
        $contactGroup[$prevContactID]['groupTitle'] = implode(', ', $contactGroup[$prevContactID]['groupTitle']);
      }
      $prevContactID = $dao->contact_id;
      if (!array_key_exists($dao->contact_id, $contactGroup)) {
        $contactGroup[$dao->contact_id] =
          array( 'group' => array(), 'groupTitle' => array());
      }

      $contactGroup[$dao->contact_id]['group'][] =
        array(
          'id' => $dao->group_id,
          'title' => $dao->title,
          'description' => $dao->description,
          'children' => $dao->children
        );
      $contactGroup[$dao->contact_id]['groupTitle'][] = $dao->title;
    }

    if ($prevContactID) {
      $contactGroup[$prevContactID]['groupTitle'] = implode(', ', $contactGroup[$prevContactID]['groupTitle']);
    }

    if ((!empty($contactGroup[$contactID]) && is_numeric($contactID))) {
      return $contactGroup[$contactID];
    }
    else {
      return $contactGroup;
    }
  }

}

