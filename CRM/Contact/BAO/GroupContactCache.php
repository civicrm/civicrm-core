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

use Civi\Api4\Query\SqlExpression;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contact_BAO_GroupContactCache extends CRM_Contact_DAO_GroupContactCache {

  public static $_alreadyLoaded = [];

  /**
   * Get a list of caching modes.
   *
   * @return array
   */
  public static function getModes() {
    return [
      // Flush expired caches in response to user actions.
      'opportunistic' => ts('Opportunistic Flush'),

      // Flush expired caches via background cron jobs.
      'deterministic' => ts('Cron Flush'),
    ];
  }

  /**
   * Check to see if we have cache entries for this group.
   *
   * If not, regenerate, else return.
   *
   * @param array $groupIDs
   *   Of group that we are checking against.
   *
   * @return bool
   *   TRUE if we did not regenerate, FALSE if we did
   */
  public static function check($groupIDs) {
    if (empty($groupIDs)) {
      return TRUE;
    }

    return self::loadAll($groupIDs);
  }

  /**
   * Formulate the query to see which groups needs to be refreshed.
   *
   * The calculation is based on their cache date and the smartGroupCacheTimeOut
   *
   * @param string $groupIDClause
   *   The clause which limits which groups we need to evaluate.
   * @param bool $includeHiddenGroups
   *   Hidden groups are excluded by default.
   *
   * @return string
   *   the sql query which lists the groups that need to be refreshed
   */
  public static function groupRefreshedClause($groupIDClause = NULL, $includeHiddenGroups = FALSE) {
    $smartGroupCacheTimeoutDateTime = self::getCacheInvalidDateTime();

    $query = "
SELECT  g.id
FROM    civicrm_group g
WHERE   ( g.saved_search_id IS NOT NULL OR g.children IS NOT NULL )
AND     g.is_active = 1
AND (
  g.cache_date IS NULL
  OR cache_date <= $smartGroupCacheTimeoutDateTime
  OR NOW() >= g.refresh_date
)";

    if (!$includeHiddenGroups) {
      $query .= "AND (g.is_hidden = 0 OR g.is_hidden IS NULL)";
    }

    if (!empty($groupIDClause)) {
      $query .= " AND ( $groupIDClause ) ";
    }

    return $query;
  }

  /**
   * Check to see if a group has been refreshed recently.
   *
   * This is primarily used in a locking scenario when some other process might have refreshed things underneath
   * this process
   *
   * @param int $groupID
   *   The group ID.
   * @param bool $includeHiddenGroups
   *   Hidden groups are excluded by default.
   *
   * @return string
   *   the sql query which lists the groups that need to be refreshed
   */
  public static function shouldGroupBeRefreshed($groupID, $includeHiddenGroups = FALSE) {
    $query = self::groupRefreshedClause("g.id = %1", $includeHiddenGroups);
    $params = [1 => [$groupID, 'Integer']];

    // if the query returns the group ID, it means the group is a valid candidate for refreshing
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * Check to see if we have cache entries for this group.
   *
   * if not, regenerate, else return
   *
   * @param int|array $groupIDs groupIDs of group that we are checking against
   *                           if empty, all groups are checked
   * @param int $limit
   *   Limits the number of groups we evaluate.
   *
   * @return bool
   *   TRUE if we did not regenerate, FALSE if we did
   */
  public static function loadAll($groupIDs = NULL, $limit = 0) {
    // ensure that all the smart groups are loaded
    // this function is expensive and should be sparingly used if groupIDs is empty
    if (empty($groupIDs)) {
      $groupIDClause = NULL;
      $groupIDs = [];
    }
    else {
      if (!is_array($groupIDs)) {
        $groupIDs = [$groupIDs];
      }

      // note escapeString is a must here and we can't send the imploded value as second argument to
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
    $processGroupIDs = [];
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
      $time = self::getRefreshDateTime();
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
   * Build the smart group cache for given groups.
   *
   * @param array $groupIDs
   */
  public static function add($groupIDs) {
    $groupIDs = (array) $groupIDs;

    foreach ($groupIDs as $groupID) {
      // first delete the current cache
      self::clearGroupContactCache($groupID);
      $params = [['group', 'IN', [$groupID], 0, 0]];
      // the below call updates the cache table as a byproduct of the query
      CRM_Contact_BAO_Query::apiQuery($params, ['contact_id'], NULL, NULL, 0, 0, FALSE);
    }
  }

  /**
   * Store values into the group contact cache.
   *
   * @todo review use of INSERT IGNORE. This function appears to be slower that inserting
   * with a left join. Also, 200 at once seems too little.
   *
   * @param array $groupID
   * @param array $values
   */
  public static function store($groupID, &$values) {
    $processed = FALSE;

    // sort the values so we put group IDs in front and hence optimize
    // mysql storage (or so we think) CRM-9493
    sort($values);

    // to avoid long strings, lets do BULK_INSERT_COUNT values at a time
    while (!empty($values)) {
      $processed = TRUE;
      $input = array_splice($values, 0, CRM_Core_DAO::BULK_INSERT_COUNT);
      $str = implode(',', $input);
      $sql = "INSERT IGNORE INTO civicrm_group_contact_cache (group_id,contact_id) VALUES $str;";
      CRM_Core_DAO::executeQuery($sql);
    }
    self::updateCacheTime($groupID, $processed);
  }

  /**
   * Change the cache_date.
   *
   * @param array $groupID
   * @param bool $processed
   *   Whether the cache data was recently modified.
   */
  public static function updateCacheTime($groupID, $processed) {
    // only update cache entry if we had any values
    if ($processed) {
      // also update the group with cache date information
      $now = date('YmdHis');
      $refresh = 'null';
    }
    else {
      $now = 'null';
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
   * Function to clear group contact cache and reset the corresponding
   *  group's cache and refresh date
   *
   * @param int $groupID
   *
   */
  public static function clearGroupContactCache($groupID) {
    $transaction = new CRM_Core_Transaction();
    $query = "
    DELETE  g
      FROM  civicrm_group_contact_cache g
      WHERE  g.group_id = %1 ";

    $update = "
  UPDATE civicrm_group g
    SET    cache_date = null, refresh_date = null
    WHERE  id = %1 ";

    $params = [
      1 => [$groupID, 'Integer'],
    ];

    CRM_Core_DAO::executeQuery($query, $params);
    // also update the cache_date for these groups
    CRM_Core_DAO::executeQuery($update, $params);
    unset(self::$_alreadyLoaded[$groupID]);

    $transaction->commit();
  }

  /**
   * Refresh the smart group cache tables.
   *
   * This involves clearing out any aged entries (based on the site timeout setting) and resetting the time outs.
   *
   * This function should be called via the opportunistic or deterministic cache refresh function to make the intent
   * clear.
   */
  protected static function flushCaches() {
    try {
      $lock = self::getLockForRefresh();
    }
    catch (CRM_Core_Exception $e) {
      // Someone else is kindly doing the refresh for us right now.
      return;
    }
    $params = [1 => [self::getCacheInvalidDateTime(), 'String']];
    $groupsDAO = CRM_Core_DAO::executeQuery("SELECT id FROM civicrm_group WHERE cache_date <= %1", $params);
    $expiredGroups = [];
    while ($groupsDAO->fetch()) {
      $expiredGroups[] = $groupsDAO->id;
    }
    if (!empty($expiredGroups)) {
      $expiredGroups = implode(',', $expiredGroups);
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_group_contact_cache WHERE group_id IN ({$expiredGroups})");

      // Clear these out without resetting them because we are not building caches here, only clearing them,
      // so the state is 'as if they had never been built'.
      CRM_Core_DAO::executeQuery("UPDATE civicrm_group SET cache_date = NULL, refresh_date = NULL WHERE id IN ({$expiredGroups})");
    }
    $lock->release();
  }

  /**
   * Check if the refresh is already initiated.
   *
   * We have 2 imperfect methods for this:
   *   1) a static variable in the function. This works fine within a request
   *   2) a mysql lock. This works fine as long as CiviMail is not running, or if mysql is version 5.7+
   *
   * Where these 2 locks fail we get 2 processes running at the same time, but we have at least minimised that.
   *
   * @return \Civi\Core\Lock\LockInterface
   * @throws \CRM_Core_Exception
   */
  protected static function getLockForRefresh() {
    if (!isset(Civi::$statics[__CLASS__]['is_refresh_init'])) {
      Civi::$statics[__CLASS__] = ['is_refresh_init' => FALSE];
    }

    if (Civi::$statics[__CLASS__]['is_refresh_init']) {
      throw new CRM_Core_Exception('A refresh has already run in this process');
    }
    $lock = Civi::lockManager()->acquire('data.core.group.refresh');
    if ($lock->isAcquired()) {
      Civi::$statics[__CLASS__]['is_refresh_init'] = TRUE;
      return $lock;
    }
    throw new CRM_Core_Exception('Mysql lock unavailable');
  }

  /**
   * Do an opportunistic cache refresh if the site is configured for these.
   *
   * Sites that do not run the smart group clearing cron job should refresh the
   * caches on demand. The user session will be forced to wait so it is less
   * ideal.
   */
  public static function opportunisticCacheFlush() {
    if (Civi::settings()->get('smart_group_cache_refresh_mode') == 'opportunistic') {
      self::flushCaches();
    }
  }

  /**
   * Do a forced cache refresh.
   *
   * This function is appropriate to be called by system jobs & non-user sessions.
   */
  public static function deterministicCacheFlush() {
    if (self::smartGroupCacheTimeout() == 0) {
      CRM_Core_DAO::executeQuery("TRUNCATE civicrm_group_contact_cache");
      CRM_Core_DAO::executeQuery("UPDATE civicrm_group SET cache_date = NULL, refresh_date = NULL");
    }
    else {
      self::flushCaches();
    }
  }

  /**
   * Remove one or more contacts from the smart group cache.
   *
   * @param int|array $cid
   * @param int $groupId
   *
   * @return bool
   *   TRUE if successful.
   */
  public static function removeContact($cid, $groupId = NULL) {
    $cids = [];
    // sanitize input
    foreach ((array) $cid as $c) {
      $cids[] = CRM_Utils_Type::escape($c, 'Integer');
    }
    if ($cids) {
      $condition = count($cids) == 1 ? "= {$cids[0]}" : "IN (" . implode(',', $cids) . ")";
      if ($groupId) {
        $condition .= " AND group_id = " . CRM_Utils_Type::escape($groupId, 'Integer');
      }
      $sql = "DELETE FROM civicrm_group_contact_cache WHERE contact_id $condition";
      CRM_Core_DAO::executeQuery($sql);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Load the smart group cache for a saved search.
   *
   * @param object $group
   *   The smart group that needs to be loaded.
   * @param bool $force
   *   Should we force a search through.
   *
   * @throws \CRM_Core_Exception
   */
  public static function load(&$group, $force = FALSE) {
    $groupID = $group->id;
    $savedSearchID = $group->saved_search_id;
    if (array_key_exists($groupID, self::$_alreadyLoaded) && !$force) {
      return;
    }

    self::$_alreadyLoaded[$groupID] = 1;

    // FIXME: some other process could have actually done the work before we got here,
    // Ensure that work needs to be done before continuing
    if (!$force && !self::shouldGroupBeRefreshed($groupID, TRUE)) {
      return;
    }

    $customClass = NULL;
    if ($savedSearchID) {
      $ssParams = CRM_Contact_BAO_SavedSearch::getSearchParams($savedSearchID);
      $groupID = CRM_Utils_Type::escape($groupID, 'Integer');

      $excludeClause = "NOT IN (
                        SELECT contact_id FROM civicrm_group_contact
                        WHERE civicrm_group_contact.status = 'Removed'
                        AND civicrm_group_contact.group_id = $groupID )";
      $addSelect = "$groupID AS group_id";

      if (!empty($ssParams['api_entity'])) {
        $sql = self::getApiSQL($ssParams, $addSelect, $excludeClause);
      }
      else {
        // CRM-7021 rectify params to what proximity search expects if there is a value for prox_distance
        if (!empty($ssParams)) {
          CRM_Contact_BAO_ProximityQuery::fixInputParams($ssParams);
        }
        if (isset($ssParams['customSearchID'])) {
          $sql = self::getCustomSearchSQL($savedSearchID, $ssParams, $addSelect, $excludeClause);
        }
        else {
          $sql = self::getQueryObjectSQL($savedSearchID, $ssParams, $addSelect, $excludeClause);
        }
      }
    }

    $groupContactsTempTable = CRM_Utils_SQL_TempTable::build()->setCategory('gccache')->setMemory();
    $tempTable = $groupContactsTempTable->getName();
    $groupContactsTempTable->createWithColumns('contact_id int, group_id int, UNIQUE UI_contact_group (contact_id,group_id)');

    if (!empty($sql)) {
      $contactQueries[] = $sql;
    }
    // lets also store the records that are explicitly added to the group
    // this allows us to skip the group contact LEFT JOIN
    $contactQueries[] =
      "SELECT $groupID as group_id, contact_id as contact_id
       FROM   civicrm_group_contact
       WHERE  civicrm_group_contact.status = 'Added' AND civicrm_group_contact.group_id = $groupID ";

    self::clearGroupContactCache($groupID);

    foreach ($contactQueries as $contactQuery) {
      CRM_Core_DAO::executeQuery("INSERT IGNORE INTO $tempTable (group_id, contact_id) {$contactQuery}");
    }

    if ($group->children) {

      // Store a list of contacts who are removed from the parent group
      $sqlContactsRemovedFromGroup = "
SELECT contact_id
FROM civicrm_group_contact
WHERE  civicrm_group_contact.status = 'Removed'
AND  civicrm_group_contact.group_id = $groupID ";
      $dao = CRM_Core_DAO::executeQuery($sqlContactsRemovedFromGroup);
      $removed_contacts = [];
      while ($dao->fetch()) {
        $removed_contacts[] = $dao->contact_id;
      }

      $childrenIDs = explode(',', $group->children);
      foreach ($childrenIDs as $childID) {
        $contactIDs = CRM_Contact_BAO_Group::getMember($childID, FALSE);
        // Unset each contact that is removed from the parent group
        foreach ($removed_contacts as $removed_contact) {
          unset($contactIDs[$removed_contact]);
        }
        if (empty($contactIDs)) {
          // This child group has no contact IDs so we don't need to add them to
          continue;
        }
        $values = [];
        foreach ($contactIDs as $contactID => $dontCare) {
          $values[] = "({$groupID},{$contactID})";
        }
        $str = implode(',', $values);
        CRM_Core_DAO::executeQuery("INSERT IGNORE INTO $tempTable (group_id, contact_id) VALUES $str");
      }
    }

    // grab a lock so other processes don't compete and do the same query
    $lock = Civi::lockManager()->acquire("data.core.group.{$groupID}");
    if (!$lock->isAcquired()) {
      // this can cause inconsistent results since we don't know if the other process
      // will fill up the cache before our calling routine needs it.
      // however this routine does not return the status either, so basically
      // its a "lets return and hope for the best"
      return;
    }

    // Don't call clearGroupContactCache as we don't want to clear the cache dates
    // The will get updated by updateCacheTime() below and not clearing the dates reduces
    // the chance that loadAll() will try and rebuild at the same time.
    $clearCacheQuery = "
    DELETE  g
      FROM  civicrm_group_contact_cache g
      WHERE  g.group_id = %1 ";
    $params = [
      1 => [$groupID, 'Integer'],
    ];
    CRM_Core_DAO::executeQuery($clearCacheQuery, $params);

    CRM_Core_DAO::executeQuery(
      "INSERT IGNORE INTO civicrm_group_contact_cache (contact_id, group_id)
        SELECT DISTINCT contact_id, group_id FROM $tempTable
      ");
    $groupContactsTempTable->drop();
    self::updateCacheTime([$groupID], TRUE);

    $lock->release();
  }

  /**
   * Retrieve the smart group cache timeout in minutes.
   *
   * This checks if a timeout has been configured. If one has then smart groups should not
   * be refreshed more frequently than the time out. If a group was recently refreshed it should not
   * refresh again within that period.
   *
   * @return int
   */
  public static function smartGroupCacheTimeout() {
    $config = CRM_Core_Config::singleton();

    if (
      isset($config->smartGroupCacheTimeout) &&
      is_numeric($config->smartGroupCacheTimeout)
    ) {
      return $config->smartGroupCacheTimeout;
    }

    // Default to 5 minutes.
    return 5;
  }

  /**
   * Get all the smart groups that this contact belongs to.
   *
   * Note that this could potentially be a super slow function since
   * it ensure that all contact groups are loaded in the cache
   *
   * @param int $contactID
   * @param bool $showHidden
   *   Hidden groups are shown only if this flag is set.
   *
   * @return array
   *   an array of groups that this contact belongs to
   */
  public static function contactGroup($contactID, $showHidden = FALSE) {
    if (empty($contactID)) {
      return NULL;
    }

    if (is_array($contactID)) {
      $contactIDs = $contactID;
    }
    else {
      $contactIDs = [$contactID];
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
    $contactGroup = [];
    $prevContactID = NULL;
    while ($dao->fetch()) {
      if (
        $prevContactID &&
        $prevContactID != $dao->contact_id
      ) {
        $contactGroup[$prevContactID]['groupTitle'] = implode(', ', $contactGroup[$prevContactID]['groupTitle']);
      }
      $prevContactID = $dao->contact_id;
      if (!array_key_exists($dao->contact_id, $contactGroup)) {
        $contactGroup[$dao->contact_id]
          = ['group' => [], 'groupTitle' => []];
      }

      $contactGroup[$dao->contact_id]['group'][]
        = [
          'id' => $dao->group_id,
          'title' => $dao->title,
          'description' => $dao->description,
          'children' => $dao->children,
        ];
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

  /**
   * Get the datetime from which the cache should be considered invalid.
   *
   * Ie if the smartgroup cache timeout is 5 minutes ago then the cache is invalid if it was
   * refreshed 6 minutes ago, but not if it was refreshed 4 minutes ago.
   *
   * @return string
   */
  public static function getCacheInvalidDateTime() {
    return date('YmdHis', strtotime("-" . self::smartGroupCacheTimeout() . " Minutes"));
  }

  /**
   * Get the date when the cache should be refreshed from.
   *
   * Ie. now + the offset & we will delete anything prior to then.
   *
   * @return string
   */
  public static function getRefreshDateTime() {
    return date('YmdHis', strtotime("+ " . self::smartGroupCacheTimeout() . " Minutes"));
  }

  /**
   * Invalidates the smart group cache for a particular group
   * @param int $groupID - Group to invalidate
   */
  public static function invalidateGroupContactCache($groupID) {
    CRM_Core_DAO::executeQuery("UPDATE civicrm_group
      SET cache_date = NULL, refresh_date = NULL
      WHERE id = %1", [
        1 => [$groupID, 'Positive'],
      ]);
  }

  /**
   * @param array $savedSearch
   * @param string $addSelect
   * @param string $excludeClause
   * @return string
   * @throws API_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   * @throws CRM_Core_Exception
   */
  protected static function getApiSQL(array $savedSearch, string $addSelect, string $excludeClause) {
    $apiParams = $savedSearch['api_params'] + ['select' => ['id'], 'checkPermissions' => FALSE];
    $idField = SqlExpression::convert($apiParams['select'][0], TRUE)->getAlias();
    // Unless there's a HAVING clause, we don't care about other columns
    if (empty($apiParams['having'])) {
      $apiParams['select'] = array_slice($apiParams['select'], 0, 1);
    }
    $api = \Civi\API\Request::create($savedSearch['api_entity'], 'get', $apiParams);
    $query = new \Civi\Api4\Query\Api4SelectQuery($api);
    $query->forceSelectId = FALSE;
    $query->getQuery()->having("$idField $excludeClause");
    $sql = $query->getSql();
    // Place sql in a nested sub-query, otherwise HAVING is impossible on any field other than contact_id
    return "SELECT $addSelect, `$idField` AS contact_id FROM ($sql) api_query";
  }

  /**
   * Get sql from a custom search.
   *
   * We split it up and store custom class
   * so temp tables are not destroyed if they are used
   *
   * @param int $savedSearchID
   * @param array $ssParams
   * @param string $addSelect
   * @param string $excludeClause
   *
   * @return string
   * @throws \Exception
   */
  protected static function getCustomSearchSQL($savedSearchID, array $ssParams, string $addSelect, string $excludeClause) {
    $searchSQL = CRM_Contact_BAO_SearchCustom::customClass($ssParams['customSearchID'], $savedSearchID)->contactIDs();
    $searchSQL = str_replace('ORDER BY contact_a.id ASC', '', $searchSQL);
    if (strpos($searchSQL, 'WHERE') === FALSE) {
      $searchSQL .= " WHERE contact_a.id $excludeClause";
    }
    else {
      $searchSQL .= " AND contact_a.id $excludeClause";
    }
    return preg_replace("/^\s*SELECT /", "SELECT $addSelect, ", $searchSQL);
  }

  /**
   * Get array of sql from a saved query object group.
   *
   * @param int $savedSearchID
   * @param array $ssParams
   * @param string $addSelect
   * @param string $excludeClause
   *
   * @return string
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  protected static function getQueryObjectSQL($savedSearchID, array $ssParams, string $addSelect, string $excludeClause) {
    $returnProperties = NULL;
    if (CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch', $savedSearchID, 'mapping_id')) {
      $fv = CRM_Contact_BAO_SavedSearch::getFormValues($savedSearchID);
      $returnProperties = CRM_Core_BAO_Mapping::returnProperties($fv);
    }
    $formValues = CRM_Contact_BAO_SavedSearch::getFormValues($savedSearchID);
    // CRM-17075 using the formValues in this way imposes extra logic and complexity.
    // we have the where_clause and where tables stored in the saved_search table
    // and should use these rather than re-processing the form criteria (which over-works
    // the link between the form layer & the query layer too).
    // It's hard to think of when you would want to use anything other than return
    // properties = array('contact_id' => 1) here as the point would appear to be to
    // generate the list of contact ids in the group.
    // @todo review this to use values in saved_search table (preferably for 4.8).
    $query
      = new CRM_Contact_BAO_Query(
      $ssParams, $returnProperties, NULL,
      FALSE, FALSE, 1,
      TRUE, TRUE,
      FALSE,
      $formValues['display_relationship_type'] ?? NULL,
      $formValues['operator'] ?? 'AND'
    );
    $query->_useDistinct = FALSE;
    $query->_useGroupBy = FALSE;
    $sqlParts = $query->getSearchSQLParts(
      0, 0, NULL,
      FALSE, FALSE,
      FALSE, TRUE,
      "contact_a.id $excludeClause"
    );
    $select = preg_replace("/^\s*SELECT /", "SELECT $addSelect, ", $sqlParts['select']);

    return "$select {$sqlParts['from']} {$sqlParts['where']} {$sqlParts['group_by']} {$sqlParts['having']}";
  }

}
