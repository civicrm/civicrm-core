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
   * @return \Psr\Http\Message\UriInterface
   */
  public static function parseUrl($url) {
    return new Uri($url);
  }

  /**
   * Unparse url back to a string.
   *
   * @param \Psr\Http\Message\UriInterface $parsed
   *
   * @return string
   */
  public static function unparseUrl(UriInterface $parsed) {
    return $parsed->__toString();
  }

  /**
   * Convert to a relative URL (if host/port matches).
   *
   * @param string $value
   * @param string|null $currentHostPort
   *   The value of HTTP_HOST. (NULL means "lookup HTTP_HOST")
   * @return string
   *   Either the relative version of $value (if on the same HTTP_HOST), or else
   *   the absolute version.
   */
  public static function toRelative(string $value, ?string $currentHostPort = NULL): string {
    $currentHostPort = $currentHostPort ?: $_SERVER['HTTP_HOST'] ?? NULL;

    if (preg_match(';^(//|http://|https://)([^/]*)(.*);', $value, $m)) {
      if ($m[2] === $currentHostPort) {
        return $m[3];
      }
    }

    return $value;
  }

  /**
   * Convert to an absolute URL (if relative).
   *
   * @param string $value
   * @param string|null $currentHostPort
   *   The value of HTTP_HOST. (NULL means "lookup HTTP_HOST")
   * @return string
   *   Either the relative version of $value (if on the same HTTP_HOST), or else
   *   the absolute version.
   */
  public static function toAbsolute(string $value, ?string $currentHostPort = NULL): string {
    if ($value[0] === '/') {
      $currentHostPort = $currentHostPort ?: $_SERVER['HTTP_HOST'] ?? NULL;
      $scheme = CRM_Utils_System::isSSL() ? 'https' : 'http';
      return $scheme . '://' . $currentHostPort . $value;
    }
    return $value;
  }

  /**
   * Change the CMS language prefix of a CiviCRM URL, e.g. /fr/civicrm/a?b=1 to /de/civicrm/a?b=1.
   *
   * @param string $url
   *   A root-relative or absolute URL.
   * @param string $basePath
   *   The CMS base path, with leading and trailing slash, e.g. '/' or '/drupal/'.
   * @param string[] $prefixes
   *   All language prefixes used by the CMS.
   * @param string $newPrefix
   *   The prefix the URL should have, or '' for none.
   * @param string[] $localHosts
   *   Hosts (with port, if any) whose absolute URLs may be changed.
   *
   * @return string
   *   The changed URL. URLs that aren't CiviCRM URLs on this site, or that contain %% tokens,
   *   are returned unchanged.
   */
  public static function replaceLanguagePrefix(string $url, string $basePath, array $prefixes, string $newPrefix, array $localHosts): string {
    if (str_contains($url, '%%')) {
      return $url;
    }
    if (preg_match(';^((?:https?:)?//([^/?#]*))(.*)$;', $url, $matches)) {
      if (!in_array($matches[2], $localHosts, TRUE)) {
        return $url;
      }
      [, $origin, , $rest] = $matches;
    }
    elseif (str_starts_with($url, '/')) {
      [$origin, $rest] = ['', $url];
    }
    else {
      return $url;
    }
    if (!str_starts_with($rest, $basePath)) {
      return $url;
    }

    $path = substr($rest, strlen($basePath));
    foreach (array_filter($prefixes, 'strlen') as $prefix) {
      if (str_starts_with($path, "$prefix/")) {
        $path = substr($path, strlen($prefix) + 1);
        break;
      }
    }
    // Only CiviCRM paths: other paths may be aliases, which differ by language.
    if (!preg_match(';^civicrm([/?#]|$);', $path)) {
      return $url;
    }
    return $origin . $basePath . ($newPrefix === '' ? '' : "$newPrefix/") . $path;
  }

  /**
   * @param string $url
   *   Ex: 'http://local.example.com:8080/foo/bar/whiz'
   * @return string
   *   Ex: 'http://local.example.com:8080'
   */
  public static function toOrigin(string $url): string {
    return \CRM_Utils_Url::unparseUrl(
      \CRM_Utils_Url::parseUrl($url)
        ->withUserInfo('')
        ->withPath('')
        ->withQuery('')
        ->withFragment('')
    );
  }

  /**
   * Parse an internal URL. Extract the CiviCRM route.
   *
   * @param string $pageUrl
   *   Ex: 'https://example.com/cms/civicrm/foo?id=1'
   * @param string|null $cmsRootUrl
   * @return array
   *   Ex: ['path' => 'civicrm/foo', 'query' => 'id=1']
   *
   *   Similar to parse_url(), this returns a key-value array.
   *   Keys are: 'path', 'query', 'fragment', 'user', 'pass'
   *   Keys are only returned if they have values. Unused elements are omitted.
   *
   *   Currently, this does not support detecting schemes (such as frontend or backend).
   * @throws \CRM_Core_Exception
   */
  public static function parseInternalRoute(string $pageUrl, ?string $cmsRootUrl = NULL): array {
    $cmsRootUrl ??= CIVICRM_UF_BASEURL;
    $cmsRootUrl = rtrim($cmsRootUrl, '/');

    $parsedRoot = parse_url($cmsRootUrl ?: Civi::paths()->getUrl('[cms.root]/.'));
    $parsedPage = parse_url($pageUrl);

    $result = [];

    // The scheme and host don't really matter for output, but the inputted values should be normal.
    if (!in_array($parsedPage['scheme'], ['http', 'https'])) {
      throw new \CRM_Core_Exception("Failed to parse internal URL. Invalid scheme.");
    }
    $hosts = [$_SERVER['HTTP_HOST'] ?? NULL, $parsedRoot['host']];
    if (!in_array($parsedPage['host'], $hosts)) {
      throw new \CRM_Core_Exception("Failed to parse internal URL. Unrecognized host.");
    }

    foreach (['user', 'pass', 'fragment'] as $passthru) {
      if (isset($parsedPage[$passthru])) {
        $result[$passthru] = $parsedPage[$passthru];
      }
    }

    if (isset($parsedPage['query'])) {
      $urlVar = CRM_Core_Config::singleton()->userFrameworkURLVar;
      parse_str($parsedPage['query'] ?? '', $queryParts);
      unset($parsedPage['query']);
      if (isset($queryParts[$urlVar])) {
        $result['path'] = $queryParts[$urlVar];
        unset($queryParts[$urlVar]);
      }
      if (!empty($queryParts)) {
        $result['query'] = http_build_query($queryParts);
      }
    }

    if (!isset($result['path']) && str_starts_with($parsedPage['path'], $parsedRoot['path'] ?? '')) {
      $result['path'] = substr($parsedPage['path'], 1 + strlen($parsedRoot['path'] ?? ''));
    }

    if (str_starts_with($result['path'] ?? '', 'civicrm/')) {
      return $result;
    }
    else {
      throw new CRM_Core_Exception('Failed to parse internal URL. Malformed path.');
    }

  }

  /**
   * Determine if $child is a descendent of $parent.
   *
   * Relative URLs mean that multiple strings may not
   *
   * @param string|\Psr\Http\Message\UriInterface|\Civi\Core\Url $child
   * @param string|\Psr\Http\Message\UriInterface|\Civi\Core\Url $parent
   * @return bool
   */
  public static function isChildOf($child, $parent): bool {
    $childRel = static::toRelative((string) $child);
    $parentRel = static::toRelative((string) $parent);
    return str_starts_with($childRel, $parentRel);
  }

}
