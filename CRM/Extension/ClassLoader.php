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
 * Class CRM_Extension_ClassLoader
 */
class CRM_Extension_ClassLoader {

  /**
   * List of class-loader features that are valid in this version of Civi.
   *
   * This may be useful for some extensions which enable/disable polyfills based on environment.
   */
  const FEATURES = ',psr0,psr4,';

  /**
   * A list of recently-activated extensions. This list is retained
   * even if some ill-advised part of the installer does a `ClassLoader::refresh()`.
   *
   * @var array
   */
  private static $newExtensions = [];

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

  public function isRegistered(): bool {
    return ($this->loader !== NULL);
  }

  /**
   * Registers this instance as an autoloader.
   * @return CRM_Extension_ClassLoader
   */
  public function register() {
    // In pre-installation environments, don't bother with caching.
    if (!defined('CIVICRM_DSN') || defined('CIVICRM_TEST') || \CRM_Utils_System::isInUpgradeMode()) {
      $this->loader = $this->buildClassLoader();
      return $this->loader->register();
    }

    $file = $this->getCacheFile();
    if (file_exists($file)) {
      $this->loader = require $file;
    }
    else {
      $this->loader = $this->buildClassLoader();
      $ser = serialize($this->loader);
      file_put_contents($file,
        sprintf("<?php\nreturn unserialize(%s);", var_export($ser, 1))
      );
    }
    return $this->loader->register();
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
      self::loadExtension($loader, $this->mapper->keyToInfo($key), $this->mapper->keyToBasePath($key));
    }
    foreach (static::$newExtensions as $record) {
      static::loadExtension($loader, $record[0], $record[1]);
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
   * Add a newly installed extension to the active classloader.
   *
   * NOTE: This is intended for use by CRM/Extension subsystem during installation.
   *
   * @param \CRM_Extension_Info $info
   * @param string $path
   */
  public function installExtension(CRM_Extension_Info $info, string $path): void {
    $file = $this->getCacheFile();
    if (file_exists($file)) {
      unlink($file);
    }
    static::$newExtensions[] = [$info, $path];
    if ($this->loader) {
      self::loadExtension($this->loader, $info, $path);
    }
  }

  /**
   * Read the extension metadata configure a classloader.
   *
   * @param \Composer\Autoload\ClassLoader $loader
   * @param \CRM_Extension_Info $info
   * @param string $path
   */
  private static function loadExtension(\Composer\Autoload\ClassLoader $loader, CRM_Extension_Info $info, string $path): void {
    if (!empty($info->classloader)) {
      foreach ($info->classloader as $mapping) {
        switch ($mapping['type']) {
          case 'psr0':
            $loader->add($mapping['prefix'], CRM_Utils_File::addTrailingSlash($path . '/' . $mapping['path']));
            break;

          case 'psr4':
            $loader->addPsr4($mapping['prefix'], $path . '/' . $mapping['path']);
            if (defined('CIVICRM_TEST')) {
              if (is_dir($path . '/tests/phpunit/' . $mapping['path'])) {
                $loader->addPsr4($mapping['prefix'], $path . '/tests/phpunit/' . $mapping['path']);
              }
            }
            break;
        }
      }
    }
  }

  /**
   * @return string
   */
  protected function getCacheFile() {
    $envId = md5(implode(',', array_merge(
      [\CRM_Core_Config_Runtime::getId()],
      array_column($this->mapper->getActiveModuleFiles(), 'prefix')
      // dev/core#4055 - When toggling ext's on systems with opcode caching, you may get stale reads for a moment.
      // New cache key ensures new data-set.
    )));
    $file = \Civi::paths()->getPath("[civicrm.compile]/CachedExtLoader.{$envId}.php");
    return $file;
  }

}
