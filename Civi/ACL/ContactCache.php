<?php

namespace Civi\ACL;

/**
 * Default implementation of AclContactCache.
 *
 * You can override the default implementation in your extension with the following
 * code:
 *  function hook_civicrm_container($container) {
 *    $container->setDefinition('acl_contact_cache', new Definition('MyAclContactCache'));
 *  }
 *
 *  class MyAclContactCache extends \Civi\ACL\ContactCache {
 *   // And override the function definied in this class to your own needs.
 *  }
 * Class ContactCache
 */
class ContactCache implements ContactCacheInterface {

  /**
   * @var int
   *  The cache timeout in minutes.
   * @ToDo: Get the validity period from a setting which could be set in the UI.
   */
  private $cacheInvalidTimeout = 180; // = 3 hours.

  private static $alreadyRefreshedContactCache = FALSE;

  public function __construct() {
    $this->cacheInvalidTimeout = \Civi::settings()->get('acl_contact_cache_validity');
  }

  /**
   * Gets the where clause for the ACL query.
   *
   * @param int $operation_type
   *  The operation View, Edit. @see CRM_Core_Permission::VIEW and CRM_Core_Permission::EDIT.
   * @param string $contact_table_alias
   *  The alias of the civicrm_contact table. - Optional default to 'civicrm_contact'
   * @param string $contact_id_field
   *  The field which holds the contact ID. - Optional default to 'id'
   * @param string $acl_contact_cache_alias
   *   The alias for the acl contact cache table
   * @return string
   */
  public function getAclWhereClause($operation_type, $contact_table_alias = 'civicrm_contact', $contact_id_field = 'id', $acl_contact_cache_alias = 'civicrm_acl_contacts') {
    // first see if the contact has edit / view all contacts
    if (\CRM_Core_Permission::check('edit all contacts') || ($operation_type == \CRM_Core_Permission::VIEW && \CRM_Core_Permission::check('view all contacts'))) {
      return '';
    }

    $contactID = \CRM_Core_Session::getLoggedInContactID();
    $contactID = (int) $contactID;
    $domainID = \CRM_Core_Config::domainID();

    if (!$this->isCacheValid($operation_type, $contactID, $domainID)) {
      $this->refreshCache($operation_type, $contactID, $domainID);
    }

    return " `{$acl_contact_cache_alias}`.`operation_type` = '" . $operation_type . "' AND `{$acl_contact_cache_alias}`.`user_id` = '" . $contactID . "' AND `{$acl_contact_cache_alias}`.`domain_id` = '" . $domainID . "'";
  }

  /**
   * Gets the join part for the ACL query.
   * The join is done on a table which has field for the contact_id. Could be civicrm_contact.id or e.g. civicrm_participant.contact_id.
   *
   * @param int $operation_type
   *  The operation View, Edit. @see CRM_Core_Permission::VIEW and CRM_Core_Permission::EDIT.
   * @param string $contact_table_alias
   *  The alias of the civicrm_contact table. - Optional default to 'civicrm_contact'
   * @param string $contact_id_field
   *  The field which holds the contact ID. - Optional default to 'id'
   * @param string $acl_contact_cache_alias
   *   The alias for the acl contact cache table
   * @return string
   */
  public function getAclJoin($operation_type, $contact_table_alias = 'civicrm_contact', $contact_id_field = 'id', $acl_contact_cache_alias = 'civicrm_acl_contacts') {
    // first see if the contact has edit / view all contacts
    if (\CRM_Core_Permission::check('edit all contacts') || ($operation_type == \CRM_Core_Permission::VIEW && \CRM_Core_Permission::check('view all contacts'))) {
      return '';
    }

    return "INNER JOIN `civicrm_acl_contacts` `{$acl_contact_cache_alias}` ON `{$acl_contact_cache_alias}`.`contact_id` = `{$contact_table_alias}`.`{$contact_id_field}`";
  }

