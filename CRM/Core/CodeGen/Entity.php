<?php

/**
 * Create ORM entities
 */
class CRM_Core_CodeGen_Entity extends CRM_Core_CodeGen_BaseTask {
  function run() {
    $this->generateEntitys();
  }

  function generateEntitys() {
    foreach ($this->config->tables as $table) {
      echo "Generating entity {$table['fileName']} for {$table['name']} \n";

      if (empty($table['base'])) {
        echo "No base defined for {$table['name']}, skipping output generation\n";
        continue;
      }

      $template = new CRM_Core_CodeGen_Util_Template($this->config, '');
      $template->assign('table', $table);

      $directory_path = CRM_Utils_Path::join($this->config->phpCodePath, $table['base']);
      CRM_Core_CodeGen_Util_File::createDir($directory_path);
      $file_path = CRM_Utils_Path::join($directory_path, $table['fileName']);
      $template->run('entity.tpl', $file_path);
    }
  }
}
