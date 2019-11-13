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
 *
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */
class CRM_Extension_ClassLoader {

  /**
   * @var CRM_Extension_Mapper
   */
  protected $mapper;

  /**
   * @var CRM_Extension_Container_Interface
   */
  protected $container;

  /**
   * @var CRM_Extension_Manager
   */
  protected $manager;

  /**
   * @var \Composer\Autoload\ClassLoader
   */
  protected $loader;

  /**
   * CRM_Extension_ClassLoader constructor.
   * @param \CRM_Extension_Mapper $mapper
   * @param \CRM_Extension_Container_Interface $container
   * @param \CRM_Extension_Manager $manager
   */
  public function __construct(\CRM_Extension_Mapper $mapper, \CRM_Extension_Container_Interface $container, \CRM_Extension_Manager $manager) {
    $this->mapper = $mapper;
    $this->container = $container;
    $this->manager = $manager;
  }

  public function __destruct() {
    $this->unregister();
  }

  /**
   * Registers this instance as an autoloader.
   * @return CRM_Extension_ClassLoader
   */
  public function register() {
    // In pre-installation environments, don't bother with caching.
    if (!defined('CIVICRM_DSN') || defined('CIVICRM_TEST') || \CRM_Utils_System::isInUpgradeMode()) {
      return $this->buildClassLoader()->register();
    }

    $file = $this->getCacheFile();
    if (file_exists($file)) {
      $loader = require $file;
    }
    else {
      $loader = $this->buildClassLoader();
      $ser = serialize($loader);
      file_put_contents($file,
        sprintf("<?php\nreturn unserialize(%s);", var_export($ser, 1))
      );
    }
    return $loader->register();
  }

  /**
   * @return \Composer\Autoload\ClassLoader
   * @throws \CRM_Extension_Exception
   * @throws \Exception
   */
  public function buildClassLoader() {
    $loader = new \Composer\Autoload\ClassLoader();

    $statuses = $this->manager->getStatuses();
    foreach ($statuses as $key => $status) {
      if ($status !== CRM_Extension_Manager::STATUS_INSTALLED) {
        continue;
      }
      $path = $this->mapper->keyToBasePath($key);
      $info = $this->mapper->keyToInfo($key);
      if (!empty($info->classloader)) {
        foreach ($info->classloader as $mapping) {
          switch ($mapping['type']) {
            case 'psr4':
              $loader->addPsr4($mapping['prefix'], $path . '/' . $mapping['path']);
              break;
          }
          $result[] = $mapping;
        }
      }
    }

    return $loader;
  }

  public function unregister() {
    if ($this->loader) {
      $this->loader->unregister();
      $this->loader = NULL;
    }
  }

  public function refresh() {
    $this->unregister();
    $file = $this->getCacheFile();
    if (file_exists($file)) {
      unlink($file);
    }
    $this->register();
  }

  /**
   * @return string
   */
  protected function getCacheFile() {
    $envId = \CRM_Core_Config_Runtime::getId();
    $file = \Civi::paths()->getPath("[civicrm.compile]/CachedExtLoader.{$envId}.php");
    return $file;
  }

}
