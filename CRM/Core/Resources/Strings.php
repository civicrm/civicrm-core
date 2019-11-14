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
 * Manage translatable strings on behalf of resource files.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_Resources_Strings {

  /**
   * Cache.
   *
   * @var CRM_Utils_Cache_Interface|null
   */
  private $cache = NULL;

  /**
   * @param CRM_Utils_Cache_Interface $cache
   *   Localization cache.
   */
  public function __construct($cache) {
    $this->cache = $cache;
  }

  /**
   * Flush the cache of translated strings.
   */
  public function flush() {
    $this->cache->flush();
  }

  /**
   * Get the strings from a file, using a cache if available.
   *
   * @param string $bucket
   *   The name of a cache-row which includes strings for this file.
   * @param string $file
   *   File path.
   * @param string $format
   *   Type of file (e.g. 'text/javascript', 'text/html').
   *
   * @return array
   *   List of translatable strings.
   *
   * @throws \CRM_Core_Exception
   */
  public function get($bucket, $file, $format) {
    // array($file => array(...strings...))
    $stringsByFile = $this->cache->get($bucket);
    if (!$stringsByFile) {
      $stringsByFile = [];
    }
    if (!isset($stringsByFile[$file])) {
      if ($file && is_readable($file)) {
        $stringsByFile[$file] = $this->extract($file, $format);
      }
      else {
        $stringsByFile[$file] = [];
      }
      $this->cache->set($bucket, $stringsByFile);
    }
    return $stringsByFile[$file];
  }

  /**
   * Extract a list of strings from a file.
   *
   * @param string $file
   *   File path.
   * @param string $format
   *   Type of file (e.g. 'text/javascript', 'text/html').
   * @return array
   *   List of translatable strings.
   *
   * @throws CRM_Core_Exception
   */
  public function extract($file, $format) {
    switch ($format) {
      case 'text/javascript':
        return CRM_Utils_JS::parseStrings(file_get_contents($file));

      case 'text/html':
        // Magic! The JS parser works with HTML! See CRM_Utils_HTMLTest.
        return CRM_Utils_JS::parseStrings(file_get_contents($file));

      default:
        throw new CRM_Core_Exception('Cannot extract strings: Unrecognized file type.');
    }
  }

}