  /**
   * Returns whether the cache is valid for the current user and operation type.
   *
   * Returns true when the cache is still valid.
   * Returns false when the cache is invalid.
   *
   * @param $operation_type
   *  The operation View, Edit. @see CRM_Core_Permission::VIEW and CRM_Core_Permission::EDIT.
   * @return bool
   */
  public function isCacheValidForCurrentUser($operation_type) {
    $contactID = \CRM_Core_Session::getLoggedInContactID();
    $contactID = (int) $contactID;
    $domainID = \CRM_Core_Config::domainID();
    return $this->isCacheValid($operation_type, $contactID, $domainID);
  }

  /**
   * Returns whether the cache is valid for the given user and operation type.
   *
   * Returns true when the cache is still valid.
   * Returns false when the cache is invalid.
   *
   * @param $operation_type
   *  The operation View, Edit. @see CRM_Core_Permission::VIEW and CRM_Core_Permission::EDIT.
   * @param int $userId
   * @param int $domainId
   * @return bool
   */
  public function isCacheValid($operation_type, $userId, $domainId) {
    $validityPeriod = $this->cacheInvalidTimeout;
    $lastModifiedDate = new \DateTime();
    $lastModifiedDate->modify('-' . $validityPeriod . ' minutes');
    $validityParams[1] = array($userId, 'Integer');
    $validityParams[2] = array($operation_type, 'Integer');
    $validityParams[3] = array($domainId, 'Integer');
    $validityParams[4] = array(
      $lastModifiedDate->format('Y-m-d H:i:s'),
      'String',
    );
    $isValid = \CRM_Core_DAO::singleValueQuery("SELECT user_id FROM `civicrm_acl_contacts_validity` WHERE `user_id` = %1 AND `operation_type` = %2 AND `domain_id` = %3 AND `modified_date` >= %4", $validityParams);
    return $isValid ? TRUE : FALSE;
  }

  /**
   * Refresh the cache for the current user.
   *
   * @param int $operation_type
   *  The operation View, Edit. @see CRM_Core_Permission::VIEW and CRM_Core_Permission::EDIT.
   * @return void
   */
  public function refreshCacheForCurrentUser($operation_type) {
    $contactID = \CRM_Core_Session::getLoggedInContactID();
    $contactID = (int) $contactID;
    $domainID = \CRM_Core_Config::domainID();
    // Make sure we only refresh the cache once per request.
    // We set a static variable when we have refreshed the cached and we
    // check that variable.
    if (!self::$alreadyRefreshedContactCache) {
      $this->refreshCache($operation_type, $contactID, $domainID);
      self::$alreadyRefreshedContactCache = TRUE;
    }
  }

  /**
   * Refresh the cache for the specified user on the specified domain.
   *
   * @param int $operation_type
   *  The operation View, Edit. @see CRM_Core_Permission::VIEW and CRM_Core_Permission::EDIT.
   * @param int $userId
   * @param int $domainId
   * @return void
   */
  public function refreshCache($operation_type, $userId, $domainId) {
    // Rebuild the civicrm_acl_contacts table.
    $aclContactsParams[1] = array($userId, 'Integer');
    $aclContactsParams[2] = array($operation_type, 'Integer');
    $aclContactsParams[3] = array($domainId, 'Integer');
    \CRM_Core_DAO::executeQuery("DELETE FROM `civicrm_acl_contacts` WHERE `user_id` = %1 AND `operation_type` = %2 AND domain_id = %3", $aclContactsParams);
    \CRM_Core_DAO::executeQuery("DELETE FROM `civicrm_acl_contacts_validity` WHERE `user_id` = %1 AND `operation_type` = %2 AND domain_id = %3", $aclContactsParams);

    $aclTables = array();
    $aclWhereTables = array();
    $where = \CRM_ACL_BAO_ACL::whereClause($operation_type, $aclTables, $aclWhereTables, $userId);

    // Add permission on self
    // @ToDo chekc the permission for the given $userId rather than for the current logged in user.
    if ($userId == \CRM_Core_Session::getLoggedInContactID() && (\CRM_Core_Permission::check('edit my contact') || $operation_type == \CRM_Core_Permission::VIEW && \CRM_Core_Permission::check('view my contact'))) {
      $where = "(contact_a.id = $userId OR ($where))";
    }
    $from = \CRM_Contact_BAO_Query::fromClause($aclWhereTables);

    $queries = $this->relationshipAClQueries($domainId, $userId, $operation_type);
    $queries[] = "SELECT '{$domainId}' AS domain_id, '{$userId}' AS user_id, contact_a.id as contact_id, '{$operation_type}' as operation_type {$from} WHERE {$where}";
    $selectQuery = "(" . implode(")\nUNION DISTINCT (", $queries) . ")";

    \CRM_Core_DAO::executeQuery("INSERT INTO `civicrm_acl_contacts` (domain_id, user_id, contact_id, operation_type) {$selectQuery}");

    //Update the acl_contacts_validity table
    \CRM_Core_DAO::executeQuery("INSERT INTO `civicrm_acl_contacts_validity` (domain_id, `user_id`, `modified_date`, `operation_type`) VALUES (%3, %1, CURRENT_TIMESTAMP(), %2)", $aclContactsParams);
  }

