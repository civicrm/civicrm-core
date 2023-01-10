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
class CRM_Extension_Container_Static implements CRM_Extension_Container_Interface {

  /**
   * @var array
   */
  protected $exts = [];

  /**
   * @param array $exts
   *   Array(string $key => array $spec) List of extensions.
   */
  public function __construct($exts) {
    $this->exts = $exts;
  }

  /**
   * @inheritDoc
   */
  public function checkRequirements() {
    return [];
  }

  /**
   * @inheritDoc
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @inheritDoc
   */
  public function getKeys() {
    return array_keys($this->exts);
  }

  /**
   * @inheritDoc
   */
  public function getPath($key) {
    $e = $this->getExt($key);
    return $e['path'];
  }

  /**
   * @inheritDoc
   */
  public function getResUrl($key) {
    $e = $this->getExt($key);
    return $e['resUrl'];
  }

  /**
   * @inheritDoc
   */
  public function refresh() {
  }

  /**
   * @param string $key
   *   Extension name.
   *
   * @throws CRM_Extension_Exception_MissingException
   */
  protected function getExt($key) {
    if (isset($this->exts[$key])) {
      return $this->exts[$key];
    }
    else {
      throw new CRM_Extension_Exception_MissingException("Missing extension: $key");
    }
  }

}
