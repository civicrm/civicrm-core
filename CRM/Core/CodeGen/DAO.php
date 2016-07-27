<?php

/**
 * Create DAO ORM classes.
 */
class CRM_Core_CodeGen_DAO extends CRM_Core_CodeGen_BaseTask {

  /**
   * @var string
   */
  public $name;

  /**
   * @var string
   */
  private $checksum;

  public function __construct($config, $name) {
    parent::__construct($config);
    $this->name = $name;
  }

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
    echo "Generating {$this->name} as " . $this->getRelFileName() . "\n";

    if (empty($this->tables[$this->name]['base'])) {
      echo "No base defined for {$this->name}, skipping output generation\n";
      return;
    }

    $template = new CRM_Core_CodeGen_Util_Template('php');
    $template->assign('table', $this->tables[$this->name]);
    $template->assign('genCodeChecksum', $this->getChecksum());
    $template->run('dao.tpl', $this->getAbsFileName());
  }

  public function getRelFileName() {
    return $this->tables[$this->name]['fileName'];
  }

  /**
   * @return string
   */
  public function getAbsFileName() {
    $directory = $this->config->phpCodePath . $this->tables[$this->name]['base'];
    CRM_Core_CodeGen_Util_File::createDir($directory);
    $absFileName = $directory . $this->getRelFileName();
    return $absFileName;
  }

  protected function getChecksum() {
    if (!$this->checksum) {
      CRM_Utils_Array::flatten($this->tables[$this->name], $flat);
      ksort($flat);
      $this->checksum = md5($this->config->getSourceDigest() . json_encode($flat));
    }
    return $this->checksum;
  }

}
