<?php

/**
 * Create classes which are used for schema introspection.
 */
class CRM_Core_CodeGen_Reflection extends CRM_Core_CodeGen_BaseTask {
  function run() {
    $this->generateListAll();
  }

  function generateListAll() {
    $template = new CRM_Core_CodeGen_Util_Template($this->config, 'php');
    $template->assign('metadata', $this->config->doctrine->metadata);
    $template->run('listAll.tpl', CRM_Utils_Path::join($this->config->CoreDAOCodePath,"AllCoreTables.php"));
  }
}
