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
   */
  public function createSmarty() {
    $base = dirname(dirname(dirname(dirname(__DIR__))));

    require_once 'Smarty/Smarty.class.php';
    $smarty = new Smarty();
    $smarty->template_dir = "$base/xml/templates";
    $smarty->plugins_dir = ["$base/packages/Smarty/plugins", "$base/CRM/Core/Smarty/plugins"];
    $smarty->compile_dir = $this->getCompileDir();
    $smarty->clear_all_cache();

    // CRM-5308 / CRM-3507 - we need {localize} to work in the templates

    require_once 'CRM/Core/Smarty/plugins/block.localize.php';
    $smarty->register_block('localize', 'smarty_block_localize');

    return $smarty;
  }

}
