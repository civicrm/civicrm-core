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
 * $Id$
 *
 * Convenience class for PHP-Gettext compatibility.
 */
class CRM_Core_I18n_NativeGettext {

  /**
   * @param $string
   *
   * @return string
   */
  public function translate($string) {
    return gettext($string);
  }

  /**
   * Based on php-gettext, since native gettext does not support this as is.
   *
   * @param $context
   * @param $text
   *
   * @return string
   */
  public function pgettext($context, $text) {
    $key = $context . chr(4) . $text;
    $ret = $this->translate($key);

    if (strpos($ret, "\004") !== FALSE) {
      return $text;
    }
    else {
      return $ret;
    }
  }

  /**
   * @param $text
   * @param $plural
   * @param $count
   *
   * @return string
   */
  public function ngettext($text, $plural, $count) {
    return ngettext($text, $plural, $count);
  }

}
