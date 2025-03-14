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
class CRM_Contact_BAO_Contact_Permission {

  /**
   * @var bool
   *
   * @deprecated
   */
  public static $useTempTable = FALSE;

  /**
   * Set whether to use a temporary table or not when building ACL Cache
   * @param bool $useTemporaryTable
   *
   * @deprecated
   */
  public static function setUseTemporaryTable($useTemporaryTable = TRUE) {
    self::$useTempTable = $useTemporaryTable;
  }

  /**
   * Get variable for determining if we should use Temporary Table or not
   *
   * @return bool
   *
   * @deprecated
   */
  public static function getUseTemporaryTable() {
    return self::$useTempTable;
  }

  /**
   * Check which of the given contact IDs the logged in user
   *   has permissions for the operation type according to:
   *    - general permissions (e.g. 'edit all contacts')
   *    - deletion status (unless you have 'access deleted contacts')
   *    - ACL
   *    - permissions inherited through relationships (also second degree if enabled)
   *
   * @param array $contact_ids
   *   Contact IDs.
   * @param int $type the type of operation (view|edit)
   *
   * @see CRM_Contact_BAO_Contact_Permission::allow
   *
   * @return array
   *   list of contact IDs the logged in user has the given permission for
   */
  public static function allowList($contact_ids, $type = CRM_Core_Permission::VIEW) {
    $result_set = [];
    if (empty($contact_ids)) {
      // empty contact lists would cause trouble in the SQL. And be pointless.
      return $result_set;
    }

    // make sure the the general permissions are given
    if (CRM_Core_Permission::check('edit all contacts')
        || $type == CRM_Core_Permission::VIEW && CRM_Core_Permission::check('view all contacts')
      ) {

      // if the general permission is there, all good
      if (CRM_Core_Permission::check('access deleted contacts')) {
        // if user can access deleted contacts -> fine
        return $contact_ids;
      }
      else {
        // if the user CANNOT access deleted contacts, these need to be filtered
        $contact_id_list = implode(',', $contact_ids);
        $filter_query = "SELECT DISTINCT(id) FROM civicrm_contact WHERE id IN ($contact_id_list) AND is_deleted = 0";
        $query = CRM_Core_DAO::executeQuery($filter_query);
        while ($query->fetch()) {
          $result_set[(int) $query->id] = TRUE;
        }
        return array_keys($result_set);
      }
    }

    // get logged in user
    $contactID = CRM_Core_Session::getLoggedInContactID();
    if (empty($contactID)) {
      return [];
    }

    // make sure the cache is filled
    self::cache($contactID, $type);

    // compile query
    $operation = ($type == CRM_Core_Permission::VIEW) ? 'View' : 'Edit';

    // add clause for deleted contacts, if the user doesn't have the permission to access them
    $LEFT_JOIN_DELETED = $AND_CAN_ACCESS_DELETED = '';
    if (!CRM_Core_Permission::check('access deleted contacts')) {
      $LEFT_JOIN_DELETED      = "LEFT JOIN civicrm_contact ON civicrm_contact.id = contact_id";
      $AND_CAN_ACCESS_DELETED = "AND civicrm_contact.is_deleted = 0";
    }

    // RUN the query
    $contact_id_list = implode(',', $contact_ids);
    $query = "
SELECT contact_id
 FROM civicrm_acl_contact_cache
 {$LEFT_JOIN_DELETED}
WHERE contact_id IN ({$contact_id_list})
  AND user_id = {$contactID}
  AND operation = '{$operation}'
  {$AND_CAN_ACCESS_DELETED}";
    $result = CRM_Core_DAO::executeQuery($query);
    while ($result->fetch()) {
      $result_set[(int) $result->contact_id] = TRUE;
    }

    // if some have been rejected, double check for permissions inherited by relationship
    if (count($result_set) < count($contact_ids)) {
      $rejected_contacts = array_diff_key($contact_ids, $result_set);
      // @todo consider storing these to the acl cache for next time, since we have fetched.
      $allowed_by_relationship = self::relationshipList($rejected_contacts, $type);
      foreach ($allowed_by_relationship as $contact_id) {
        $result_set[(int) $contact_id] = TRUE;
      }
    }

    return array_keys($result_set);
  }

