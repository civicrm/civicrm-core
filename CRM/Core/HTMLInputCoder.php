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
 * Class CRM_Core_HTMLInputCoder
 */
class CRM_Core_HTMLInputCoder {

  /**
   * @param string $fldName
   * @return bool
   *   TRUE if encoding should be skipped for this field
   */
  public static function isSkippedField($fldName) {
    return CRM_Utils_API_HTMLInputCoder::singleton()->isSkippedField($fldName);
  }

  /**
   * going to filter the
   * submitted values across XSS vulnerability.
   *
   * @param array|string $values
   * @param bool $castToString
   *   If TRUE, all scalars will be filtered (and therefore cast to strings).
   *    If FALSE, then non-string values will be preserved
   */
  public static function encodeInput(&$values, $castToString = TRUE) {
    return CRM_Utils_API_HTMLInputCoder::singleton()->encodeInput($values, $castToString);
  }

}
