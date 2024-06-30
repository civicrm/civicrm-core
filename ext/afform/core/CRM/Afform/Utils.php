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

use CRM_Afform_ExtensionUtil as E;

/**
 *
 */
class CRM_Afform_Utils {

  /**
   * Get a list of authentication options for `afform_mail_auth_token`.
   *
   * @return array
   *   Array (string $machineName => string $label).
   */
  public static function getMailAuthOptions(): array {
    return [
      'session' => E::ts('Session-level authentication'),
      'page' => E::ts('Page-level authentication'),
    ];
  }

}
