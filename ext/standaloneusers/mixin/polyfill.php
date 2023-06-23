<?php

/**
 * When deploying on systems that lack mixin support, fake it.
 *
 * @mixinFile polyfill.php
 *
 * This polyfill does some (persnickity) deduplication, but it doesn't allow upgrades or shipping replacements in core.
 *
 * Note: The polyfill.php is designed to be copied into extensions for interoperability. Consequently, this file is
 * not used 'live' by `civicrm-core`. However, the file does need a canonical home, and it's convenient to keep it
 * adjacent to the actual mixin files.
 *
 * @param string $longName
 * @param string $shortName
 * @param string $basePath
 */
return function ($longName, $shortName, $basePath) {
  // Construct imitations of the mixin services. These cannot work as well (e.g. with respect to
  // number of file-reads, deduping, upgrading)... but they should be OK for a few months while
  // the mixin services become available.

  // List of active mixins; deduped by version
  $mixinVers = [];
  foreach ((array) glob($basePath . '/mixin/*.mixin.php') as $f) {
    [$name, $ver] = explode('@', substr(basename($f), 0, -10));
    if (!isset($mixinVers[$name]) || version_compare($ver, $mixinVers[$name], '>')) {
      $mixinVers[$name] = $ver;
    }
  }
  $mixins = [];
  foreach ($mixinVers as $name => $ver) {
    $mixins[] = "$name@$ver";
  }

  // Imitate CRM_Extension_MixInfo.
  $mixInfo = new class() {

    /**
     * @var string
     */
    public $longName;

    /**
     * @var string
     */
    public $shortName;

    public $_basePath;

    public function getPath($file = NULL) {
      return $this->_basePath . ($file === NULL ? '' : (DIRECTORY_SEPARATOR . $file));
    }

    public function isActive() {
      return \CRM_Extension_System::singleton()->getMapper()->isActiveModule($this->shortName);
    }

  };
  $mixInfo->longName = $longName;
  $mixInfo->shortName = $shortName;
  $mixInfo->_basePath = $basePath;

  // Imitate CRM_Extension_BootCache.
  $bootCache = new class() {

    public function define($name, $callback) {
      $envId = \CRM_Core_Config_Runtime::getId();
      $oldExtCachePath = \Civi::paths()->getPath("[civicrm.compile]/CachedExtLoader.{$envId}.php");
      $stat = stat($oldExtCachePath);
      $file = Civi::paths()->getPath('[civicrm.compile]/CachedMixin.' . md5($name . ($stat['mtime'] ?? 0)) . '.php');
      if (file_exists($file)) {
        return include $file;
      }
      else {
        $data = $callback();
        file_put_contents($file, '<' . "?php\nreturn " . var_export($data, 1) . ';');
        return $data;
      }
    }

  };

  // Imitate CRM_Extension_MixinLoader::run()
  // Parse all live mixins before trying to scan any classes.
  global $_CIVIX_MIXIN_POLYFILL;
  foreach ($mixins as $mixin) {
    // If the exact same mixin is defined by multiple exts, just use the first one.
    if (!isset($_CIVIX_MIXIN_POLYFILL[$mixin])) {
      $_CIVIX_MIXIN_POLYFILL[$mixin] = include_once $basePath . '/mixin/' . $mixin . '.mixin.php';
    }
  }
  foreach ($mixins as $mixin) {
    // If there's trickery about installs/uninstalls/resets, then we may need to register a second time.
    if (!isset(\Civi::$statics[__FUNCTION__][$mixin])) {
      \Civi::$statics[__FUNCTION__][$mixin] = 1;
      $func = $_CIVIX_MIXIN_POLYFILL[$mixin];
      $func($mixInfo, $bootCache);
    }
  }
};