  /**
   * Check if the logged in user has permissions for the operation type.
   *
   * @param int $id
   *   Contact id.
   * @param int|string $type the type of operation (view|edit)
   * @param int $userID
   *   Contact id of user to check (defaults to current logged-in user)
   *
   * @return bool
   *   true if the user has permission, false otherwise
   */
  public static function allow($id, $type = CRM_Core_Permission::VIEW, $userID = NULL) {
    // Default to logged in user if not supplied
    $userID ??= CRM_Core_Session::getLoggedInContactID();

    // first: check if contact is trying to view own contact
    if ($userID == $id && ($type == CRM_Core_Permission::VIEW && CRM_Core_Permission::check('view my contact')
     || $type == CRM_Core_Permission::EDIT && CRM_Core_Permission::check('edit my contact', $userID))
      ) {
      return TRUE;
    }

    // FIXME: push this somewhere below, to not give this permission so many rights
    $isDeleted = (bool) CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $id, 'is_deleted');
    if (CRM_Core_Permission::check('access deleted contacts', $userID) && $isDeleted) {
      return TRUE;
    }

    // short circuit for admin rights here so we avoid unneeeded queries
    // some duplication of code, but we skip 3-5 queries
    if (CRM_Core_Permission::check('edit all contacts', $userID) ||
      ($type == CRM_Core_Permission::VIEW && CRM_Core_Permission::check('view all contacts', $userID))
    ) {
      return TRUE;
    }

    // check permission based on relationship, CRM-2963
    if (self::relationshipList([$id], $type, $userID)) {
      return TRUE;
    }

    // We should probably do a cheap check whether it's in the cache first.
    // check permission based on ACL
    $tables = [];
    $whereTables = [];

    $permission = CRM_ACL_API::whereClause($type, $tables, $whereTables, $userID, FALSE, FALSE, TRUE);
    $from = CRM_Contact_BAO_Query::fromClause($whereTables);

    $query = "
SELECT contact_a.id
       $from
WHERE contact_a.id = %1 AND $permission
  LIMIT 1
";

