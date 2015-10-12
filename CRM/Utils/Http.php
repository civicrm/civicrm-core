<?php

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
