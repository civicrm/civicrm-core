<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @package CiviCRM_Hook
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Utils_Http {

  /**
   * Parse the expiration time from a series of HTTP headers.
   *
   * @param array $headers
   * @return int|NULL
   *   Expiration tme as seconds since epoch, or NULL if not cacheable.
   */
  public static function parseExpiration($headers) {
    $headers = CRM_Utils_Array::rekey($headers, function ($k, $v) {
      return strtolower($k);
    });

    if (!empty($headers['cache-control'])) {
      $cc = self::parseCacheControl($headers['cache-control']);
      if ($cc['max-age'] && is_numeric($cc['max-age'])) {
        return CRM_Utils_Time::getTimeRaw() + $cc['max-age'];
      }
    }

    return NULL;
  }

  /**
   * @param string $value
   *   Ex: "max-age=86400, public".
   * @return array
   *   Ex: Array("max-age"=>86400, "public"=>1).
   */
  public static function parseCacheControl($value) {
    $result = array();

    $parts = preg_split('/, */', $value);
    foreach ($parts as $part) {
      if (strpos($part, '=') !== FALSE) {
        list ($key, $value) = explode('=', $part, 2);
        $result[$key] = $value;
      }
      else {
        $result[$part] = TRUE;
      }
    }

    return $result;
  }

}
