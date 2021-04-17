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
 * Track minimal information which identifies the target extension.
 */
trait CRM_Extension_Upgrader_IdentityTrait {

  /**
   * @var string
   *   eg 'com.example.myextension'
   */
  protected $extensionName;

  /**
   * @var string
   *   full path to the extension's source tree
   */
  protected $extensionDir;

  /**
   * {@inheritDoc}
   */
  public function init(array $params) {
    $this->extensionName = $params['key'];
    $system = CRM_Extension_System::singleton();
    $mapper = $system->getMapper();
    $this->extensionDir = $mapper->keyToBasePath($this->extensionName);
  }

  /**
   * @return string
   *   Ex: 'org.example.foobar'
   */
  public function getExtensionKey() {
    return $this->extensionName;
  }

  /**
   * @return string
   *   Ex: '/var/www/sites/default/ext/org.example.foobar'
   */
  public function getExtensionDir() {
    return $this->extensionDir;
  }

}
