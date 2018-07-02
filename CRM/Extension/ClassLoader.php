<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 *
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
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
    if (!defined('CIVICRM_TEMPLATE_COMPILEDIR') || !defined('CIVICRM_DSN') || defined('CIVICRM_TEST') || \CRM_Utils_System::isInUpgradeMode()) {
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
    $file = CIVICRM_TEMPLATE_COMPILEDIR . "/CachedExtLoader.{$envId}.php";
    return $file;
  }

}