    return (bool) CRM_Core_DAO::singleValueQuery($query, [1 => [$id, 'Integer']]);
  }

  /**
   * Fill the acl contact cache for this ACLed contact id if empty.
   *
   * @param int $userID - contact_id of the ACLed user
   * @param int|string $type the type of operation (view|edit)
   * @param bool $force - Should we force a recompute (only used for unit tests)
   *
   */
  public static function cache($userID, $type = CRM_Core_Permission::VIEW, $force = FALSE) {
    // FIXME: maybe find a better way of keeping track of this. @eileen pointed out
    //   that somebody might flush the cache away from under our feet,
    //   but the alternative would be a SQL call every time this is called,
    //   and a complete rebuild if the result was an empty set...
    $currentDomainID = CRM_Core_Config::domainID();
    if (!isset(Civi::$statics[__CLASS__]['processed'])) {
      Civi::$statics[__CLASS__]['processed'] = [
        $currentDomainID => [
          CRM_Core_Permission::VIEW => [],
          CRM_Core_Permission::EDIT => [],
        ],
      ];
    }
    elseif (!isset(Civi::$statics[__CLASS__]['processed'][$currentDomainID])) {
      Civi::$statics[__CLASS__]['processed'][$currentDomainID] = [
        CRM_Core_Permission::VIEW => [],
        CRM_Core_Permission::EDIT => [],
      ];
    }

    if ($type == CRM_Core_Permission::VIEW) {
      $operationClause = " operation IN ( 'Edit', 'View' ) ";
      $operation = 'View';
    }
    else {
      $operationClause = " operation = 'Edit' ";
      $operation = 'Edit';
    }
    $queryParams = [1 => [$userID, 'Integer'], 2 => [$currentDomainID, 'Integer']];

    if (!$force) {
      // skip if already calculated
      if (!empty(Civi::$statics[__CLASS__]['processed'][$currentDomainID][$type][$userID])) {
        // \Civi::log()->debug("CRM_Contact_BAO_Contact_Permission::cache already called. Operation: $operation; UserID: $userID");
        return;
      }
    }

    // grab a lock so other processes don't compete and do the same query
    $lock = Civi::lockManager()->acquire("data.core.aclcontact.{$userID}");
    if (!$lock->isAcquired()) {
      // this can cause inconsistent results since we don"t know if the other process
      // will fill up the cache before our calling routine needs it.
      // The default 3 second timeout should be enough for the other process to finish.
      // However this routine does not return the status either, so basically
      // its a "lets return and hope for the best"
      // \Civi::log()->debug("cache: aclcontact lock not acquired for user: $userID");
      return;
    }

    if (!$force) {
      // Check if the cache has already been built for this userID
      // The lock guards against simultaneous building of the cache but we don't clear individual userIDs from the cache,
      //   instead we truncate the whole table before calling cache() which may then be called multiple times.
      // The only way we get to this point with the cache already filled is if two processes call cache() almost simultaneously
      //   and the lock completes before the next process reaches the "get lock" call.
      $sql = "
SELECT count(*)
FROM   civicrm_acl_contact_cache
WHERE  user_id = %1
AND    $operationClause
AND domain_id = %2
";
      $count = CRM_Core_DAO::singleValueQuery($sql, $queryParams);
      if ($count > 0) {
        Civi::$statics[__CLASS__]['processed'][$currentDomainID][$type][$userID] = 1;
        $lock->release();
        // \Civi::log()->debug("CRM_Contact_BAO_Contact_Permission::cache already called via check query. Operation: $operation; UserID: $userID");
        return;
      }
    }

    // \Civi::log()->debug("cache: building for $userID; operation=$operation; force=$force");

    $tables = [];
    $whereTables = [];

    $permission = CRM_ACL_API::whereClause($type, $tables, $whereTables, $userID, FALSE, FALSE, TRUE);

    $from = CRM_Contact_BAO_Query::fromClause($whereTables);
    /* Ends up something like this:
    CREATE TEMPORARY TABLE civicrm_temp_acl_contact_cache1310 (SELECT DISTINCT 2960 as user_id, contact_a.id as contact_id, 'View' as operation
    FROM civicrm_contact contact_a  LEFT JOIN civicrm_group_contact_cache `civicrm_group_contact_cache-ACL` ON contact_a.id = `civicrm_group_contact_cache-ACL`.contact_id
    LEFT JOIN civicrm_acl_contact_cache ac ON ac.user_id = 2960 AND ac.contact_id = contact_a.id AND ac.operation = 'View'
    WHERE     ( `civicrm_group_contact_cache-ACL`.group_id IN (14, 25, 46, 47, 48, 49, 50, 51) )  AND (contact_a.is_deleted = 0)
    AND ac.user_id IS NULL*/
    /*$sql = "SELECT DISTINCT $userID as user_id, contact_a.id as contact_id, '{$operation}' as operation
    $from
    LEFT JOIN civicrm_acl_contact_cache ac ON ac.user_id = $userID AND ac.contact_id = contact_a.id AND ac.operation = '{$operation}'
    WHERE    $permission
    AND ac.user_id IS NULL
    ";*/
    $sql = " $from WHERE    $permission";
    $sql = "SELECT $userID as user_id, contact_a.id as contact_id, '{$operation}' as operation, '{$currentDomainID}' as domain_id" . $sql . ' GROUP BY contact_a.id';
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_acl_contact_cache (user_id, contact_id, operation, domain_id) {$sql}");

    // Add in a row for the logged in contact. Do not try to combine with the above query or an ugly OR will appear in
    // the permission clause.
    if ($userID && (CRM_Core_Permission::check('edit my contact') ||
      ($type == CRM_Core_Permission::VIEW && CRM_Core_Permission::check('view my contact')))) {
      $queryParams[3] = [$operation, 'String'];
      if (!CRM_Core_DAO::singleValueQuery("
        SELECT count(*) FROM civicrm_acl_contact_cache WHERE user_id = %1 AND contact_id = %1 AND operation = %3 AND domain_id = %2 LIMIT 1", $queryParams)) {
        CRM_Core_DAO::executeQuery("INSERT INTO civicrm_acl_contact_cache ( user_id, contact_id, operation, domain_id ) VALUES(%1, %1, %3, %2)", $queryParams);
      }
    }
    Civi::$statics[__CLASS__]['processed'][$currentDomainID][$type][$userID] = 1;
    $lock->release();
  }

  /**
   * @param string[]|string $contactAlias
   *
   * @return array
   */
  public static function cacheClause($contactAlias = 'contact_a') {
    if (CRM_Core_Permission::check('view all contacts') ||
      CRM_Core_Permission::check('edit all contacts')
    ) {
      if (!CRM_Core_Permission::check('access deleted contacts')) {
        $wheres = [];
        foreach ((array) $contactAlias as $alias) {
          // CRM-6181
          $wheres[] = "$alias.is_deleted = 0";
        }
        return [NULL, '(' . implode(' AND ', $wheres) . ')'];
      }
      else {
        return [NULL, '( 1 )'];
      }
    }

    $contactID = (int) CRM_Core_Session::getLoggedInContactID();
    self::cache($contactID);
    $domainID = CRM_Core_Config::domainID();

    if (is_array($contactAlias) && !empty($contactAlias)) {
      //More than one contact alias
      $clauses = [];
      foreach ($contactAlias as $k => $alias) {
        $clauses[] = " INNER JOIN civicrm_acl_contact_cache aclContactCache_{$k} ON {$alias}.id = aclContactCache_{$k}.contact_id AND aclContactCache_{$k}.user_id = $contactID AND aclContactCache_{$k}.domain_id = {$domainID} ";
      }

      $fromClause = implode(" ", $clauses);
      $whereClause = NULL;
    }
    else {
      $fromClause = " INNER JOIN civicrm_acl_contact_cache aclContactCache ON {$contactAlias}.id = aclContactCache.contact_id ";
      $whereClause = " aclContactCache.user_id = $contactID AND aclContactCache.domain_id = {$domainID}";
      if (!CRM_Core_Permission::check('access deleted contacts')) {
        $whereClause .= " AND $contactAlias.is_deleted = 0";
      }
    }

    return [$fromClause, $whereClause];
  }

  /**
   * Generate acl subquery that can be placed in the WHERE clause of a query or the ON clause of a JOIN.
   *
   * This is specifically for VIEW operations.
   *
   * @return string|null
   */
  public static function cacheSubquery() {
    if (!CRM_Core_Permission::check([['view all contacts', 'edit all contacts']])) {
      $contactID = (int) CRM_Core_Session::getLoggedInContactID();
      $domainID = CRM_Core_Config::domainID();
      self::cache($contactID);
      return "IN (SELECT contact_id FROM civicrm_acl_contact_cache WHERE user_id = $contactID AND domain_id = {$domainID})";
    }
    return NULL;
  }

  /**
   * Filter a list of contact_ids by the ones that the
   * user as a permissioned relationship with
   *
   * @param array $contact_ids
   *   List of contact IDs to be filtered
   * @param int $type
   *   access type CRM_Core_Permission::VIEW or CRM_Core_Permission::EDIT
   * @param int $userID
   *
   * @return array
   *   List of contact IDs that the user has permissions for
   */
  public static function relationshipList($contact_ids, $type, $userID = NULL) {
    $result_set = [];

    // no processing empty lists (avoid SQL errors as well)
    if (empty($contact_ids)) {
      return [];
    }

    // Default to currently logged in user
    $userID ??= CRM_Core_Session::getLoggedInContactID();
    if (empty($userID)) {
      return [];
    }

    // compile a list of queries (later to UNION)
    $queries = [];
    $contact_id_list = implode(',', $contact_ids);

    // add a select statement for each direction
    $directions = [['from' => 'a', 'to' => 'b'], ['from' => 'b', 'to' => 'a']];

    // CRM_Core_Permission::VIEW is satisfied by either CRM_Contact_BAO_Relationship::VIEW or CRM_Contact_BAO_Relationship::EDIT
    if ($type == CRM_Core_Permission::VIEW) {
      $is_perm_condition = ' IN ( ' . CRM_Contact_BAO_Relationship::EDIT . ' , ' . CRM_Contact_BAO_Relationship::VIEW . ' ) ';
    }
    else {
      $is_perm_condition = ' = ' . CRM_Contact_BAO_Relationship::EDIT;
    }

    // NORMAL/SINGLE DEGREE RELATIONSHIPS
    foreach ($directions as $direction) {
      $user_id_column    = "contact_id_{$direction['from']}";
      $contact_id_column = "contact_id_{$direction['to']}";

      // add clause for deleted contacts, if the user doesn't have the permission to access them
      $LEFT_JOIN_DELETED = $AND_CAN_ACCESS_DELETED = '';
      if (!CRM_Core_Permission::check('access deleted contacts')) {
        $LEFT_JOIN_DELETED       = "LEFT JOIN civicrm_contact ON civicrm_contact.id = {$contact_id_column} ";
        $AND_CAN_ACCESS_DELETED  = "AND civicrm_contact.is_deleted = 0";
      }

      $queries[] = "
SELECT civicrm_relationship.{$contact_id_column} AS contact_id
  FROM civicrm_relationship
  {$LEFT_JOIN_DELETED}
 WHERE civicrm_relationship.{$user_id_column} = {$userID}
   AND civicrm_relationship.{$contact_id_column} IN ({$contact_id_list})
   AND civicrm_relationship.is_active = 1
   AND civicrm_relationship.is_permission_{$direction['from']}_{$direction['to']} {$is_perm_condition}
   $AND_CAN_ACCESS_DELETED";
    }

    // FIXME: secondDegRelPermissions should be a setting
    $config = CRM_Core_Config::singleton();
    if ($config->secondDegRelPermissions) {
      foreach ($directions as $first_direction) {
        foreach ($directions as $second_direction) {
          // add clause for deleted contacts, if the user doesn't have the permission to access them
          $LEFT_JOIN_DELETED = $AND_CAN_ACCESS_DELETED = '';
          if (!CRM_Core_Permission::check('access deleted contacts')) {
            $LEFT_JOIN_DELETED       = "LEFT JOIN civicrm_contact first_degree_contact  ON first_degree_contact.id  = second_degree_relationship.contact_id_{$second_direction['from']}\n";
            $LEFT_JOIN_DELETED      .= "LEFT JOIN civicrm_contact second_degree_contact ON second_degree_contact.id = second_degree_relationship.contact_id_{$second_direction['to']} ";
            $AND_CAN_ACCESS_DELETED  = "AND first_degree_contact.is_deleted = 0\n";
            $AND_CAN_ACCESS_DELETED .= "AND second_degree_contact.is_deleted = 0 ";
          }

          $queries[] = "
SELECT second_degree_relationship.contact_id_{$second_direction['to']} AS contact_id
  FROM civicrm_relationship first_degree_relationship
  LEFT JOIN civicrm_relationship second_degree_relationship ON first_degree_relationship.contact_id_{$first_direction['to']} = second_degree_relationship.contact_id_{$second_direction['from']}
  {$LEFT_JOIN_DELETED}
 WHERE first_degree_relationship.contact_id_{$first_direction['from']} = {$userID}
   AND second_degree_relationship.contact_id_{$second_direction['to']} IN ({$contact_id_list})
   AND first_degree_relationship.is_active = 1
   AND first_degree_relationship.is_permission_{$first_direction['from']}_{$first_direction['to']} {$is_perm_condition}
   AND second_degree_relationship.is_active = 1
   AND second_degree_relationship.is_permission_{$second_direction['from']}_{$second_direction['to']} {$is_perm_condition}
   $AND_CAN_ACCESS_DELETED";
        }
      }
    }

    // finally UNION the queries and call
    $query = "(" . implode(")\nUNION DISTINCT (", $queries) . ")";
    $result = CRM_Core_DAO::executeQuery($query);
    while ($result->fetch()) {
      $result_set[(int) $result->contact_id] = TRUE;
    }
    return array_keys($result_set);
  }

  /**
   * @param int $contactID
   * @param CRM_Core_Form $form
   * @param bool $redirect
   *
   * @return bool
   */
  public static function validateOnlyChecksum($contactID, &$form, $redirect = TRUE) {
    // check if this is of the format cs=XXX
    if (!CRM_Contact_BAO_Contact_Utils::validChecksum($contactID,
      CRM_Utils_Request::retrieve('cs', 'String', $form, FALSE)
    )
    ) {
      if ($redirect) {
        // also set a message in the UF framework
        $message = ts('You do not have permission to edit this contact record. Contact the site administrator if you need assistance.');
        CRM_Utils_System::setUFMessage($message);

        $config = CRM_Core_Config::singleton();
        CRM_Core_Error::statusBounce($message,
          $config->userFrameworkBaseURL
        );
        // does not come here, we redirect in the above statement
      }
      return FALSE;
    }

    // set appropriate AUTH source
    self::initChecksumAuthSrc(TRUE, $form);

    // so here the contact is posing as $contactID, lets set the logging contact ID variable
    // CRM-8965
    CRM_Core_DAO::executeQuery('SET @civicrm_user_id = %1',
      [1 => [$contactID, 'Integer']]
    );

    return TRUE;
  }

  /**
   * @param bool $checkSumValidationResult
   * @param CRM_Core_Form|null $form
   */
  public static function initChecksumAuthSrc($checkSumValidationResult = FALSE, $form = NULL) {
    $session = CRM_Core_Session::singleton();
    if ($checkSumValidationResult && $form && CRM_Utils_Request::retrieve('cs', 'String', $form, FALSE)) {
      // if result is already validated, and url has cs, set the flag.
      $session->set('authSrc', CRM_Core_Permission::AUTH_SRC_CHECKSUM);
    }
    elseif (($session->get('authSrc') & CRM_Core_Permission::AUTH_SRC_CHECKSUM) == CRM_Core_Permission::AUTH_SRC_CHECKSUM) {
      // if checksum wasn't present in REQUEST OR checksum result validated as FALSE,
      // and flag was already set exactly as AUTH_SRC_CHECKSUM, unset it.
      $session->set('authSrc', CRM_Core_Permission::AUTH_SRC_UNKNOWN);
    }
  }

  /**
   * @param int $contactID
   * @param CRM_Core_Form $form
   * @param bool $redirect
   *
   * @return bool
   */
  public static function validateChecksumContact($contactID, &$form, $redirect = TRUE) {
    if (!self::allow($contactID, CRM_Core_Permission::EDIT)) {
      // check if this is of the format cs=XXX
      return self::validateOnlyChecksum($contactID, $form, $redirect);
    }
    return TRUE;
  }

}
