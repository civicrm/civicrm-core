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
 */
class CRM_Contact_BAO_Contact_Permission {

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
   *    list of contact IDs the logged in user has the given permission for
   */
  public static function allowList($contact_ids, $type = CRM_Core_Permission::VIEW) {
    $result_set = array();
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
      return array();
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
      $rejected_contacts       = array_diff_key($contact_ids, $result_set);
      // @todo consider storing these to the acl cache for next time, since we have fetched.
      $allowed_by_relationship = self::relationshipList($rejected_contacts);
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
   *
   * @return bool
   *   true if the user has permission, false otherwise
   */
  public static function allow($id, $type = CRM_Core_Permission::VIEW) {
    // get logged in user
    $contactID = CRM_Core_Session::getLoggedInContactID();

    // first: check if contact is trying to view own contact
    if ($contactID == $id && ($type == CRM_Core_Permission::VIEW && CRM_Core_Permission::check('view my contact')
     || $type == CRM_Core_Permission::EDIT && CRM_Core_Permission::check('edit my contact'))
      ) {
      return TRUE;
    }

    # FIXME: push this somewhere below, to not give this permission so many rights
    $isDeleted = (bool) CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $id, 'is_deleted');
    if (CRM_Core_Permission::check('access deleted contacts') && $isDeleted) {
      return TRUE;
    }

    // short circuit for admin rights here so we avoid unneeeded queries
    // some duplication of code, but we skip 3-5 queries
    if (CRM_Core_Permission::check('edit all contacts') ||
      ($type == CRM_ACL_API::VIEW && CRM_Core_Permission::check('view all contacts'))
    ) {
      return TRUE;
    }

    // check permission based on relationship, CRM-2963
    if (self::relationshipList(array($id))) {
      return TRUE;
    }

    // We should probably do a cheap check whether it's in the cache first.
    // check permission based on ACL
    $tables = array();
    $whereTables = array();

    $permission = CRM_ACL_API::whereClause($type, $tables, $whereTables);
    $from = CRM_Contact_BAO_Query::fromClause($whereTables);

    $query = "
SELECT contact_a.id
       $from
WHERE contact_a.id = %1 AND $permission
  LIMIT 1
";

    if (CRM_Core_DAO::singleValueQuery($query, array(1 => array($id, 'Integer')))) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Fill the acl contact cache for this contact id if empty.
   *
   * @param int $userID
   * @param int|string $type the type of operation (view|edit)
   * @param bool $force
   *   Should we force a recompute.
   */
  public static function cache($userID, $type = CRM_Core_Permission::VIEW, $force = FALSE) {
    // FIXME: maybe find a better way of keeping track of this. @eileen pointed out
    //   that somebody might flush the cache away from under our feet,
    //   but the altenative would be a SQL call every time this is called,
    //   and a complete rebuild if the result was an empty set...
    static $_processed = array(
      CRM_Core_Permission::VIEW => array(),
      CRM_Core_Permission::EDIT => array());

    if ($type == CRM_Core_Permission::VIEW) {
      $operationClause = " operation IN ( 'Edit', 'View' ) ";
      $operation = 'View';
    }
    else {
      $operationClause = " operation = 'Edit' ";
      $operation = 'Edit';
    }
    $queryParams = array(1 => array($userID, 'Integer'));

    if (!$force) {
      // skip if already calculated
      if (!empty($_processed[$type][$userID])) {
        return;
      }

      // run a query to see if the cache is filled
      $sql = "
SELECT count(id)
FROM   civicrm_acl_contact_cache
WHERE  user_id = %1
AND    $operationClause
";
      $count = CRM_Core_DAO::singleValueQuery($sql, $queryParams);
      if ($count > 0) {
        $_processed[$type][$userID] = 1;
        return;
      }
    }

    $tables = array();
    $whereTables = array();

    $permission = CRM_ACL_API::whereClause($type, $tables, $whereTables, $userID, FALSE, FALSE, TRUE);

    $from = CRM_Contact_BAO_Query::fromClause($whereTables);
    CRM_Core_DAO::executeQuery("
INSERT INTO civicrm_acl_contact_cache ( user_id, contact_id, operation )
SELECT DISTINCT $userID as user_id, contact_a.id as contact_id, '{$operation}' as operation
         $from
         LEFT JOIN civicrm_acl_contact_cache ac ON ac.user_id = $userID AND ac.contact_id = contact_a.id AND ac.operation = '{$operation}'
WHERE    $permission
AND ac.user_id IS NULL
");

    // Add in a row for the logged in contact. Do not try to combine with the above query or an ugly OR will appear in
    // the permission clause.
    if (CRM_Core_Permission::check('edit my contact') ||
      ($type == CRM_Core_Permission::VIEW && CRM_Core_Permission::check('view my contact'))) {
      if (!CRM_Core_DAO::singleValueQuery("
        SELECT count(*) FROM civicrm_acl_contact_cache WHERE user_id = %1 AND contact_id = %1 AND operation = '{$operation}' LIMIT 1", $queryParams)) {
        CRM_Core_DAO::executeQuery("INSERT INTO civicrm_acl_contact_cache ( user_id, contact_id, operation ) VALUES(%1, %1, '{$operation}')");
      }
    }
    $_processed[$type][$userID] = 1;
  }

  /**
   * @param string $contactAlias
   *
   * @return array
   */
  public static function cacheClause($contactAlias = 'contact_a') {
    if (CRM_Core_Permission::check('view all contacts') ||
      CRM_Core_Permission::check('edit all contacts')
    ) {
      if (is_array($contactAlias)) {
        $wheres = array();
        foreach ($contactAlias as $alias) {
          // CRM-6181
          $wheres[] = "$alias.is_deleted = 0";
        }
        return array(NULL, '(' . implode(' AND ', $wheres) . ')');
      }
      else {
        // CRM-6181
        return array(NULL, "$contactAlias.is_deleted = 0");
      }
    }

    $contactID = (int) CRM_Core_Session::getLoggedInContactID();
    self::cache($contactID);

    if (is_array($contactAlias) && !empty($contactAlias)) {
      //More than one contact alias
      $clauses = array();
      foreach ($contactAlias as $k => $alias) {
        $clauses[] = " INNER JOIN civicrm_acl_contact_cache aclContactCache_{$k} ON {$alias}.id = aclContactCache_{$k}.contact_id AND aclContactCache_{$k}.user_id = $contactID ";
      }

      $fromClause = implode(" ", $clauses);
      $whereClase = NULL;
    }
    else {
      $fromClause = " INNER JOIN civicrm_acl_contact_cache aclContactCache ON {$contactAlias}.id = aclContactCache.contact_id ";
      $whereClase = " aclContactCache.user_id = $contactID AND $contactAlias.is_deleted = 0";
    }

    return array($fromClause, $whereClase);
  }

  /**
   * Generate acl subquery that can be placed in the WHERE clause of a query or the ON clause of a JOIN
   *
   * @return string|null
   */
  public static function cacheSubquery() {
    if (!CRM_Core_Permission::check(array(array('view all contacts', 'edit all contacts')))) {
      $contactID = (int) CRM_Core_Session::getLoggedInContactID();
      self::cache($contactID);
      return "IN (SELECT contact_id FROM civicrm_acl_contact_cache WHERE user_id = $contactID)";
    }
    return NULL;
  }

  /**
   * Filter a list of contact_ids by the ones that the
   *  currently active user as a permissioned relationship with
   *
   * @param array $contact_ids
   *   List of contact IDs to be filtered
   *
   * @return array
   *   List of contact IDs that the user has permissions for
   */
  public static function relationshipList($contact_ids) {
    $result_set = array();

    // no processing empty lists (avoid SQL errors as well)
    if (empty($contact_ids)) {
      return array();
    }

    // get the currently logged in user
    $contactID = CRM_Core_Session::getLoggedInContactID();
    if (empty($contactID)) {
      return array();
    }

    // compile a list of queries (later to UNION)
    $queries = array();
    $contact_id_list = implode(',', $contact_ids);

    // add a select statement for each direection
    $directions = array(array('from' => 'a', 'to' => 'b'), array('from' => 'b', 'to' => 'a'));

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
 WHERE civicrm_relationship.{$user_id_column} = {$contactID}
   AND civicrm_relationship.{$contact_id_column} IN ({$contact_id_list})
   AND civicrm_relationship.is_active = 1
   AND civicrm_relationship.is_permission_{$direction['from']}_{$direction['to']} = 1
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
  LEFT JOIN civicrm_relationship second_degree_relationship ON first_degree_relationship.contact_id_{$first_direction['to']} = second_degree_relationship.contact_id_{$first_direction['from']}
  {$LEFT_JOIN_DELETED}
 WHERE first_degree_relationship.contact_id_{$first_direction['from']} = {$contactID}
   AND second_degree_relationship.contact_id_{$second_direction['to']} IN ({$contact_id_list})
   AND first_degree_relationship.is_active = 1
   AND first_degree_relationship.is_permission_{$first_direction['from']}_{$first_direction['to']} = 1
   AND second_degree_relationship.is_active = 1
   AND second_degree_relationship.is_permission_{$second_direction['from']}_{$second_direction['to']} = 1
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
      array(1 => array($contactID, 'Integer'))
    );

    return TRUE;
  }

  /**
   * @param bool $checkSumValidationResult
   * @param null $form
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
