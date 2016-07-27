<?php

/**
 * Class CRM_Core_CodeGen_BaseTask
 */
abstract class CRM_Core_CodeGen_BaseTask implements CRM_Core_CodeGen_ITask {
  /**
   * @var CRM_Core_CodeGen_Main
   */
  protected $config;

  protected $tables;

  /**
   * @param CRM_Core_CodeGen_Main $config
   */
  public function __construct($config) {
    $this->setConfig($config);
  }

  /**
   * TODO: this is the most rudimentary possible hack.  CG config should
   * eventually be made into a first-class object.
   *
   * @param object $config
   */
  public function setConfig($config) {
    $this->config = $config;
    $this->tables = $this->config->tables;
  }

  /**
   * @return bool
   *   TRUE if an update is needed.
   */
  public function needsUpdate() {
    return TRUE;
  }

  /**
   * Extract a single regex from a file.
   *
   * @param string $file
   *   File name
   * @param string $regex
   *   A pattern to match. Ex: "foo=([a-z]+)".
   * @return string|NULL
   *   The value matched.
   */
  protected static function extractRegex($file, $regex) {
    $content = file_get_contents($file);
    if (preg_match($regex, $content, $matches)) {
      return $matches[1];
    }
    else {
      return NULL;
    }
  }

}
