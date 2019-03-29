<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * Manage translatable strings on behalf of resource files.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 */
class CRM_Core_Resources_Strings {

  /**
   * @var CRM_Utils_Cache_Interface|NULL
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
   * @return array
   *   List of translatable strings.
   */
  public function get($bucket, $file, $format) {
    $stringsByFile = $this->cache->get($bucket); // array($file => array(...strings...))
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
   * @throws Exception
   */
  public function extract($file, $format) {
    switch ($format) {
      case 'text/javascript':
        return CRM_Utils_JS::parseStrings(file_get_contents($file));

      case 'text/html':
        // Magic! The JS parser works with HTML! See CRM_Utils_HTMLTest.
        return CRM_Utils_JS::parseStrings(file_get_contents($file));

      default:
        throw new Exception("Cannot extract strings: Unrecognized file type.");
    }
  }

}
