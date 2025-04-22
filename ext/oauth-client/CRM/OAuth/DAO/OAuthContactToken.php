<?php

/**
 * DAOs provide an OOP-style facade for reading and writing database records.
 *
 * DAOs are a primary source for metadata in older versions of CiviCRM (<5.74)
 * and are required for some subsystems (such as APIv3).
 *
 * This stub provides compatibility. It is not intended to be modified in a
 * substantive way. Property annotations may be added, but are not required.
 * @property string $id
 * @property string $tag
 * @property string $client_id
 * @property string $contact_id
 * @property string $grant_type
 * @property string $scopes
 * @property string $token_type
 * @property string $access_token
 * @property string $expires
 * @property string $refresh_token
 * @property string $resource_owner_name
 * @property string $resource_owner
 * @property string $error
 * @property string $raw
 * @property string $created_date
 * @property string $modified_date
 */
class CRM_OAuth_DAO_OAuthContactToken extends CRM_OAuth_DAO_Base {

  /**
   * Required by older versions of CiviCRM (<5.74).
   * @var string
   */
  public static $_tableName = 'civicrm_oauth_contact_token';

}
