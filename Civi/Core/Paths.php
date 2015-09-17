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
  private $containers = array();

  protected $containerFactory = array();

  public function __construct() {
    $this
      //->register('civicrm', function () {
      //  return \CRM_Core_Config::singleton()->userSystem->getCiviSourceStorage();
      //})
      ->register('civicrm.root', function () {
        return \CRM_Core_Config::singleton()->userSystem->getCiviSourceStorage();
      })
      ->register('civicrm.files', function () {
        return \CRM_Core_Config::singleton()->userSystem->getDefaultFileStorage();
      })
      ->register('cms', function () {
        return array(
          'path' => \CRM_Core_Config::singleton()->userSystem->cmsRootPath(),
          'url' => \CRM_Utils_System::baseCMSURL(),
        );
      })
      ->register('cms.root', function () {
        return array(
          'path' => \CRM_Core_Config::singleton()->userSystem->cmsRootPath(),
          // Misleading: this *removes* the language part of the URL, producing a pristine base URL.
          'url' => \CRM_Utils_System::languageNegotiationURL(\CRM_Utils_System::baseCMSURL(), FALSE, TRUE),
        );
      });
  }

  /**
   * Register a new URL/file path mapping.
   *
   * @param string $name
   *   The name of the container.
   * @param callable $factory
   *   Function which returns an array with keys:
   *    - path: string.
   *    - url: string.
   * @return $this
   */
  public function register($name, $factory) {
    $this->containerFactory[$name] = $factory;
    return $this;
  }

  protected function getContainerAttr($name, $attr) {
    if (!isset($this->containers[$name])) {
      $this->containers[$name] = call_user_func($this->containerFactory[$name]);
    }
    if (!isset($this->containers[$name][$attr])) {
      throw new \RuntimeException("Cannot resolve path using \"$name.$attr\"");
    }
    return $this->containers[$name][$attr];
  }

  /**
   * Determine the absolute path to a file, given that the file is most likely
   * in a given particular container.
   *
   * @param string $value
   *   The file path (which is probably relative to $container).
   *   Use "." to reference to container root.
   *   Values may explicitly specify the a container, e.g. "[civicrm.files]/upload".
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
    return \CRM_Utils_File::absoluteDirectory($value, $this->getContainerAttr($defaultContainer, 'path'));
  }

  /**
   * Determine the absolute URL to a file, given that the file is most likely
   * in a given particular container.
   *
   * @param string $value
   *   The file path (which is probably relative to $container).
   *   Values may explicitly specify the a container, e.g. "[civicrm.files]/upload".
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

    $value = $this->getContainerAttr($defaultContainer, 'url') . $value;

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
