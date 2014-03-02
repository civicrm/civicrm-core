<?php

/**
 * Create DAO ORM classes.
 */
class CRM_Core_CodeGen_DAO extends CRM_Core_CodeGen_BaseTask {
  function run() {
    $this->generateDAOs();
  }

  function generateDAOs() {
    foreach ($this->config->doctrine->dao_metadata as $table) {
      $template = new CRM_Core_CodeGen_Util_Template($this->config, 'php');
      $template->assign('table', $table);
      $output_dir_path = CRM_Utils_Path::join($this->config->civicrm_root_path, $table['daoDir']);
      CRM_Core_CodeGen_Util_File::createDir($output_dir_path);
      $output_file_path = CRM_Utils_Path::join($output_dir_path, $table['daoFileName']);
      $template->run('dao.tpl', $output_file_path);
    }
  }
}
