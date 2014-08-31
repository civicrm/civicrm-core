<?php

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

  /**
   * @var Smarty
   */
  private $smarty;

  private $compileDir;

  function __destruct() {
    if ($this->compileDir) {
      CRM_Core_CodeGen_Util_File::cleanTempDir($this->compileDir);
    }
  }

  function setPluginDirs($pluginDirs) {
    $this->smartyPluginDirs = $pluginDirs;
    $this->smarty = NULL;
  }

  function getCompileDir() {
    if ($this->compileDir === NULL) {
      $this->compileDir = CRM_Core_CodeGen_Util_File::createTempDir('templates_c_');
    }
    return $this->compileDir;
  }

  function getSmarty() {
    if ($this->smarty === NULL) {
      require_once 'Smarty/Smarty.class.php';
      $this->smarty = new Smarty();
      $this->smarty->template_dir = './templates';
      $this->smarty->plugins_dir = $this->smartyPluginDirs;
      $this->smarty->compile_dir = $this->getCompileDir();
      $this->smarty->clear_all_cache();
    }
    return $this->smarty;
  }
}