<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * An extension container is a locally-accessible source tree which can be
 * scanned for extensions.
 */
class CRM_Extension_Container_Basic implements CRM_Extension_Container_Interface {

  /**
   * @var string
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $baseDir;

  /**
   * @var string
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $baseUrl;

  /**
   * @var CRM_Utils_Cache_Interface|NULL
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $cache;

  /**
   * @var string the cache key used for any data stored by this container
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $cacheKey;

  /**
   * @var array($key => $relPath)
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $relPaths = FALSE;

  /**
   * @var array($key => $relUrl)
   *
   * Derived from $relPaths. On Unix systems (where file-paths and
   * URL-paths both use '/' separator), this isn't necessary. On Windows
   * systems, this is derived from $relPaths.
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $relUrls = FALSE;

  /**
   * @param string $baseDir
   *   Local path to the container.
   * @param string $baseUrl
   *   Public URL of the container.
   * @param CRM_Utils_Cache_Interface $cache
   *   Cache in which to store extension metadata.
   * @param string $cacheKey
   *   Unique name for this container.
   */
  public function __construct($baseDir, $baseUrl, CRM_Utils_Cache_Interface $cache = NULL, $cacheKey = NULL) {
    $this->cache = $cache;
    $this->cacheKey = $cacheKey;
    $this->baseDir = rtrim($baseDir, '/');
    $this->baseUrl = rtrim($baseUrl, '/');
  }

  /**
   * @inheritDoc
   *
   * @return array
   */
  public function checkRequirements() {
    $errors = array();

    if (empty($this->baseDir) || !is_dir($this->baseDir)) {
      $errors[] = array(
        'title' => ts('Invalid Base Directory'),
        'message' => ts('An extension container has been defined with a blank directory.'),
      );
    }
    if (empty($this->baseUrl)) {
      $errors[] = array(
        'title' => ts('Invalid Base URL'),
        'message' => ts('An extension container has been defined with a blank URL.'),
      );
    }

    return $errors;
  }

  /**
   * @inheritDoc
   *
   * @return array_keys
   */
  public function getKeys() {
    return array_keys($this->getRelPaths());
  }

  /**
   * @inheritDoc
   */
  public function getPath($key) {
    return $this->baseDir . $this->getRelPath($key);
  }

  /**
   * @inheritDoc
   */
  public function getResUrl($key) {
    if (!$this->baseUrl) {
      CRM_Core_Session::setStatus(
        ts('Failed to determine URL for extension (%1). Please update <a href="%2">Resource URLs</a>.',
          array(
            1 => $key,
            2 => CRM_Utils_System::url('civicrm/admin/setting/url', 'reset=1'),
          )
        )
      );
    }
    return $this->baseUrl . $this->getRelUrl($key);
  }

  /**
   * @inheritDoc
   */
  public function refresh() {
    $this->relPaths = NULL;
    if ($this->cache) {
      $this->cache->delete($this->cacheKey);
    }
  }

  /**
   * @return string
   */
  public function getBaseDir() {
    return $this->baseDir;
  }

  /**
   * Determine the relative path of an extension directory.
   *
   * @param string $key
   *   Extension name.
   *
   * @throws CRM_Extension_Exception_MissingException
   * @return string
   */
  protected function getRelPath($key) {
    $keypaths = $this->getRelPaths();
    if (!isset($keypaths[$key])) {
      throw new CRM_Extension_Exception_MissingException("Failed to find extension: $key");
    }
    return $keypaths[$key];
  }

  /**
   * Scan $basedir for a list of extension-keys
   *
   * @return array
   *   ($key => $relPath)
   */
  protected function getRelPaths() {
    if (!is_array($this->relPaths)) {
      if ($this->cache) {
        $this->relPaths = $this->cache->get($this->cacheKey);
      }
      if (!is_array($this->relPaths)) {
        $this->relPaths = array();
        $infoPaths = CRM_Utils_File::findFiles($this->baseDir, 'info.xml');
        foreach ($infoPaths as $infoPath) {
          $relPath = CRM_Utils_File::relativize(dirname($infoPath), $this->baseDir);
          try {
            $info = CRM_Extension_Info::loadFromFile($infoPath);
          }
          catch (CRM_Extension_Exception_ParseException $e) {
            CRM_Core_Session::setStatus(ts('Parse error in extension: %1', array(
              1 => $e->getMessage(),
            )), '', 'error');
            CRM_Core_Error::debug_log_message("Parse error in extension: " . $e->getMessage());
            continue;
          }
          $this->relPaths[$info->key] = $relPath;
        }
        if ($this->cache) {
          $this->cache->set($this->cacheKey, $this->relPaths);
        }
      }
    }
    return $this->relPaths;
  }

  /**
   * Determine the relative path of an extension directory.
   *
   * @param string $key
   *   Extension name.
   *
   * @throws CRM_Extension_Exception_MissingException
   * @return string
   */
  protected function getRelUrl($key) {
    $relUrls = $this->getRelUrls();
    if (!isset($relUrls[$key])) {
      throw new CRM_Extension_Exception_MissingException("Failed to find extension: $key");
    }
    return $relUrls[$key];
  }

  /**
   * Scan $basedir for a list of extension-keys
   *
   * @return array
   *   ($key => $relUrl)
   */
  protected function getRelUrls() {
    if (DIRECTORY_SEPARATOR == '/') {
      return $this->getRelPaths();
    }
    if (!is_array($this->relUrls)) {
      $this->relUrls = self::convertPathsToUrls(DIRECTORY_SEPARATOR, $this->getRelPaths());
    }
    return $this->relUrls;
  }

  /**
   * Convert a list of relative paths to relative URLs.
   *
   * Note: Treat as private. This is only public to facilitate testing.
   *
   * @param string $dirSep
   *   Directory separator ("/" or "\").
   * @param array $relPaths
   *   Array($key => $relPath).
   * @return array
   *   Array($key => $relUrl).
   */
  public static function convertPathsToUrls($dirSep, $relPaths) {
    $relUrls = array();
    foreach ($relPaths as $key => $relPath) {
      $relUrls[$key] = str_replace($dirSep, '/', $relPath);
    }
    return $relUrls;
  }

}
