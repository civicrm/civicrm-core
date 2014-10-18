<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 */

/**
 * An extension container is a locally-accessible source tree which can be
 * scanned for extensions.
 */
interface CRM_Extension_Container_Interface {

  /**
   * Determine if any unmet requirements prevent use of this container.
   *
   * @return array list of error messages; empty if OK
   */
  public function checkRequirements();

  /**
   * Get a list of extensions available in this container
   *
   * @return array list of keys (strings)
   */
  public function getKeys();

  /**
   * Determine the main .php file for an extension
   *
   * @param string $key fully-qualified extension name
   * @return string local path to the extension's main .php file
   * @throws Exception
   */
  public function getPath($key);

  /**
   * Determine the base URL for resources provided by the extension
   *
   * @param string $key fully-qualified extension name
   * @return string public URL
   * @throws Exception
   */
  public function getResUrl($key);

  public function refresh();
}
