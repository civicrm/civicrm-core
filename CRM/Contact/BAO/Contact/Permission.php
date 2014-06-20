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
class CRM_Contact_BAO_Contact_Permission {

  /**
   * check if the logged in user has permissions for the operation type
   *
   * @param int $id contact id
   * @param int|string $type the type of operation (view|edit)
   *
   * @return boolean true if the user has permission, false otherwise
   * @access public
   * @static
   */
  static function allow($id, $type = CRM_Core_Permission::VIEW) {
    $tables = array();
    $whereTables = array();

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

    //check permission based on relationship, CRM-2963
    if (self::relationship($id)) {
      return TRUE;
    }

    $permission = CRM_ACL_API::whereClause($type, $tables, $whereTables);

    $from = CRM_Contact_BAO_Query::fromClause($whereTables);

    $query = "
SELECT count(DISTINCT contact_a.id)
       $from
WHERE contact_a.id = %1 AND $permission";
    $params = array(1 => array($id, 'Integer'));

    return (CRM_Core_DAO::singleValueQuery($query, $params) > 0) ? TRUE : FALSE;
  }

  /**
   * fill the acl contact cache for this contact id if empty
   *
   * @param $userID
   * @param int|string $type the type of operation (view|edit)
   * @param boolean $force should we force a recompute
   *
   * @internal param int $id contact id
   * @return void
   * @access public
   * @static
   */
  static function cache($userID, $type = CRM_Core_Permission::VIEW, $force = FALSE) {
    static $_processed = array();

    if ($type = CRM_Core_Permission::VIEW) {
      $operationClause = " operation IN ( 'Edit', 'View' ) ";
      $operation = 'View';
    }
    else {
      $operationClause = " operation = 'Edit' ";
      $operation = 'Edit';
    }

    if (!$force) {
      if (!empty($_processed[$userID])) {
        return;
      }

      // run a query to see if the cache is filled
      $sql = "
SELECT count(id)
FROM   civicrm_acl_contact_cache
WHERE  user_id = %1
AND    $operationClause
";
      $params = array(1 => array($userID, 'Integer'));
      $count = CRM_Core_DAO::singleValueQuery($sql, $params);
      if ($count > 0) {
        $_processed[$userID] = 1;
        return;
      }
    }

    $tables = array();
    $whereTables = array();

    $permission = CRM_ACL_API::whereClause($type, $tables, $whereTables, $userID);

    $from = CRM_Contact_BAO_Query::fromClause($whereTables);

    CRM_Core_DAO::executeQuery("
INSERT INTO civicrm_acl_contact_cache ( user_id, contact_id, operation )
SELECT      $userID as user_id, contact_a.id as contact_id, '$operation' as operation
         $from
WHERE    $permission
GROUP BY contact_a.id
ON DUPLICATE KEY UPDATE
         user_id=VALUES(user_id),
         contact_id=VALUES(contact_id),
         operation=VALUES(operation)"
    );

    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_acl_contact_cache WHERE contact_id IN (SELECT id FROM civicrm_contact WHERE is_deleted = 1)');
    $_processed[$userID] = 1;

    return;
  }

  /**
   * Function to check if there are any contacts in cache table
   *
   * @param int|string $type the type of operation (view|edit)
   * @param int $contactID contact id
   *
   * @return boolean
   * @access public
   * @static
   */
  static function hasContactsInCache($type = CRM_Core_Permission::VIEW,
    $contactID = NULL
  ) {
    if (!$contactID) {
      $session = CRM_Core_Session::singleton();
      $contactID = $session->get('userID');
    }

    if ($type = CRM_Core_Permission::VIEW) {
      $operationClause = " operation IN ( 'Edit', 'View' ) ";
      $operation = 'View';
    }
    else {
      $operationClause = " operation = 'Edit' ";
      $operation = 'Edit';
    }

    // fill cache
    self::cache($contactID);

    $sql = "
SELECT id
FROM   civicrm_acl_contact_cache
WHERE  user_id = %1
AND    $operationClause LIMIT 1";

    $params = array(1 => array($contactID, 'Integer'));
    return (bool) CRM_Core_DAO::singleValueQuery($sql, $params);
  }

  /**
   * @param string $contactAlias
   * @param null $contactID
   *
   * @return array
   */
  static function cacheClause($contactAlias = 'contact_a', $contactID = NULL) {
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

    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');
    if (!$contactID) {
      $contactID = 0;
    }
    $contactID = CRM_Utils_Type::escape($contactID, 'Integer');

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
      $whereClase = " aclContactCache.user_id = $contactID ";
    }

