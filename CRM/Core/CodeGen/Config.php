<?php

/**
 * Generate configuration files
 */
class CRM_Core_CodeGen_Config extends CRM_Core_CodeGen_BaseTask {
  public function run() {
    echo "Generating civicrm-version file\n";
    $template = new CRM_Core_CodeGen_Util_Template('php');
    $template->assign('db_version', $this->config->db_version);
    $template->assign('cms', ucwords($this->config->cms));
    $template->assign('svnrevision', $this->config->db_version); // FIXME
    $template->run('civicrm_version.tpl', $this->config->phpCodePath . "civicrm-version.php");
  }

}
