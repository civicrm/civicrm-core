<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 *
 * CRM_Utils_Cache_Interface
 *
 * PHP-FIG has been developing a draft standard for caching,
 * PSR-6. The standard has not been ratified yet. When
 * making changes to this interface, please take care to
 * avoid *conflicst* with PSR-6's CacheItemPoolInterface. At
 * time of writing, they do not conflict. Avoiding conflicts
 * will enable more transition paths where Civi
 * simultaneously supports both interfaces in the same
 * implementation.
 *
 * For example, the current interface defines:
 *
 *   function get($key) => mixed $value
 *
 * and PSR-6 defines:
 *
 *   function getItem($key) => ItemInterface $item
 *
 * These are different styles (e.g. "weak item" vs "strong item"),
 * but the two methods do not *conflict*. They can coexist,
 * and you can trivially write adapters between the two.
 *
 * @see https://github.com/php-fig/fig-standards/blob/master/proposed/cache.md
 */
interface CRM_Utils_Cache_Interface {

  /**
   * Set the value in the cache.
   *
   * @param string $key
   * @param mixed $value
   */
  public function set($key, &$value);

  /**
   * Get a value from the cache.
   *
   * @param string $key
   * @return mixed
   *   NULL if $key has not been previously set
   */
  public function get($key);

  /**
   * Delete a value from the cache.
   *
   * @param string $key
   */
  public function delete($key);

  /**
   * Delete all values from the cache.
   */
  public function flush();

}
