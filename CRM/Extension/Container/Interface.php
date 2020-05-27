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
interface CRM_Extension_Container_Interface {

  /**
   * Determine if any unmet requirements prevent use of this container.
   */
  public function checkRequirements();

  /**
   * Get a list of extensions available in this container.
   */
  public function getKeys();

  /**
   * Determine the main .php file for an extension
   *
   * @param string $key
   *   Fully-qualified extension name.
   */
  public function getPath($key);

  /**
   * Determine the base URL for resources provided by the extension.
   *
   * @param string $key
   *   Fully-qualified extension name.
   *
   * @throws \CRM_Extension_Exception_MissingException
   */
  public function getResUrl($key);

  /**
   * Scan the container for available extensions.
   */
  public function refresh();

}
