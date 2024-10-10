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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
   * @var CRM_Utils_Cache_Interface|null
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $cache;

  /**
   * @var string
   * The cache key used for any data stored by this container
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $cacheKey;

  /**
   * @var array
   * ($key => $relPath)
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $relPaths = FALSE;

  /**
   * @var array
   * ($key => $relUrl)
   *
   * Derived from $relPaths. On Unix systems (where file-paths and
   * URL-paths both use '/' separator), this isn't necessary. On Windows
   * systems, this is derived from $relPaths.
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $relUrls = FALSE;

  /**
   * @var array
   *   Array(function(CRM_Extension_Info $info): bool)
   *   List of callables which determine whether an extension is visible.
   *   Each function returns TRUE if the extension should be visible.
   */
  protected $filters = [];

  /**
   * @var int|null
   *   Maximum number of subdirectories to search.
   */
  protected $maxDepth;

  /**
   * @param string $baseDir
   *   Local path to the container.
   * @param string $baseUrl
   *   Public URL of the container.
   * @param CRM_Utils_Cache_Interface|null $cache
   *   Cache in which to store extension metadata.
   * @param string $cacheKey
   *   Unique name for this container.
   * @param int|null $maxDepth
   *   Maximum number of subdirectories to search.
   */
  public function __construct($baseDir, $baseUrl, ?CRM_Utils_Cache_Interface $cache = NULL, $cacheKey = NULL, ?int $maxDepth = NULL) {
    $this->cache = $cache;
    $this->cacheKey = $cacheKey;
    $this->baseDir = rtrim($baseDir, '/' . DIRECTORY_SEPARATOR);
    $this->baseUrl = rtrim($baseUrl, '/');
    $this->maxDepth = $maxDepth;
  }

  /**
   * @inheritDoc
   *
   * @return array
   */
  public function checkRequirements() {
    $errors = [];

    if (empty($this->baseDir) || !is_dir($this->baseDir)) {
      $errors[] = [
        'title' => ts('Invalid Base Directory'),
        'message' => ts('An extension container has been defined with a blank directory.'),
      ];
    }
    if (empty($this->baseUrl)) {
      $errors[] = [
        'title' => ts('Invalid Base URL'),
        'message' => ts('An extension container has been defined with a blank URL.'),
      ];
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
          [
            1 => $key,
            2 => CRM_Utils_System::url('civicrm/admin/setting/url', 'reset=1'),
          ]
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
        $this->relPaths = [];
        $infoPaths = CRM_Utils_File::findFiles($this->baseDir, 'info.xml', FALSE, $this->maxDepth);
        foreach ($infoPaths as $infoPath) {
          $relPath = CRM_Utils_File::relativize(dirname($infoPath), $this->baseDir);
          try {
            $info = CRM_Extension_Info::loadFromFile($infoPath);
          }
          catch (CRM_Extension_Exception_ParseException $e) {
            CRM_Core_Session::setStatus(ts('Parse error in extension %1: %2', [
              1 => ltrim($relPath, '/'),
              2 => $e->getMessage(),
            ]), '', 'error');
            CRM_Core_Error::debug_log_message("Parse error in extension " . ltrim($relPath, '/') . ": " . $e->getMessage());
            continue;
          }
          $visible = TRUE;
          foreach ($this->filters as $filter) {
            if (!$filter($info)) {
              $visible = FALSE;
              break;
            }
          }
          if ($visible) {
            $this->relPaths[$info->key] = $relPath;
          }
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
   * Register a filter which determine whether a copy of an extension
   * appears as available.
   *
   * @param callable $callable
   *   function(CRM_Extension_Info $info): bool
   *   Each function returns TRUE if the extension should be visible.
   * @return $this
   */
  public function addFilter($callable) {
    $this->filters[] = $callable;
    return $this;
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
    $relUrls = [];
    foreach ($relPaths as $key => $relPath) {
      $relUrls[$key] = str_replace($dirSep, '/', $relPath);
    }
    return $relUrls;
  }

}
