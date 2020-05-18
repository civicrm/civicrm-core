<?php
namespace Civi\Core;

/**
 * Class Paths
 * @package Civi\Core
 *
 * This paths class translates path-expressions into local file paths and
 * URLs. Path-expressions may take a few forms:
 *
 *  - Paths and URLs may use a variable prefix. For example, '[civicrm.files]/upload'
 *  - Paths and URLS may be absolute.
 *  - Paths may be relative (base dir: [civicrm.files]).
 *  - URLs may be relative (base dir: [cms.root]).
 */
class Paths {

  const DEFAULT_URL = 'cms.root';
  const DEFAULT_PATH = 'civicrm.files';

  /**
   * @var array
   *   Array(string $name => array(url => $, path => $)).
   */
  private $variables = [];

  private $variableFactory = [];

  /**
   * Class constructor.
   */
  public function __construct() {
    $paths = $this;
    $this
      ->register('civicrm.root', function () {
        return \CRM_Core_Config::singleton()->userSystem->getCiviSourceStorage();
      })
      ->register('civicrm.packages', function () {
        return \CRM_Core_Config::singleton()->userSystem->getCiviPackagesStorage();
      })
      ->register('civicrm.vendor', function () {
        return \CRM_Core_Config::singleton()->userSystem->getCiviVendorStorage();
      })
      ->register('civicrm.bower', function () {
        return \CRM_Core_Config::singleton()->userSystem->getCiviBowerStorage();
      })
      ->register('civicrm.files', function () {
        return \CRM_Core_Config::singleton()->userSystem->getDefaultFileStorage();
      })
      ->register('civicrm.private', function () {
        return \CRM_Core_Config::singleton()->userSystem->getCiviPrivateStorage();
      })
      ->register('civicrm.log', function () {
        return \CRM_Core_Config::singleton()->userSystem->getCiviConfigAndLogStorage();
      })
      ->register('civicrm.compile', function () {
        return \CRM_Core_Config::singleton()->userSystem->getCiviCompileDirectory();
      })
      ->register('civicrm.l10n', function () {
        return \CRM_Core_Config::singleton()->userSystem->getCiviL10nDirectory();
      })
      ->register('wp.frontend.base', function () {
        return \CRM_Core_Config::singleton()->userSystem->getWPFrontendBase();
      })
      ->register('wp.frontend', function () use ($paths) {
        return \CRM_Core_Config::singleton()->userSystem->getWPFrontend($paths);
      })
      ->register('wp.backend.base', function () {
        return \CRM_Core_Config::singleton()->userSystem->getWPBackendBase();
      })
      ->register('wp.backend', function () use ($paths) {
        return \CRM_Core_Config::singleton()->userSystem->getWPBackend($paths);
      })
      ->register('cms', function () {
        return \CRM_Core_Config::singleton()->userSystem->getCMSPathAndURL();
      })
      ->register('cms.root', function () {
        return \CRM_Core_Config::singleton()->userSystem->getCMSRootPathAndURL();
      });
  }

  /**
   * Register a new URL/file path mapping.
   *
   * @param string $name
   *   The name of the variable.
   * @param callable $factory
   *   Function which returns an array with keys:
   *    - path: string.
   *    - url: string.
   * @return Paths
   */
  public function register($name, $factory) {
    $this->variableFactory[$name] = $factory;
    return $this;
  }

  /**
   * @param string $name
   *   Ex: 'civicrm.root'.
   * @param string $attr
   *   Ex: 'url', 'path'.
   * @return mixed
   */
  public function getVariable($name, $attr) {
    if (!isset($this->variables[$name])) {
      // Try looking in $GLOBALS first.
      if (isset($GLOBALS['civicrm_paths'][$name])) {
        if (isset($this->variables[$name])) {
          $this->variables[$name] = array_merge($this->variables[$name], $GLOBALS['civicrm_paths'][$name]);
        }
        else {
          $this->variables[$name] = $GLOBALS['civicrm_paths'][$name];
        }
      }
      // If nothing is found in $GLOBALS, call closure.
      if (!isset($this->variables[$name])) {
        $this->variables[$name] = call_user_func($this->variableFactory[$name]);
      }
      if (isset($this->variables[$name]['url'])) {
        // Typical behavior is to return an absolute URL. If an admin has put an override that's site-relative, then convert.
        $this->variables[$name]['url'] = $this->toAbsoluteUrl($this->variables[$name]['url'], $name);
      }
    }
    if (!isset($this->variables[$name][$attr])) {
      throw new \RuntimeException("Cannot resolve path using \"$name.$attr\"");
    }
    return $this->variables[$name][$attr];
  }