    return array($fromClause, $whereClase);
  }

  /**
   * Function to get the permission base on its relationship
   *
   * @param $selectedContactID
   * @param null $contactID
   *
   * @internal param int $selectedContactId contact id of selected contact
   * @internal param int $contactId contact id of the current contact
   *
   * @return booleab true if logged in user has permission to view
   * selected contact record else false
   * @static
   */
  static function relationship($selectedContactID, $contactID = NULL) {
    $session = CRM_Core_Session::singleton();
    $config = CRM_Core_Config::singleton();
    if (!$contactID) {
      $contactID = $session->get('userID');
      if (!$contactID) {
        return FALSE;
      }
    }
    if ($contactID == $selectedContactID && CRM_Core_Permission::check('edit my contact')) {
      return TRUE;
    }
    else {
      if ($config->secondDegRelPermissions) {
        $query = "
SELECT firstdeg.id
FROM   civicrm_relationship firstdeg
LEFT JOIN civicrm_relationship seconddegaa
  on firstdeg.contact_id_a = seconddegaa.contact_id_b
  and seconddegaa.is_permission_b_a = 1
  and firstdeg.is_permission_b_a = 1
  and seconddegaa.is_active = 1
LEFT JOIN civicrm_relationship seconddegab
  on firstdeg.contact_id_a = seconddegab.contact_id_a
  and seconddegab.is_permission_a_b = 1
  and firstdeg.is_permission_b_a = 1
  and seconddegab.is_active = 1
LEFT JOIN civicrm_relationship seconddegba
  on firstdeg.contact_id_b = seconddegba.contact_id_b
  and seconddegba.is_permission_b_a = 1
  and firstdeg.is_permission_a_b = 1
  and seconddegba.is_active = 1
LEFT JOIN civicrm_relationship seconddegbb
  on firstdeg.contact_id_b = seconddegbb.contact_id_a
  and seconddegbb.is_permission_a_b = 1
  and firstdeg.is_permission_a_b = 1
  and seconddegbb.is_active = 1
WHERE
  (
    ( firstdeg.contact_id_a = %1 AND firstdeg.contact_id_b = %2 AND firstdeg.is_permission_a_b = 1 )
    OR ( firstdeg.contact_id_a = %2 AND firstdeg.contact_id_b = %1 AND firstdeg.is_permission_b_a = 1 )
    OR (
      firstdeg.contact_id_a = %1 AND seconddegba.contact_id_a = %2
      AND (seconddegba.contact_id_a NOT IN (SELECT id FROM civicrm_contact WHERE is_deleted = 1))
    )
    OR (
      firstdeg.contact_id_a = %1 AND seconddegbb.contact_id_b = %2
      AND (seconddegbb.contact_id_b NOT IN (SELECT id FROM civicrm_contact WHERE is_deleted = 1))
    )
    OR (
      firstdeg.contact_id_b = %1 AND seconddegab.contact_id_b = %2
      AND (seconddegab.contact_id_b NOT IN (SELECT id FROM civicrm_contact WHERE is_deleted = 1))
    )
    OR (
      firstdeg.contact_id_b = %1 AND seconddegaa.contact_id_a = %2      AND (seconddegaa.contact_id_a NOT IN (SELECT id FROM civicrm_contact WHERE is_deleted = 1))
    )
  )
  AND (firstdeg.contact_id_a NOT IN (SELECT id FROM civicrm_contact WHERE is_deleted = 1))
  AND (firstdeg.contact_id_b NOT IN (SELECT id FROM civicrm_contact WHERE is_deleted = 1))
  AND ( firstdeg.is_active = 1)
      ";
      }
      else {
        $query = "
SELECT id
FROM   civicrm_relationship
WHERE  (( contact_id_a = %1 AND contact_id_b = %2 AND is_permission_a_b = 1 ) OR
        ( contact_id_a = %2 AND contact_id_b = %1 AND is_permission_b_a = 1 )) AND
       (contact_id_a NOT IN (SELECT id FROM civicrm_contact WHERE is_deleted = 1)) AND
       (contact_id_b NOT IN (SELECT id FROM civicrm_contact WHERE is_deleted = 1))
  AND  ( civicrm_relationship.is_active = 1 )
";
      }
      $params = array(1 => array($contactID, 'Integer'),
        2 => array($selectedContactID, 'Integer'),
      );
      return CRM_Core_DAO::singleValueQuery($query, $params);
    }
  }


  /**
   * @param $contactID
   * @param $form
   * @param bool $redirect
   *
   * @return bool
   */
  static function validateOnlyChecksum($contactID, &$form, $redirect = TRUE) {
    // check if this is of the format cs=XXX
    if (!CRM_Contact_BAO_Contact_Utils::validChecksum($contactID,
        CRM_Utils_Request::retrieve('cs', 'String', $form, FALSE)
      )) {
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
  static function initChecksumAuthSrc($checkSumValidationResult = FALSE, $form = NULL) {
    $session = CRM_Core_Session::singleton();
    if ($checkSumValidationResult && $form && CRM_Utils_Request::retrieve('cs', 'String', $form, FALSE)) {
      // if result is already validated, and url has cs, set the flag.
      $session->set('authSrc', CRM_Core_Permission::AUTH_SRC_CHECKSUM);
    } else if (($session->get('authSrc') & CRM_Core_Permission::AUTH_SRC_CHECKSUM) == CRM_Core_Permission::AUTH_SRC_CHECKSUM) {
      // if checksum wasn't present in REQUEST OR checksum result validated as FALSE,
      // and flag was already set exactly as AUTH_SRC_CHECKSUM, unset it.
      $session->set('authSrc', CRM_Core_Permission::AUTH_SRC_UNKNOWN);
    }
  }

  /**
   * @param $contactID
   * @param $form
   * @param bool $redirect
   *
   * @return bool
   */
  static function validateChecksumContact($contactID, &$form, $redirect = TRUE) {
    if (!self::allow($contactID, CRM_Core_Permission::EDIT)) {
      // check if this is of the format cs=XXX
      return self::validateOnlyChecksum($contactID, $form, $redirect);
    }
    return TRUE;
  }
}

