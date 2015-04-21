<?php

/**
 * Create DAO ORM classes.
 */
class CRM_Core_CodeGen_DAO extends CRM_Core_CodeGen_BaseTask {
  public function run() {
    $this->generateDAOs();
  }

  public function generateDAOs() {
    foreach (array_keys($this->tables) as $name) {
      echo "Generating $name as " . $this->tables[$name]['fileName'] . "\n";

      if (empty($this->tables[$name]['base'])) {
        echo "No base defined for $name, skipping output generation\n";
        continue;
      }

      $template = new CRM_Core_CodeGen_Util_Template('php');
      $template->assign('table', $this->tables[$name]);

      $directory = $this->config->phpCodePath . $this->tables[$name]['base'];
      CRM_Core_CodeGen_Util_File::createDir($directory);

      $template->run('dao.tpl', $directory . $this->tables[$name]['fileName']);
    }
  }
}
