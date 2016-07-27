<?php

/**
 * Create classes which are used for schema introspection.
 */
class CRM_Core_CodeGen_Reflection extends CRM_Core_CodeGen_BaseTask {

  protected $checksum;

  /**
   * @return bool
   *   TRUE if an update is needed.
   */
  public function needsUpdate() {
    if (!file_exists($this->getAbsFileName())) {
      return TRUE;
    }
    return $this->getChecksum() !== self::extractRegex($this->getAbsFileName(), ';\(GenCodeChecksum:([a-z0-9]+)\);');
  }

  public function run() {
    echo "Generating table list\n";
    $template = new CRM_Core_CodeGen_Util_Template('php');
    $template->assign('tables', $this->tables);
    $template->assign('genCodeChecksum', $this->getChecksum());
    $template->run('listAll.tpl', $this->getAbsFileName());
  }

  /**
   * @return string
   */
  protected function getAbsFileName() {
    return $this->config->CoreDAOCodePath . "AllCoreTables.data.php";
  }

  protected function getChecksum() {
    if (!$this->checksum) {
      CRM_Utils_Array::flatten($this->tables, $flat);
      ksort($flat);
      $this->checksum = md5($this->config->getSourceDigest() . json_encode($flat));
    }
    return $this->checksum;
  }

}
