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
 * Class CRM_Core_Resources_Bundle
 *
 * A bundle is a collection of web resources with the following details:
 * - Only scripts, styles, and settings are allowed. Free-form markup is not.
 * - Resources *may* have a 'region'. Hopefully, this is not necessary for most bundles.
 * - If no 'region' is given, then CRM_Core_Resources will pick a default at activation time.
 */
class CRM_Core_Resources_Bundle implements CRM_Core_Resources_CollectionInterface {

  use CRM_Core_Resources_CollectionTrait;

  /**
   * Symbolic name for this bundle.
   *
   * @var string|null
   */
  public $name;

  /**
   * @param string|NULL $name
   * @param string[]|NULL $types
   *   List of resource-types to permit in this bundle. NULL for a default list.
   */
  public function __construct($name = NULL, $types = NULL) {
    $this->name = $name;
    $this->types = $types ?: ['script', 'scriptFile', 'scriptUrl', 'settings', 'style', 'styleFile', 'styleUrl'];
  }

}
