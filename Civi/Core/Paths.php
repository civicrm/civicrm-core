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
        return [
          'path' => \Civi::paths()->getPath('[civicrm.root]/packages/'),
          'url' => \Civi::paths()->getUrl('[civicrm.root]/packages/'),
        ];
      })
      ->register('civicrm.vendor', function () {
        return [
          'path' => \Civi::paths()->getPath('[civicrm.root]/vendor/'),
          'url' => \Civi::paths()->getUrl('[civicrm.root]/vendor/'),
        ];
      })
      ->register('civicrm.bower', function () {
        return [
          'path' => \Civi::paths()->getPath('[civicrm.root]/bower_components/'),
          'url' => \Civi::paths()->getUrl('[civicrm.root]/bower_components/'),
        ];
      })
      ->register('civicrm.files', function () {
        return \CRM_Core_Config::singleton()->userSystem->getDefaultFileStorage();
      })
      ->register('wp.frontend.base', function () {
        return ['url' => rtrim(CIVICRM_UF_BASEURL, '/') . '/'];
      })
      ->register('wp.frontend', function () use ($paths) {
        $config = \CRM_Core_Config::singleton();
        $suffix = defined('CIVICRM_UF_WP_BASEPAGE') ? CIVICRM_UF_WP_BASEPAGE : $config->wpBasePage;
        return [
          'url' => $paths->getVariable('wp.frontend.base', 'url') . $suffix,
        ];
      })
      ->register('wp.backend.base', function () {
        return ['url' => rtrim(CIVICRM_UF_BASEURL, '/') . '/wp-admin/'];
      })
      ->register('wp.backend', function () use ($paths) {
        return [
          'url' => $paths->getVariable('wp.backend.base', 'url') . 'admin.php',
        ];
      })
      ->register('cms', function () {
        return [
          'path' => \CRM_Core_Config::singleton()->userSystem->cmsRootPath(),
          'url' => \CRM_Utils_System::baseCMSURL(),
        ];
      })
      ->register('cms.root', function () {
        return [
          'path' => \CRM_Core_Config::singleton()->userSystem->cmsRootPath(),
          // Misleading: this *removes* the language part of the URL, producing a pristine base URL.
          'url' => \CRM_Utils_System::languageNegotiationURL(\CRM_Utils_System::baseCMSURL(), FALSE, TRUE),
        ];
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
      $this->variables[$name] = call_user_func($this->variableFactory[$name]);
      if (isset($GLOBALS['civicrm_paths'][$name])) {
        $this->variables[$name] = array_merge($this->variables[$name], $GLOBALS['civicrm_paths'][$name]);
      }
    }
    if (!isset($this->variables[$name][$attr])) {
      throw new \RuntimeException("Cannot resolve path using \"$name.$attr\"");
    }
    return $this->variables[$name][$attr];
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
    $defaultContainer = self::DEFAULT_PATH;
    if ($value && $value{0} == '[' && preg_match(';^\[([a-zA-Z0-9\._]+)\]/(.*);', $value, $matches)) {
      $defaultContainer = $matches[1];
      $value = $matches[2];
    }
    if (empty($value)) {
      return FALSE;
    }
    if ($value === '.') {
      $value = '';
    }
    return \CRM_Utils_File::absoluteDirectory($value, $this->getVariable($defaultContainer, 'path'));
  }

  /**
   * Determine the URL to a file.
   *
   * @param string $value
   *   The file path. The path may begin with a variable, e.g. "[civicrm.files]/upload".
   * @param string $preferFormat
   *   The preferred format ('absolute', 'relative').
   *   The result data may not meet the preference -- if the setting
   *   refers to an external domain, then the result will be
   *   absolute (regardless of preference).
   * @param bool|NULL $ssl
   *   NULL to autodetect. TRUE to force to SSL.
   * @return mixed|string
   */
  public function getUrl($value, $preferFormat = 'relative', $ssl = NULL) {
    $defaultContainer = self::DEFAULT_URL;
    if ($value && $value{0} == '[' && preg_match(';^\[([a-zA-Z0-9\._]+)\](/(.*))$;', $value, $matches)) {
      $defaultContainer = $matches[1];
      $value = empty($matches[3]) ? '.' : $matches[3];
    }

    if (empty($value)) {
      return FALSE;
    }
    if ($value === '.') {
      $value = '';
    }
    if (substr($value, 0, 4) == 'http') {
      return $value;
    }

    $value = $this->getVariable($defaultContainer, 'url') . $value;

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
