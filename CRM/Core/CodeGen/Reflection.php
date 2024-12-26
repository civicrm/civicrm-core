<?php

/**
 * Create classes which are used for schema introspection.
 */
class CRM_Core_CodeGen_Reflection extends CRM_Core_CodeGen_BaseTask {

  /**
   * @return bool
   *   TRUE if an update is needed.
   */
  public function needsUpdate() {
    // Generating this file is fairly cheap, and we don't have robust heuristic
    // for the checksum.

    // skip this task on test environment as the schema generation should only be triggered during installation/upgrade
    return CIVICRM_UF !== 'UnitTests';
  }

  /**
   * Run generator.
   */
  public function run() {
    echo "Generating table list\n";
    $template = new CRM_Core_CodeGen_Util_Template('php', FALSE);
    $template->assign('tables', $this->tables);
    $template->assign('genCodeChecksum', 'IGNORE');
    $template->run('listAll.tpl', $this->getAbsFileName());
  }

  /**
   * Get absolute file name.
   *
   * @return string
   */
  protected function getAbsFileName() {
    return $this->config->CoreDAOCodePath . "AllCoreTables.data.php";
  }

}
