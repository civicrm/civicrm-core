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

/**
 * Class CRM_Utils_Geocode
 */
class CRM_Utils_Geocode {

  /**
   * @deprecated
   *
   * @todo Remove this method. In case people are calling this downstream (which
   *   is unsupported usage), we'll deprecate it for a few releases before
   *   removing it altogether.
   *
   * @return string|''
   *   Class name, or empty.
   */
  public static function getProviderClass() {
    Civi::log()->warning(
      'CRM_Utils_Geocode is deprecated and will be removed from core soon, use CRM_Utils_GeocodeProvider::getUsableClassName()',
      ['civi.tag' => 'deprecated']
    );

    return (string) CRM_Utils_GeocodeProvider::getUsableClassName();
  }

}
