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
 * This class stores logic for managing CiviCRM extensions.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Extension_Manager_Module extends CRM_Extension_Manager_Base {

  /**
   * @param CRM_Extension_Mapper $mapper
   */
  public function __construct(CRM_Extension_Mapper $mapper) {
    parent::__construct(FALSE);
    $this->mapper = $mapper;
  }

  /**
   * @param CRM_Extension_Info $info
   */
  public function onPreInstall(CRM_Extension_Info $info) {
    $this->callHook($info, 'install');
    $this->callHook($info, 'enable');
  }

  /**
   * @param CRM_Extension_Info $info
   */
  public function onPostPostInstall(CRM_Extension_Info $info) {
    $this->callHook($info, 'postInstall');
  }

  /**
   * @param CRM_Extension_Info $info
   * @param string $hookName
   */
  private function callHook(CRM_Extension_Info $info, $hookName) {
    try {
      $file = $this->mapper->keyToPath($info->key);
    }
    catch (CRM_Extension_Exception $e) {
      return;
    }
    if (!file_exists($file)) {
      return;
    }
    include_once $file;
    $fnName = "{$info->file}_civicrm_{$hookName}";
    if (function_exists($fnName)) {
      $fnName();
    }
  }

  /**
   * @param CRM_Extension_Info $info
   *
   * @return bool
   */
  public function onPreUninstall(CRM_Extension_Info $info) {
    $this->callHook($info, 'uninstall');
    return TRUE;
  }

  /**
   * @param CRM_Extension_Info $info
   */
  public function onPostUninstall(CRM_Extension_Info $info) {
  }

  /**
   * @param CRM_Extension_Info $info
   */
  public function onPreDisable(CRM_Extension_Info $info) {
    $this->callHook($info, 'disable');
  }

  /**
   * @param CRM_Extension_Info $info
   */
  public function onPreEnable(CRM_Extension_Info $info) {
    $this->callHook($info, 'enable');
  }

}
