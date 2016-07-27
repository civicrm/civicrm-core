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

  private $smartyPluginDirs = array();

  private $compileDir;

  public function __destruct() {
    if ($this->compileDir) {
      CRM_Core_CodeGen_Util_File::cleanTempDir($this->compileDir);
    }
  }

  /**
   * Set plugin directories.
   *
   * @param array $pluginDirs
   */
  public function setPluginDirs($pluginDirs) {
    $this->smartyPluginDirs = $pluginDirs;
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
   */
  public function createSmarty() {
    require_once 'Smarty/Smarty.class.php';
    $smarty = new Smarty();
    $smarty->template_dir = './templates';
    $smarty->plugins_dir = $this->smartyPluginDirs;
    $smarty->compile_dir = $this->getCompileDir();
    $smarty->clear_all_cache();

    // CRM-5308 / CRM-3507 - we need {localize} to work in the templates

    require_once 'CRM/Core/Smarty/plugins/block.localize.php';
    $smarty->register_block('localize', 'smarty_block_localize');

    return $smarty;
  }

}
