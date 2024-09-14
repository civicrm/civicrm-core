<?php

/**
 * Class CRM_Core_CodeGen_Util_Smarty
 */
class CRM_Core_CodeGen_Util_Smarty {
  /**
   * @var CRM_Core_CodeGen_Util_Smarty
   */
  private static $singleton;

  /**
   * @return CRM_Core_CodeGen_Util_Smarty
   */
  public static function singleton() {
    if (self::$singleton === NULL) {
      self::$singleton = new CRM_Core_CodeGen_Util_Smarty();
    }
    return self::$singleton;
  }

  private $compileDir;

  public function __destruct() {
    if ($this->compileDir) {
      CRM_Core_CodeGen_Util_File::cleanTempDir($this->compileDir);
    }
  }

  /**
   * Get templates_c directory.
   *
   * @return string
   */
  public function getCompileDir() {
    if ($this->compileDir === NULL) {
      $this->compileDir = CRM_Core_CodeGen_Util_File::createTempDir('templates_c_');
    }
    return $this->compileDir;
  }

  /**
   * Create a Smarty instance.
   *
   * @return \Smarty
   * @throws \SmartyException
   */
  public function createSmarty(): Smarty {
    $base = dirname(__DIR__, 4);
    if (!class_exists('Smarty', FALSE)) {
      // Prefer Smarty v5; but if we get here in some scenario with another Smarty, use that.
      $pkgs = file_exists(dirname($base) . "/civicrm-packages") ? dirname($base) . "/civicrm-packages" : "$base/packages";
      require_once $pkgs . '/smarty5/Smarty.php';
    }
    $smarty = new Smarty();
    $smarty->setTemplateDir("$base/xml/templates");
    // Doesn't seem to work very well.... since I still need require_once below
    $smarty->addPluginsDir(["$base/CRM/Core/Smarty/plugins"]);
    $smarty->setCompileDir($this->getCompileDir());
    $smarty->clearAllCache();

    $smarty->registerPlugin('modifier', 'json_encode', 'json_encode');
    $smarty->registerPlugin('modifier', 'count', 'count');
    $smarty->registerPlugin('modifier', 'implode', 'implode');

    return $smarty;
  }

}
