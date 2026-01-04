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
 * @package CiviCRM_Hook
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Http {

  /**
   * Parse the expiration time from a series of HTTP headers.
   *
   * @param array $headers
   * @return int|null
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
    $result = [];

    $parts = preg_split('/, */', $value);
    foreach ($parts as $part) {
      if (str_contains($part, '=')) {
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
