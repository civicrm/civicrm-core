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
 * @property string $provider
 * @property string $guid
 * @property string $tenant
 * @property string $secret
 * @property string $options
 * @property bool|string $is_active
 * @property string $created_date
 * @property string $modified_date
 */
class CRM_OAuth_DAO_OAuthClient extends CRM_OAuth_DAO_Base {

  /**
   * Required by older versions of CiviCRM (<5.74).
   * @var string
   */
  public static $_tableName = 'civicrm_oauth_client';

}
