<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @inheritDoc
 */
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
