<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * An extension container is a locally-accessible source tree which can be
 * scanned for extensions.
 */
class CRM_Extension_Container_Static implements CRM_Extension_Container_Interface {
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
    return array();
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
