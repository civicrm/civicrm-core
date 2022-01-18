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
 * SameSite Utility Class.
 *
 * Determines if the current User Agent can handle the `SameSite=None` parameter
 * by mapping against known incompatible clients.
 *
 * Sample code:
 *
 * // Get User Agent string.
 * $rawUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
 * $userAgent = mb_convert_encoding($rawUserAgent, 'UTF-8');
 *
 * // Get boolean representing User Agent compatibility.
 * $shouldUseSameSite = CRM_Utils_SameSite::shouldSendSameSiteNone($userAgent);
 *
 * Based on code provided by "The Chromium Projects".
 *
 * @see https://www.chromium.org/updates/same-site/incompatible-clients
 */
class CRM_Utils_SameSite {

  /**
   * Determine if the current User Agent can handle the `SameSite=None` parameter.
   *
   * @param string $userAgent
   * @return bool True if the User Agent is compatible, FALSE otherwise.
   */
  public static function shouldSendSameSiteNone($userAgent) {
    return !self::isSameSiteNoneIncompatible($userAgent);
  }

  /**
   * Detect classes of browsers known to be incompatible.
   *
   * @param string $userAgent The User Agent.
   * @return bool True if the User Agent is determined to be incompatible, FALSE otherwise.
   */
  private static function isSameSiteNoneIncompatible($userAgent) {
    return self::hasWebKitSameSiteBug($userAgent) ||
           self::dropsUnrecognizedSameSiteCookies($userAgent);
  }

  /**
   * Detect versions of Safari and embedded browsers on MacOS 10.14 and all
   * browsers on iOS 12.
   *
   * These versions will erroneously treat cookies marked with `SameSite=None`
   * as if they were marked `SameSite=Strict`.
   *
   * @param string $userAgent The User Agent.
   * @return bool
   */
  private static function hasWebKitSameSiteBug($userAgent) {
    return self::isIosVersion(12, $userAgent) || (self::isMacosxVersion(10, 14, $userAgent) &&
           (self::isSafari($userAgent) || self::isMacEmbeddedBrowser($userAgent)));
  }

  /**
   * Detect versions of UC Browser on Android prior to version 12.13.2.
   *
   * Older versions will reject a cookie with `SameSite=None`. This behavior was
   * correct according to the version of the cookie specification at that time,
   * but with the addition of the new "None" value to the specification, this
   * behavior has been updated in newer versions of UC Browser.
   *
   * @param string $userAgent The User Agent.
   * @return bool
   */
  private static function dropsUnrecognizedSameSiteCookies($userAgent) {
    if (self::isUcBrowser($userAgent)) {
      return !self::isUcBrowserVersionAtLeast(12, 13, 2, $userAgent);
    }

    return self::isChromiumBased($userAgent) &&
           self::isChromiumVersionAtLeast(51, $userAgent, '>=') &&
           self::isChromiumVersionAtLeast(67, $userAgent, '<=');
  }

  /**
   * Detect iOS version.
   *
   * @param int $major The major version to test.
   * @param string $userAgent The User Agent.
   * @return bool
   */
  private static function isIosVersion($major, $userAgent) {
    $regex = "/\(iP.+; CPU .*OS (\d+)[_\d]*.*\) AppleWebKit\//";
    $matched = [];

    if (preg_match($regex, $userAgent, $matched)) {
      // Extract digits from first capturing group.
      $version = (int) $matched[1];
      return version_compare($version, $major, '<=');
    }

    return FALSE;
  }

  /**
   * Detect MacOS version.
   *
   * @param int $major The major version to test.
   * @param int $minor The minor version to test.
   * @param string $userAgent The User Agent.
   * @return bool
   */
  private static function isMacosxVersion($major, $minor, $userAgent) {
    $regex = "/\(Macintosh;.*Mac OS X (\d+)_(\d+)[_\d]*.*\) AppleWebKit\//";
    $matched = [];

    if (preg_match($regex, $userAgent, $matched)) {
      // Extract digits from first and second capturing groups.
      return version_compare((int) $matched[1], $major, '=') &&
             version_compare((int) $matched[2], $minor, '<=');
    }

    return FALSE;
  }

  /**
   * Detect MacOS Safari.
   *
   * @param string $userAgent The User Agent.
   * @return bool
   */
  private static function isSafari($userAgent) {
    $regex = "/Version\/.* Safari\//";
    return preg_match($regex, $userAgent) && !self::isChromiumBased($userAgent);
  }

  /**
   * Detect MacOS embedded browser.
   *
   * @param string $userAgent The User Agent.
   * @return FALSE|int
   */
  private static function isMacEmbeddedBrowser($userAgent) {
    $regex = "/^Mozilla\/[\.\d]+ \(Macintosh;.*Mac OS X [_\d]+\) AppleWebKit\/[\.\d]+ \(KHTML, like Gecko\)$/";
    return preg_match($regex, $userAgent);
  }

  /**
   * Detect if browser is Chromium based.
   *
   * @param string $userAgent The User Agent.
   * @return FALSE|int
   */
  private static function isChromiumBased($userAgent) {
    $regex = "/Chrom(e|ium)/";
    return preg_match($regex, $userAgent);
  }

  /**
   * Detect if Chromium version meets requirements.
   *
   * @param int $major The major version to test.
   * @param string $userAgent The User Agent.
   * @param string $operator
   * @return bool|int
   */
  private static function isChromiumVersionAtLeast($major, $userAgent, $operator) {
    $regex = "/Chrom[^ \/]+\/(\d+)[\.\d]* /";
    $matched = [];

    if (preg_match($regex, $userAgent, $matched)) {
      // Extract digits from first capturing group.
      $version = (int) $matched[1];
      return version_compare($version, $major, $operator);
    }
    return FALSE;
  }

  /**
   * Detect UCBrowser.
   *
   * @param string $userAgent The User Agent.
   * @return FALSE|int
   */
  private static function isUcBrowser($userAgent) {
    $regex = "/UCBrowser\//";
    return preg_match($regex, $userAgent);
  }

  /**
   * Detect if UCBrowser version meets requirements.
   *
   * @param int $major The major version to test.
   * @param int $minor The minor version to test.
   * @param int $build The build version to test.
   * @param string $userAgent The User Agent.
   * @return bool|int
   */
  private static function isUcBrowserVersionAtLeast($major, $minor, $build, $userAgent) {
    $regex = "/UCBrowser\/(\d+)\.(\d+)\.(\d+)[\.\d]* /";
    $matched = [];

    if (preg_match($regex, $userAgent, $matched)) {
      // Extract digits from three capturing groups.
      $majorVersion = (int) $matched[1];
      $minorVersion = (int) $matched[2];
      $buildVersion = (int) $matched[3];

      if (version_compare($majorVersion, $major, '>=')) {
        if (version_compare($minorVersion, $minor, '>=')) {
          return version_compare($buildVersion, $build, '>=');
        }
      }
    }

    return FALSE;
  }

}
