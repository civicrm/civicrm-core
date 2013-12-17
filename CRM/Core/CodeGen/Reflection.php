<?php

/**
 * Create classes which are used for schema introspection.
 */
class CRM_Core_CodeGen_Reflection extends CRM_Core_CodeGen_BaseTask {
  function run() {
    $this->generateListAll();
  }

  function generateListAll() {
    $template = new CRM_Core_CodeGen_Util_Template('php');
    $template->assign('tables', $this->tables);

    $template->run('listAll.tpl', $this->config->CoreDAOCodePath . "AllCoreTables.php");
  }
}
