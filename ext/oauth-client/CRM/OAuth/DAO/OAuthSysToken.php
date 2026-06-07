<?php

/**
 * DAOs provide an OOP-style facade for reading and writing database records.
 *
 * DAOs are a primary source for metadata in older versions of CiviCRM (<5.74)
 * and are required for some subsystems (such as APIv3).
 *
 * This stub provides compatibility. It is not intended to be modified in a
 * substantive way. Property annotations may be added, but are not required.
 *
 * @property int|string|null $id
 * @property string|null $tag
 * @property int|string|null $client_id
 * @property string|null $grant_type
 * @property string|null $scopes
 * @property string|null $token_type
 * @property string|null $access_token
 * @property int|string|null $expires
 * @property string|null $refresh_token
 * @property string|null $resource_owner_name
 * @property string|null $resource_owner
 * @property string|null $error
 * @property string|null $raw
 * @property string|null $created_date
 * @property string|null $modified_date
 */
class CRM_OAuth_DAO_OAuthSysToken extends CRM_OAuth_DAO_Base {

  /**
   * Required by older versions of CiviCRM (<5.74).
   * @var string
   */
  public static $_tableName = 'civicrm_oauth_systoken';

}
