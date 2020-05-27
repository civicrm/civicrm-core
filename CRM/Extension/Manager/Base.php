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
 * The extension manager handles installing, disabling enabling, and
 * uninstalling extensions.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Extension_Manager_Base implements CRM_Extension_Manager_Interface {

  /**
   * Whether to automatically uninstall and install during 'replace'.
   *
   * @var bool
   */
  public $autoReplace;

  /**
   * @param bool $autoReplace
   *   Whether to automatically uninstall and install during 'replace'.
   */
  public function __construct($autoReplace = FALSE) {
    $this->autoReplace = $autoReplace;
  }

  /**
   * @inheritDoc
   *
   * @param CRM_Extension_Info $info
   */
  public function onPreInstall(CRM_Extension_Info $info) {
  }

  /**
   * @inheritDoc
   *
   * @param CRM_Extension_Info $info
   */
  public function onPostInstall(CRM_Extension_Info $info) {
  }

  /**
   * @inheritDoc
   *
   * @param CRM_Extension_Info $info
   */
  public function onPostPostInstall(CRM_Extension_Info $info) {
  }

  /**
   * @inheritDoc
   *
   * @param CRM_Extension_Info $info
   */
  public function onPreEnable(CRM_Extension_Info $info) {
  }

  /**
   * @inheritDoc
   *
   * @param CRM_Extension_Info $info
   */
  public function onPostEnable(CRM_Extension_Info $info) {
  }

  /**
   * @inheritDoc
   *
   * @param CRM_Extension_Info $info
   */
  public function onPreDisable(CRM_Extension_Info $info) {
  }

  /**
   * @inheritDoc
   *
   * @param CRM_Extension_Info $info
   */
  public function onPostDisable(CRM_Extension_Info $info) {
  }

  /**
   * @inheritDoc
   *
   * @param CRM_Extension_Info $info
   */
  public function onPreUninstall(CRM_Extension_Info $info) {
  }

  /**
   * @inheritDoc
   *
   * @param CRM_Extension_Info $info
   */
  public function onPostUninstall(CRM_Extension_Info $info) {
  }

  /**
   * @inheritDoc
   *
   * @param CRM_Extension_Info $oldInfo
   * @param CRM_Extension_Info $newInfo
   */
  public function onPreReplace(CRM_Extension_Info $oldInfo, CRM_Extension_Info $newInfo) {
    if ($this->autoReplace) {
      $this->onPreUninstall($oldInfo);
      $this->onPostUninstall($oldInfo);
    }
  }

  /**
   * @inheritDoc
   *
   * @param CRM_Extension_Info $oldInfo
   * @param CRM_Extension_Info $newInfo
   */
  public function onPostReplace(CRM_Extension_Info $oldInfo, CRM_Extension_Info $newInfo) {
    if ($this->autoReplace) {
      $this->onPreInstall($oldInfo);
      $this->onPostInstall($oldInfo);
    }
  }

}