  /**
   * @param string $url
   *   Ex: 'https://example.com:8000/foobar' or '/foobar'
   * @param string $for
   *   Ex: 'civicrm.root' or 'civicrm.packages'
   * @return string
   */
  private function toAbsoluteUrl($url, $for) {
    if (!$url) {
      return $url;
    }
    elseif ($url[0] === '/') {
      // Relative URL interpretation
      if ($for === 'cms.root') {
        throw new \RuntimeException('Invalid configuration: the [cms.root] path must be an absolute URL');
      }
      $cmsUrl = rtrim($this->getVariable('cms.root', 'url'), '/');
      // The norms for relative URLs dictate:
      // Single-slash: "/sub/dir" or "/" (domain-relative)
      // Double-slash: "//example.com/sub/dir" (same-scheme)
      $prefix = ($url === '/' || $url[1] !== '/')
        ? $cmsUrl
        : (parse_url($cmsUrl, PHP_URL_SCHEME) . ':');
      return $prefix . $url;
    }
    else {
      // Assume this is an absolute URL, as in the past ('_h_ttp://').
      return $url;
    }
  }

  /**
   * Does the variable exist.
   *
   * @param string $name
   *
   * @return bool
   */
  public function hasVariable($name) {
    return isset($this->variableFactory[$name]);
  }

  /**
   * Determine the absolute path to a file, given that the file is most likely
   * in a given particular variable.
   *
   * @param string $value
   *   The file path.
   *   Use "." to reference to default file root.
   *   Values may begin with a variable, e.g. "[civicrm.files]/upload".
   * @return mixed|string
   */
  public function getPath($value) {
    if ($value === NULL || $value === FALSE || $value === '') {
      return FALSE;
    }

    $defaultContainer = self::DEFAULT_PATH;
    if ($value && $value{0} == '[' && preg_match(';^\[([a-zA-Z0-9\._]+)\]/(.*);', $value, $matches)) {
      $defaultContainer = $matches[1];
      $value = $matches[2];
    }

    $isDot = $value === '.';
    if ($isDot) {
      $value = '';
    }

    $result = \CRM_Utils_File::absoluteDirectory($value, $this->getVariable($defaultContainer, 'path'));
    return $isDot ? rtrim($result, '/' . DIRECTORY_SEPARATOR) : $result;
  }

  /**
   * Determine the URL to a file.
   *
   * @param string $value
   *   The file path. The path may begin with a variable, e.g. "[civicrm.files]/upload".
   *
   *   This function was designed for locating files under a given tree, and the
   *   the result for a straight variable expressions ("[foo.bar]") was not
   *   originally defined. You may wish to use one of these:
   *
   *   - getVariable('foo.bar', 'url') => Lookup variable by itself
   *   - getUrl('[foo.bar]/') => Get the variable (normalized with a trailing "/").
   *   - getUrl('[foo.bar]/.') => Get the variable (normalized without a trailing "/").
   * @param string $preferFormat
   *   The preferred format ('absolute', 'relative').
   *   The result data may not meet the preference -- if the setting
   *   refers to an external domain, then the result will be
   *   absolute (regardless of preference).
   * @param bool|NULL $ssl
   *   NULL to autodetect. TRUE to force to SSL.
   * @return FALSE|string
   *   The URL for $value (string), or FALSE if the $value is not specified.
   */
  public function getUrl($value, $preferFormat = 'relative', $ssl = NULL) {
    if ($value === NULL || $value === FALSE || $value === '') {
      return FALSE;
    }

    $defaultContainer = self::DEFAULT_URL;
    if ($value && $value{0} == '[' && preg_match(';^\[([a-zA-Z0-9\._]+)\](/(.*))$;', $value, $matches)) {
      $defaultContainer = $matches[1];
      $value = $matches[3];
    }

    $isDot = $value === '.';
    if (substr($value, 0, 5) === 'http:' || substr($value, 0, 6) === 'https:') {
      return $value;
    }

    $value = rtrim($this->getVariable($defaultContainer, 'url'), '/') . ($isDot ? '' : "/$value");

    if ($preferFormat === 'relative') {
      $parsed = parse_url($value);
      if (isset($_SERVER['HTTP_HOST']) && isset($parsed['host']) && $_SERVER['HTTP_HOST'] == $parsed['host']) {
        $value = $parsed['path'];
      }
    }

    if ($ssl || ($ssl === NULL && \CRM_Utils_System::isSSL())) {
      $value = str_replace('http://', 'https://', $value);
    }

    return $value;
  }

}
