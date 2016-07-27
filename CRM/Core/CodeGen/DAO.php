<?php

/**
 * Create DAO ORM classes.
 */
class CRM_Core_CodeGen_DAO extends CRM_Core_CodeGen_BaseTask {

  /**
   * @var string
   */
  public $name;

  public function __construct($config, $name) {
    parent::__construct($config);
    $this->name = $name;
  }

  public function run() {
    $name = $this->name;
    echo "Generating $name as " . $this->tables[$name]['fileName'] . "\n";

    if (empty($this->tables[$name]['base'])) {
      echo "No base defined for $name, skipping output generation\n";
      return;
    }

    $template = new CRM_Core_CodeGen_Util_Template('php');
    $template->assign('table', $this->tables[$name]);

    $directory = $this->config->phpCodePath . $this->tables[$name]['base'];
    CRM_Core_CodeGen_Util_File::createDir($directory);

    $template->run('dao.tpl', $directory . $this->tables[$name]['fileName']);
  }

}
