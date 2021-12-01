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
    $cacheFile = (defined('CIVICRM_DSN') && !defined('CIVICRM_TEST') && !\CRM_Utils_System::isInUpgradeMode())
      ? $this->getCacheFile() : NULL;

    if (file_exists($cacheFile)) {
      [$classLoader, $mixinLoader, $bootCache] = require $cacheFile;
      $cacheUpdate = NULL;
    }
    else {
      $classLoader = $this->buildClassLoader();
      $mixinLoader = (new CRM_Extension_MixinScanner($this->mapper, $this->manager, $cacheFile !== NULL))->createLoader();
      $bootCache = new CRM_Extension_BootCache();
      // We don't own Composer\Autoload\ClassLoader, so we clone to prevent register() from potentially leaking data.
      // We do own MixinLoader, and we want its state - like $bootCache - to be written.
      $cacheUpdate = $cacheFile ? [clone $classLoader, clone $mixinLoader, $bootCache] : NULL;
    }

    $classLoader->register();
    $mixinLoader->run($bootCache);

    if ($cacheUpdate !== NULL) {
      // Save cache after $mixinLoader has a chance to fill $bootCache.
      $export = var_export(serialize($cacheUpdate), 1);
      file_put_contents($cacheFile, sprintf("<?php\nreturn unserialize(%s);", $export));
    }

    $this->loader = $classLoader;
    return $classLoader;
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
            break;
        }
      }
    }
  }

  /**
   * @return string
   */
  protected function getCacheFile() {
    $formatRev = '_2';
    $envId = \CRM_Core_Config_Runtime::getId() . $formatRev;
    $file = \Civi::paths()->getPath("[civicrm.compile]/CachedExtLoader.{$envId}.php");
    return $file;
  }

}
