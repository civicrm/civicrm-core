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

use Civi\API\Request;
use Civi\Api4\Group;
use Civi\Api4\Query\Api4SelectQuery;
use Civi\Api4\Query\SqlExpression;
use Civi\Api4\SavedSearch;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contact_BAO_GroupContactCache extends CRM_Contact_DAO_GroupContactCache {

  /**
   * Get a list of caching modes.
   *
   * @return array
   */
  public static function getModes(): array {
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
  public static function check($groupIDs): bool {
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
  protected static function groupRefreshedClause($groupIDClause = NULL, $includeHiddenGroups = FALSE): string {
    $smartGroupCacheTimeoutDateTime = self::getCacheInvalidDateTime();

    $query = "
SELECT  g.id
FROM    civicrm_group g
WHERE   ( g.saved_search_id IS NOT NULL OR g.children IS NOT NULL )
AND     g.is_active = 1
AND (
  g.cache_date IS NULL
  OR cache_date <= $smartGroupCacheTimeoutDateTime
)";

    if (!$includeHiddenGroups) {
      $query .= "AND g.is_hidden = 0";
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
   *
   * @return bool
   */
  public static function shouldGroupBeRefreshed($groupID): bool {
    $query = self::groupRefreshedClause('g.id = %1');
    $params = [1 => [$groupID, 'Integer']];

    // if the query returns the group ID, it means the group is a valid candidate for refreshing
    return (bool) CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * Check to see if we have cache entries for this group.
   *
   * if not, regenerate, else return
   *
   * @param array|null $groupIDs groupIDs of group that we are checking against
   *                           if empty, all groups are checked
   * @param int $limit
   *   Limits the number of groups we evaluate.
   *
   * @return bool
   *   TRUE if we did not regenerate, FALSE if we did
   */
  public static function loadAll($groupIDs = NULL, $limit = 0) {
    if ($groupIDs) {
      // Passing a single value is deprecated.
      $groupIDs = (array) $groupIDs;
    }

    // Treat the default help text in Scheduled Jobs as equivalent to no limit.
    $limit = (int) $limit;
    $processGroupIDs = self::getGroupsNeedingRefreshing($groupIDs, $limit);

    if (!empty($processGroupIDs)) {
      self::add($processGroupIDs);
    }
    return TRUE;
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
      $params = [['group', 'IN', [$groupID], 0, 0]];
      // the below call updates the cache table as a byproduct of the query
      CRM_Contact_BAO_Query::apiQuery($params, ['contact_id'], NULL, NULL, 0, 0, FALSE);
    }
  }

  /**
   * Change the cache_date.
   *
   * @param array $groupID
   * @param bool $processed
   *   Whether the cache data was recently modified.
   * @param ?float $startTime
   *   A float from microtime() for when we began work updating this group.
   */
  protected static function updateCacheTime($groupID, $processed, ?float $startTime = NULL) {
    // only update cache entry if we had any values
    $now = 'null';
    $setCacheFillTook = '';
    if ($processed) {
      // also update the group with cache date information
      $now = date('YmdHis');
      if ($startTime) {
        // If we can calculate how long this took, we update the cache_fill_took column.
        $took = microtime(TRUE) - $startTime;
        $setCacheFillTook = ", cache_fill_took = $took";
        $maxTime = CRM_Utils_Constant::value('CIVICRM_SLOW_SMART_GROUP_SECONDS');
        if ($maxTime && $took > $maxTime) {
          Civi::log()->warning(
            "Updating smart group $groupID took over "
            . $maxTime
            . "s defined by CIVICRM_SLOW_SMART_GROUP_SECONDS (took " . number_format($took, 3) . "s)"
          );
        }
      }
    }

    $groupIDs = implode(',', $groupID);
    $sql = "
UPDATE civicrm_group
SET    cache_date = $now$setCacheFillTook
WHERE  id IN ( $groupIDs )
";
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Function to clear group contact cache
   *
   * @param array $groupIDs
   *
   */
  protected static function clearGroupContactCache($groupIDs): void {
    $clearCacheQuery = '
    DELETE  g
      FROM  civicrm_group_contact_cache g
      WHERE  g.group_id IN (%1) ';
    $params = [
      1 => [implode(',', $groupIDs), 'CommaSeparatedIntegers'],
    ];
    CRM_Core_DAO::executeQuery($clearCacheQuery, $params);
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
    if (!CRM_Core_Config::isPermitCacheFlushMode()) {
      return;
    }

    try {
      $lock = self::getLockForRefresh();
    }
    catch (CRM_Core_Exception $e) {
      // Someone else is kindly doing the refresh for us right now.
      return;
    }

    // Get the list of expired smart groups that may need flushing
    $params = [1 => [self::getCacheInvalidDateTime(), 'String']];
    $groupsThatMayNeedToBeFlushedSQL = "SELECT id FROM civicrm_group WHERE (saved_search_id IS NOT NULL OR children <> '') AND (cache_date <= %1 OR cache_date IS NULL)";
    $groupsDAO = CRM_Core_DAO::executeQuery($groupsThatMayNeedToBeFlushedSQL, $params);
    $expiredGroups = [];
    while ($groupsDAO->fetch()) {
      $expiredGroups[] = $groupsDAO->id;
    }
    if (empty($expiredGroups)) {
      // There are no expired smart groups to flush
      return;
    }

    $expiredGroupsCSV = implode(',', $expiredGroups);
    $flushSQLParams = [1 => [$expiredGroupsCSV, 'CommaSeparatedIntegers']];
    // Now check if we actually have any entries in the smart groups to flush
    $groupsHaveEntriesToFlushSQL = 'SELECT group_id FROM civicrm_group_contact_cache gc WHERE group_id IN (%1) LIMIT 1';
    $groupsHaveEntriesToFlush = (bool) CRM_Core_DAO::singleValueQuery($groupsHaveEntriesToFlushSQL, $flushSQLParams);

    if ($groupsHaveEntriesToFlush) {
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_group_contact_cache WHERE group_id IN (%1)", [1 => [$expiredGroupsCSV, 'CommaSeparatedIntegers']]);

      // Clear these out without resetting them because we are not building caches here, only clearing them,
      // so the state is 'as if they had never been built'.
      CRM_Core_DAO::executeQuery("UPDATE civicrm_group SET cache_date = NULL WHERE id IN (%1)", [1 => [$expiredGroupsCSV, 'CommaSeparatedIntegers']]);
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
  public static function opportunisticCacheFlush(): void {
    if (Civi::settings()->get('smart_group_cache_refresh_mode') === 'opportunistic') {
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
      CRM_Core_DAO::executeQuery("UPDATE civicrm_group SET cache_date = NULL");
    }
    else {
      self::flushCaches();
    }
  }

  /**
   * Remove one or more contacts from the smart group cache.
   *
   * @param int|array $cid
   * @param int|null $groupId
   *
   * @return bool
   *   TRUE if successful.
   * @throws \CRM_Core_Exception
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
   * @param CRM_Core_DAO $group
   *   The smart group that needs to be loaded.
   * @param bool $force
   *   deprecated parameter = Should we force a search through.
   *
   * return bool
   * @throws \CRM_Core_Exception
   */
  public static function load($group, $force = FALSE) {
    $groupID = (int) $group->id;
    if ($force) {
      CRM_Core_Error::deprecatedWarning('use invalidate group contact cache first.');
      self::invalidateGroupContactCache($group->id);
    }

    $lockedGroups = self::getLocksForRefreshableGroupsTo([$groupID]);
    foreach ($lockedGroups as $groupID) {
      $startTime = microtime(TRUE);
      $groupContactsTempTable = CRM_Utils_SQL_TempTable::build()
        ->setCategory('gccache')
        ->setMemory();
      self::buildGroupContactTempTable([$groupID], $groupContactsTempTable);
      self::clearGroupContactCache([$groupID]);
      self::updateCacheFromTempTable($groupContactsTempTable, [$groupID], $startTime);
      self::releaseGroupLocks([$groupID]);
      $groupContactsTempTable->drop();

    }
    return in_array($groupID, $lockedGroups);
  }

  /**
   * Get an array of locks for all the refreshable groups in the array.
   *
   * The groups are refreshable if both the following conditions are met:
   * 1) the cache date in the database is null or stale
   * 2) a mysql lock can be acquired for the group.
   *
   * @param array $groupIDs
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected static function getLocksForRefreshableGroupsTo(array $groupIDs): array {
    $locks = [];
    $groupIDs = self::getGroupsNeedingRefreshing($groupIDs);
    foreach ($groupIDs as $groupID) {
      if (self::getGroupLock($groupID)) {
        $locks[] = $groupID;
      }
    }
    return $locks;
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
  protected static function smartGroupCacheTimeout() {
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
   *
   * @return array
   *   an array of groups that this contact belongs to
   */
  public static function contactGroup(int $contactID): array {

    self::loadAll();

    $sql = "
SELECT     gc.group_id, gc.contact_id, g.title, g.children, g.description
FROM       civicrm_group_contact_cache gc
INNER JOIN civicrm_group g ON g.id = gc.group_id
WHERE      gc.contact_id = $contactID
            AND g.is_hidden = 0
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

    if ((!empty($contactGroup[$contactID]))) {
      return $contactGroup[$contactID];
    }
    return $contactGroup;
  }

  /**
   * Get the datetime from which the cache should be considered invalid.
   *
   * Ie if the smartgroup cache timeout is 5 minutes ago then the cache is invalid if it was
   * refreshed 6 minutes ago, but not if it was refreshed 4 minutes ago.
   *
   * @return string
   */
  public static function getCacheInvalidDateTime(): string {
    return date('YmdHis', strtotime("-" . self::smartGroupCacheTimeout() . " Minutes"));
  }

  /**
   * Invalidates the smart group cache for one or more groups
   * @param int|int[] $groupID - Group to invalidate
   */
  public static function invalidateGroupContactCache($groupID): void {
    $groupIDs = implode(',', (array) $groupID);
    CRM_Core_DAO::executeQuery('UPDATE civicrm_group
      SET cache_date = NULL
      WHERE id IN (%1) AND (saved_search_id IS NOT NULL OR children IS NOT NULL)', [
        1 => [$groupIDs, 'CommaSeparatedIntegers'],
      ]);
  }

  /**
   * @param array $savedSearch
   * @param int $groupID
   *
   * @return string
   * @throws CRM_Core_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   * @throws CRM_Core_Exception
   */
  protected static function getApiSQL(array $savedSearch, int $groupID): string {
    $excludeClause = "NOT IN (
                        SELECT contact_id FROM civicrm_group_contact
                        WHERE civicrm_group_contact.status = 'Removed'
                        AND civicrm_group_contact.group_id = $groupID )";
    $addSelect = "$groupID AS group_id";

    $apiParams = $savedSearch['api_params'] + ['select' => ['id'], 'checkPermissions' => FALSE];
    $idField = SqlExpression::convert($apiParams['select'][0], TRUE)->getAlias();
    // Unless there's a HAVING clause, we don't care about other columns
    if (empty($apiParams['having'])) {
      $apiParams['select'] = array_slice($apiParams['select'], 0, 1);
    }
    // Order is irrelevant unless using limit or offset
    if (empty($apiParams['limit']) && empty($apiParams['offset'])) {
      unset($apiParams['orderBy']);
    }
    /** @var \Civi\Api4\Generic\DAOGetAction $api */
    $api = Request::create($savedSearch['api_entity'], 'get', $apiParams);
    $query = new Api4SelectQuery($api);
    $query->forceSelectId = FALSE;
    $query->getQuery()->having("$idField $excludeClause");
    $sql = $query->getSql();
    // Place sql in a nested sub-query, otherwise HAVING is impossible on any field other than contact_id
    return "SELECT $addSelect, `$idField` AS contact_id FROM ($sql) api_query";
  }

  /**
   * Get array of sql from a saved query object group.
   *
   * @param array $savedSearch
   * @param int $groupID
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  protected static function getQueryObjectSQL(array $savedSearch, int $groupID): string {
    $savedSearchID = $savedSearch['id'];
    $excludeClause = "NOT IN (
                        SELECT contact_id FROM civicrm_group_contact
                        WHERE civicrm_group_contact.status = 'Removed'
                        AND civicrm_group_contact.group_id = $groupID )";
    $addSelect = "$groupID AS group_id";
    $fv = CRM_Contact_BAO_SavedSearch::getFormValues($savedSearchID);
    //check if the saved search has mapping id
    if ($savedSearch['mapping_id']) {
      $ssParams = CRM_Core_BAO_Mapping::formattedFields($fv);
    }
    else {
      $ssParams = CRM_Contact_BAO_Query::convertFormValues($fv);
    }
    // CRM-7021 rectify params to what proximity search expects if there is a value for prox_distance
    if (!empty($ssParams)) {
      CRM_Contact_BAO_ProximityQuery::fixInputParams($ssParams);
    }

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

  /**
   * Build a temporary table for the contacts in the specified group.
   *
   * @param array $groupIDs
   *   Currently only one id is build but this has been written
   *   to make it easy to switch to multiple.
   * @param CRM_Utils_SQL_TempTable $tempTableObject
   *
   * @throws \CRM_Core_Exception
   */
  protected static function buildGroupContactTempTable(array $groupIDs, $tempTableObject): void {
    $groups = Group::get(FALSE)->addWhere('id', 'IN', $groupIDs)
      ->setSelect(['saved_search_id', 'children', 'id'])->execute();
    $tempTableName = $tempTableObject->getName();
    $tempTableObject->createWithColumns('contact_id int, group_id int, UNIQUE UI_contact_group (contact_id,group_id)');
    foreach ($groups as $group) {
      self::insertGroupContactsIntoTempTable($tempTableName, $group['id'], $group['saved_search_id'], $group['children']);
    }
  }

  /**
   * [Internal core function] Populate a temporary table with group ids and contact ids.
   *
   * Do not call this outside of core tested code - it WILL change.
   *
   * @param int[] $groupIDs
   * @param string $temporaryTable
   *
   * @throws \CRM_Core_Exception
   */
  public static function populateTemporaryTableWithContactsInGroups(array $groupIDs, string $temporaryTable): void {
    $childAndParentGroupIDs = array_merge($groupIDs, CRM_Contact_BAO_GroupNesting::getDescendentGroupIds($groupIDs));
    $groups = civicrm_api3('Group', 'get', [
      'is_active' => 1,
      'id' => ['IN' => $childAndParentGroupIDs],
      'saved_search_id' => ['>' => 0],
      'return' => 'id',
    ]);
    $smartGroups = array_keys($groups['values']);

    $query = '
       SELECT DISTINCT group_contact.contact_id as contact_id
       FROM civicrm_group_contact group_contact
       WHERE group_contact.group_id IN (' . implode(', ', $childAndParentGroupIDs) . ")
       AND group_contact.status = 'Added' ";

    if (!empty($smartGroups)) {
      $groupContactsTempTable = CRM_Utils_SQL_TempTable::build()
        ->setCategory('gccache')
        ->setMemory();
      $lockedGroups = self::getLocksForRefreshableGroupsTo($smartGroups);
      if (!empty($lockedGroups)) {
        self::buildGroupContactTempTable($lockedGroups, $groupContactsTempTable);
        // Note in theory we could do this transfer from the temp
        // table to the group_contact_cache table out-of-process - possibly by
        // continuing on after the browser is released (which seems to be
        // possibly possible https://stackoverflow.com/questions/15273570/continue-processing-php-after-sending-http-response
        // or by making the table durable and using a cron to process it (or an ajax call
        // at the end to process out of the queue.
        // if we did that we would union in DISTINCT contact_id FROM
        // $groupContactsTempTable->getName()
        // but still use the last union for array_diff_key($smartGroups, $locks)
        // as that would hold the already-cached groups (if any).
        // Also - if we switched to the 'triple union' approach described above
        // we could throw a try-catch around this line since best-effort would
        // be good enough & potentially improve user experience.
        self::clearGroupContactCache($lockedGroups);
        self::updateCacheFromTempTable($groupContactsTempTable, $lockedGroups);
        self::releaseGroupLocks($lockedGroups);
        $groupContactsTempTable->drop();
      }

      $smartGroups = implode(',', $smartGroups);
      $query .= "
        UNION DISTINCT
        SELECT smartgroup_contact.contact_id as contact_id
        FROM civicrm_group_contact_cache smartgroup_contact
        WHERE smartgroup_contact.group_id IN ({$smartGroups}) ";
    }
    CRM_Core_DAO::executeQuery('INSERT INTO ' . $temporaryTable . ' ' . $query);
  }

  /**
   * @param array|null $groupIDs
   * @param int $limit
   *
   * @return array
   */
  protected static function getGroupsNeedingRefreshing(?array $groupIDs, int $limit = 0): array {
    $groupIDClause = NULL;
    // ensure that all the smart groups are loaded
    // this function is expensive and should be sparingly used if groupIDs is empty
    if (!empty($groupIDs)) {
      // note escapeString is a must here and we can't send the imploded value as second argument to
      // the executeQuery(), since that would put single quote around the string and such a string
      // of comma separated integers would not work.
      $groupIDString = CRM_Core_DAO::escapeString(implode(', ', $groupIDs));
      $groupIDClause = "g.id IN ({$groupIDString})";
    }

    $query = self::groupRefreshedClause($groupIDClause, !empty($groupIDs));

    $limitClause = $orderClause = NULL;
    if ($limit > 0) {
      $limitClause = " LIMIT 0, $limit";
      $orderClause = " ORDER BY g.cache_date";
    }
    // We ignore hidden groups and disabled groups
    $query .= "
        $orderClause
        $limitClause
";

    $dao = CRM_Core_DAO::executeQuery($query);
    $processGroupIDs = [];
    while ($dao->fetch()) {
      $processGroupIDs[] = $dao->id;
    }
    return $processGroupIDs;
  }

  /**
   * Transfer the contact ids to the group cache table and update the cache time.
   *
   * @param \CRM_Utils_SQL_TempTable $groupContactsTempTable
   * @param array $groupIDs
   * @param ?float $startTime
   *   A float from microtime() for when we began work updating this group.
   */
  private static function updateCacheFromTempTable(CRM_Utils_SQL_TempTable $groupContactsTempTable, array $groupIDs, ?float $startTime = NULL): void {
    $tempTable = $groupContactsTempTable->getName();

    // @fixme: GROUP BY is here to guard against having duplicate contacts in the temptable.
    //   That used to happen for an unknown reason and probably doesn't anymore so we *should*
    //   be able to remove GROUP BY here safely.
    CRM_Core_DAO::executeQuery(
      "INSERT INTO civicrm_group_contact_cache (contact_id, group_id)
        SELECT contact_id, group_id FROM $tempTable
        GROUP BY contact_id,group_id
      ");
    foreach ($groupIDs as $groupID) {
      self::updateCacheTime([$groupID], TRUE, $startTime);
    }
  }

  /**
   * Inserts all the contacts in the group into a temp table.
   *
   * This is the worker function for building the list of contacts in the
   * group.
   *
   * @param string $tempTableName
   * @param int $groupID
   * @param int|null $savedSearchID
   * @param int[]|null $children
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  protected static function insertGroupContactsIntoTempTable(string $tempTableName, int $groupID, ?int $savedSearchID, ?array $children): void {
    if ($savedSearchID) {
      $savedSearch = SavedSearch::get(FALSE)
        ->addWhere('id', '=', $savedSearchID)
        ->addSelect('*')
        ->execute()
        ->first();

      $sql = '';
      CRM_Utils_Hook::buildGroupContactCache($savedSearch, $groupID, $sql);
      if (!$sql) {
        if ($savedSearch['api_entity']) {
          $sql = self::getApiSQL($savedSearch, $groupID);
        }
        elseif (!empty($savedSearch['search_custom_id'])) {
          Group::update(FALSE)->addWhere('id', '=', $groupID)->setValues(['is_active' => FALSE])->execute();
          CRM_Core_Session::setStatus(ts('Invalid group %1 found and disabled'), [1 => $groupID]);
          return;
        }
        else {
          $sql = self::getQueryObjectSQL($savedSearch, $groupID);
        }
      }
    }

    if (!empty($sql)) {
      $contactQueries[] = $sql;
    }
    // lets also store the records that are explicitly added to the group
    // this allows us to skip the group contact LEFT JOIN
    $contactQueries[] =
      "SELECT $groupID as group_id, contact_id as contact_id
       FROM   civicrm_group_contact
       WHERE  civicrm_group_contact.status = 'Added' AND civicrm_group_contact.group_id = $groupID ";

    foreach ($contactQueries as $contactQuery) {
      CRM_Core_DAO::executeQuery("INSERT IGNORE INTO $tempTableName (group_id, contact_id) {$contactQuery}");
    }

    CRM_Core_DAO::reenableFullGroupByMode();

    if ($children) {

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

      foreach ($children as $childID) {
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
        CRM_Core_DAO::executeQuery("INSERT IGNORE INTO $tempTableName (group_id, contact_id) VALUES $str");
      }
    }
  }

  /**
   * Get a lock, if available, for the given group.
   *
   * @param int $groupID
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  protected static function getGroupLock(int $groupID): bool {
    $cacheKey = "data.core.group.$groupID";
    if (isset(Civi::$statics["data.core.group.$groupID"])) {
      // Loop avoidance for a circular parent-child situation.
      // This would occur where the parent is a criteria of the child
      // but needs to resolve the child to resolve itself.
      // This has a unit test - testGroupWithParentInCriteria
      return FALSE;
    }
    $lock = Civi::lockManager()->acquire($cacheKey);
    if ($lock->isAcquired()) {
      Civi::$statics["data.core.group.$groupID"] = $lock;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Release locks on the groups.
   *
   * @param array $groupIDs
   */
  protected static function releaseGroupLocks(array $groupIDs): void {
    foreach ($groupIDs as $groupID) {
      $lock = Civi::$statics["data.core.group.$groupID"];
      $lock->release();
      unset(Civi::$statics["data.core.group.$groupID"]);
    }
  }

}
