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
 * "Mixins" allow extensions to be initialized with small, reusable chunks of code.
 *
 * Example: A mixin might scan an extension for YAML files, aggregate them, add that
 * to the boot-cache, and use the results to register event-listeners during initialization.
 *
 * Mixins have the following characteristics:
 *
 * - They are defined by standalone PHP files, e.g. `civix@1.0.2.mixin.php`
 * - They are implicitly versioned via strict SemVer. (`1.1.0` can replace `1.0.0`; `2.0.0` and `1.0.0` are separate/parallel things).
 * - They are activated via `info.xml` (`<mix>civix@1.0</mix>`).
 * - They may be copied/reproduced in multiple extensions.
 * - They are de-duped - such that a major-version (eg `civix@1` or `civix@2`) is only loaded once.
 *
 * The "MixInfo" record tracks the mixins needed by an extension. You may consider this an
 * optimized subset of the 'info.xml'. (The mix-info is loaded on every page-view, so this
 * record is serialized and stored in the MixinLoader cache.)
 */
class CRM_Extension_MixInfo {

  /**
   * @var string
   *
   * Ex: 'org.civicrm.flexmailer'
   */
  public $longName;

  /**
   * @var string
   *
   * Ex: 'flexmailer'
   */
  public $shortName;

  /**
   * @var string|null
   *
   * Ex: '/var/www/modules/civicrm/ext/flexmailer'.
   */
  public $path;

  /**
   * @var array
   *   Ex: ['civix@2.0', 'menu@1.0']
   */
  public $mixins;

  /**
   * Get a path relative to the target extension.
   *
   * @param string $relPath
   * @return string
   */
  public function getPath($relPath = NULL) {
    return $relPath === NULL ? $this->path : $this->path . DIRECTORY_SEPARATOR . ltrim($relPath, '/');
  }

  public function isActive() {
    return \CRM_Extension_System::singleton()->getMapper()->isActiveModule($this->shortName);
  }

}
