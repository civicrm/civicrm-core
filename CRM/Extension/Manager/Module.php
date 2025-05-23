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
   * @var \CRM_Extension_Mapper
   */
  protected $mapper;

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
    $this->registerClassloader($info);
    $this->callHook($info, 'preInstall');
    $this->callHook($info, 'install');
    $this->callHook($info, 'enable');
  }

  public function onPostInstall(CRM_Extension_Info $info) {
    \Civi\Core\ClassScanner::cache('index')->flush();
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
    if ($info->upgrader) {
      $this->mapper->getUpgrader($info->key)->notify($hookName);
    }
  }

  /**
   * @param CRM_Extension_Info $info
   *
   * @return bool
   */
  public function onPreUninstall(CRM_Extension_Info $info) {
    $this->registerClassloader($info);
    $this->callHook($info, 'uninstall');
    return TRUE;
  }

  /**
   * @param CRM_Extension_Info $info
   */
  public function onPostUninstall(CRM_Extension_Info $info) {
    \Civi\Core\ClassScanner::cache('index')->flush();
  }

  /**
   * @param CRM_Extension_Info $info
   */
  public function onPreDisable(CRM_Extension_Info $info) {
    $this->callHook($info, 'disable');
  }

  public function onPostDisable(CRM_Extension_Info $info) {
    \Civi\Core\ClassScanner::cache('index')->flush();
  }

  /**
   * @param CRM_Extension_Info $info
   */
  public function onPreEnable(CRM_Extension_Info $info) {
    $this->registerClassloader($info);
    $this->callHook($info, 'enable');
  }

  public function onPostEnable(CRM_Extension_Info $info) {
    \Civi\Core\ClassScanner::cache('index')->flush();
  }

  public function onPostReplace(CRM_Extension_Info $oldInfo, CRM_Extension_Info $newInfo) {
    // Like everything, ClassScanner is probably affected by pre-existing/long-standing issue dev/core#3686.
    // This may mitigate a couple edge-cases. But really #3686 needs a different+deeper fix.
    \Civi\Core\ClassScanner::cache('structure')->flush();
    \Civi\Core\ClassScanner::cache('index')->flush();

    parent::onPostReplace($oldInfo, $newInfo);
  }

  /**
   * @param CRM_Extension_Info $info
   */
  private function registerClassloader($info) {
    try {
      $extPath = dirname($this->mapper->keyToPath($info->key));
    }
    catch (CRM_Extension_Exception_MissingException $e) {
      // This could happen if there was a dirty removal (i.e. deleting ext-code before uninstalling).
      return;
    }

    $classloader = CRM_Extension_System::singleton()->getClassLoader();
    if (!$classloader->isRegistered()) {
      $classloader->register();
    }
    $classloader->installExtension($info, $extPath);
  }

}
