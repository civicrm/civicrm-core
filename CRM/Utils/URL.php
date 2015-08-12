<?php

class CRM_Utils_URL {

  /**
   * Create a partial URL, based only pieces of the original URL.
   *
   * @param string $url
   *   Ex: 'http://user:pass@host:port/path'.
   * @param array $hideParts
   *   Ex: array('user','pass).
   * @return string $url
   *   Ex: 'http://host:port@path'.
   */
  public static function mask($url, $hideParts = array()) {
    $parts = parse_url($url);
    $r = '';
    if (!empty($parts['scheme']) && !in_array('scheme', $hideParts)) {
      $r .= $parts['scheme'] . '://';
    }

    $hasCred = FALSE;
    if (!empty($parts['user']) && !in_array('user', $hideParts)) {
      $r .= $parts['user'];
      $hasCred = TRUE;
    }
    if (!empty($parts['pass']) && !in_array('pass', $hideParts)) {
      $r .= ':' . $parts['pass'];
      $hasCred = TRUE;
    }
    if ($hasCred) {
      $r .= '@';
    }

    if (!empty($parts['host']) && !in_array('host', $hideParts)) {
      $r .= $parts['host'];
    }
    if (!empty($parts['port']) && !in_array('port', $hideParts)) {
      $r .= ':' . $parts['port'];
    }
    if (!empty($parts['path']) && !in_array('path', $hideParts)) {
      $r .= $parts['path'];
    }
    if (!empty($parts['query']) && !in_array('query', $hideParts)) {
      $r .= '?' . $parts['query'];
    }
    if (!empty($parts['fragment']) && !in_array('fragment', $hideParts)) {
      $r .= '#' . $parts['fragment'];
    }
    return $r;
  }

}
