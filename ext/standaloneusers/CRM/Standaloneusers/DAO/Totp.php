<?php
use CRM_Standaloneusers_ExtensionUtil as E;

/**
 * DAOs provide an OOP-style facade for reading and writing database records.
 *
 * DAOs are a primary source for metadata in several versions of CiviCRM (<5.75)
 * and are required for some subsystems (such as APIv3).
 *
 * This stub provides compatibility. It is not intended to be modified in a
 * substantive way. However, you may add comments and annotations.
 */
class CRM_Standaloneusers_DAO_Totp extends CRM_Standaloneusers_DAO_Base {

  public static $_tableName = 'civicrm_totp';

}
