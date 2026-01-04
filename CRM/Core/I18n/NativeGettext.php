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
 * Convenience class for PHP-Gettext compatibility.
 */
class CRM_Core_I18n_NativeGettext {

  /**
   * @param string $string
   *
   * @return string
   */
  public function translate($string) {
    return gettext($string);
  }

  /**
   * Based on php-gettext, since native gettext does not support this as is.
   *
   * @param string $context
   * @param string $text
   *
   * @return string
   */
  public function pgettext($context, $text) {
    $key = $context . chr(4) . $text;
    $ret = $this->translate($key);

    if (str_contains($ret, "\004")) {
      return $text;
    }
    else {
      return $ret;
    }
  }

  /**
   * @param string $text
   * @param string $plural
   * @param int $count
   *
   * @return string
   */
  public function ngettext($text, $plural, $count) {
    return ngettext($text, $plural, $count);
  }

}
