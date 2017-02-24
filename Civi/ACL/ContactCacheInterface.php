<?php

namespace Civi\ACL;

interface ContactCacheInterface {

  /**
   * Gets the where clause for the ACL query.
   *
   * @param int $operation_type
   *  The operation View, Edit. @see CRM_Core_Permission::VIEW and CRM_Core_Permission::EDIT.
   * @param string $contact_table_alias
   *  The alias of the civicrm_contact table. - Optional default to 'civicrm_contact'
   * @param string contact_id field
   *  The field which holds the contact ID. - Optional default to 'id'
   * @param string $acl_contact_cache_alias
   *   The alias for the acl contact cache table
   * @return string
   */
  public function getAclWhereClause($operation_type, $contact_table_alias='civicrm_contact', $contact_id_field='id', $acl_contact_cache_alias='civicrm_acl_contacts');

  /**
   * Gets the join part for the ACL query.
   * The join is done on a table which has field for the contact_id. Could be civicrm_contact.id or e.g. civicrm_participant.contact_id.
   *
   * @param int $operation_type
   *  The operation View, Edit. @see CRM_Core_Permission::VIEW and CRM_Core_Permission::EDIT.
   * @param string $contact_table_alias
   *  The alias of the civicrm_contact table. - Optional default to 'civicrm_contact'
   * @param string contact_id field
   *  The field which holds the contact ID. - Optional default to 'id'
   * @param string $acl_contact_cache_alias
   *   The alias for the acl contact cache table
   * @return string
   */
  public function getAclJoin($operation_type, $contact_table_alias='civicrm_contact', $contact_id_field='id', $acl_contact_cache_alias='civicrm_acl_contacts');

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
  public function isCacheValidForCurrentUser($operation_type);

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
  public function isCacheValid($operation_type, $userId, $domainId);

  /**
   * Refresh the cache for the current user.
   *
   * @param int $operation_type
   *  The operation View, Edit. @see CRM_Core_Permission::VIEW and CRM_Core_Permission::EDIT.
   * @return void
   */
  public function refreshCacheForCurrentUser($operation_type);

  /**
   * Refresh the cache for the specified user on the specified domain.
   *
   * @param int $operation_type
   *  The operation View, Edit. @see CRM_Core_Permission::VIEW and CRM_Core_Permission::EDIT.
   * @param int $userId
   * @param int $domainId
   * @return void
   */
  public function refreshCache($operation_type, $userId, $domainId);

  /**
   * Clear the cache.
   *
   * @return mixed
   */
  public function clearCache();

}