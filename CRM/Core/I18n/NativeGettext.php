<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
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
  function translate($string) {
    return gettext($string);
  }

  /**
   * Based on php-gettext, since native gettext does not support this as is.
   */
  function pgettext($context, $text) {
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
  function ngettext($text, $plural, $count) {
    return ngettext($text, $plural, $count);
  }
}