  /**
   * Clear the cache.
   *
   * @return mixed
   */
  public function clearCache() {
    \CRM_Core_DAO::executeQuery("TRUNCATE civicrm_acl_contacts");
    \CRM_Core_DAO::executeQuery("TRUNCATE civicrm_acl_contacts_validity");
  }

  /**
   * Filter a list of contact_ids by the ones that the
   *  currently active user has a permissioned relationship with
   *
   * @return array
   *   Query to use in the CRM_ACL_BAO_Contacts::fillAclContacts to include related contacts which a user has a permission to view or edit.
   */
  private function relationshipAClQueries($domain_id, $user_id, $type) {

    // compile a list of queries (later to UNION)
    $queries = array();

    // add a select statement for each direection
    $directions = array(
      array('from' => 'a', 'to' => 'b'),
      array('from' => 'b', 'to' => 'a'),
    );

    // NORMAL/SINGLE DEGREE RELATIONSHIPS
    foreach ($directions as $direction) {
      $user_id_column = "contact_id_{$direction['from']}";
      $contact_id_column = "contact_id_{$direction['to']}";

      $queries[] = "SELECT '{$domain_id}' as domain_id, '{$user_id}' as  user_id, civicrm_relationship.{$contact_id_column} AS contact_id, '{$type}' AS operation_type
                    FROM civicrm_relationship
                    WHERE civicrm_relationship.{$user_id_column} = {$user_id}
                    AND civicrm_relationship.is_active = 1 AND civicrm_relationship.is_permission_{$direction['from']}_{$direction['to']} = 1";
    }

    // FIXME: secondDegRelPermissions should be a setting
    $config = \CRM_Core_Config::singleton();
    if ($config->secondDegRelPermissions) {
      foreach ($directions as $first_direction) {
        foreach ($directions as $second_direction) {
          $queries[] = "
            SELECT '{$domain_id}' as domain_id, '{$user_id}' as  user_id,  second_degree_relationship.contact_id_{$second_direction['to']} AS contact_id, '{$type}' AS operation_type
            FROM civicrm_relationship first_degree_relationship
            LEFT JOIN civicrm_relationship second_degree_relationship ON first_degree_relationship.contact_id_{$first_direction['to']} = second_degree_relationship.contact_id_{$second_direction['from']}
            WHERE first_degree_relationship.contact_id_{$first_direction['from']} = {$user_id}
            AND first_degree_relationship.is_active = 1
            AND first_degree_relationship.is_permission_{$first_direction['from']}_{$first_direction['to']} = 1
            AND second_degree_relationship.is_active = 1
            AND second_degree_relationship.is_permission_{$second_direction['from']}_{$second_direction['to']} = 1";
        }
      }
    }
    return $queries;
  }

}
