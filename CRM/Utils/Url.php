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

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class CRM_Utils_Url {

  /**
   * Parse url to a UriInterface.
   *
   * @param string $url
   *
   * @return \GuzzleHttp\Psr7\UriInterface
   */
  public static function parseUrl($url) {
    return new Uri($url);
  }

  /**
   * Unparse url back to a string.
   *
   * @param \GuzzleHttp\Psr7\UriInterface $parsed
   *
   * @return string
   */
  public static function unparseUrl(UriInterface $parsed) {
    return $parsed->__toString();
  }

}
