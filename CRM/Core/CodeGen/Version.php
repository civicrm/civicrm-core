<?php

/**
 * Generate configuration files
 */
class CRM_Core_CodeGen_Version extends CRM_Core_CodeGen_BaseTask {
  public function run() {
    echo "Generating civicrm-version file\n";

    file_put_contents($this->config->tplCodePath . "/CRM/common/version.tpl", $this->config->db_version);

    $template = new CRM_Core_CodeGen_Util_Template('php');
    $template->assign('db_version', $this->config->db_version);
    $template->assign('cms', ucwords($this->config->cms));
    $template->run('civicrm_version.tpl', $this->config->phpCodePath . "civicrm-version.php");
  }

}
