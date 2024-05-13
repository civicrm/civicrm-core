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
class CRM_OAuth_BAO_OAuthSysToken extends CRM_OAuth_DAO_OAuthSysToken {

  private static $returnFields = ['id', 'client_id', 'expires', 'tag'];

  /**
   * Redact the content of a token.
   *
   * This is useful for processes which must internally use the entire token
   * record -- but then report on their progress to a permissioned party.
   *
   * @param array $tokenRecord
   * @return array
   */
  public static function redact($tokenRecord) {
    if (!\CRM_Core_Permission::check('manage OAuth client secrets')) {
      return \CRM_Utils_Array::subset($tokenRecord, self::$returnFields);
    }
    else {
      return $tokenRecord;
    }
  }

}
