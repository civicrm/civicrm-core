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
    $pkgs = file_exists(dirname($base) . "/civicrm-packages") ? dirname($base) . "/civicrm-packages" : "$base/packages";
    require_once $pkgs . '/smarty4/vendor/autoload.php';
    $smarty = new Smarty();
    $smarty->setTemplateDir("$base/xml/templates");
    $pluginsDirectory = $smarty->getPluginsDir();
    $pluginsDirectory[] = "$base/CRM/Core/Smarty/plugins";
    // Doesn't seem to work very well.... since I still need require_once below
    $smarty->setPluginsDir($pluginsDirectory);
    $smarty->setCompileDir($this->getCompileDir());
    $smarty->clearAllCache();

    // CRM-5308 / CRM-3507 - we need {localize} to work in the templates
    require_once 'CRM/Core/Smarty/plugins/block.localize.php';
    $smarty->registerPlugin('block', 'localize', 'smarty_block_localize');

    // Use our special replace rather than Smarty's to avoid conflicts while we
    // transition from Smarty2.
    require_once 'CRM/Core/Smarty/plugins/modifier.crmEscapeSingleQuotes.php';
    $smarty->registerPlugin('modifier', 'crmEscapeSingleQuotes', 'smarty_modifier_crmEscapeSingleQuotes');

    require_once 'CRM/Core/Smarty/plugins/modifier.crmCountCharacters.php';
    $smarty->registerPlugin('modifier', 'crmCountCharacters', 'smarty_modifier_crmCountCharacters');

    $smarty->registerPlugin('modifier', 'json_encode', 'json_encode');
    $smarty->registerPlugin('modifier', 'count', 'count');
    $smarty->registerPlugin('modifier', 'implode', 'implode');

    return $smarty;
  }

}
