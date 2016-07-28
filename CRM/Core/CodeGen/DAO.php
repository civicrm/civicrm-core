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
  private $tableChecksum;

  /**
   * @var string
   */
  private $raw;

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

    // Has the table metadata changed since the DAO was generated?
    if ($this->getTableChecksum() !== self::extractRegex($this->getAbsFileName(), ';\(GenCodeChecksum:([a-zA-Z0-9]+)\);')) {
      return TRUE;
    }

    // Has someone messed with the logic of the DAO?
    // Compare suggested+actual code (modulo whitespace).
    $stripped['actual'] = file_get_contents($this->getAbsFileName());
    $stripped['expect'] = $this->getRaw();

    foreach (array('actual', 'expect') as $key) {
      $stripped[$key] = preg_replace(';\(GenCodeChecksum:([a-zA-Z0-9]+)\);', '', $stripped[$key]);
      $stripped[$key] = preg_replace(';[ \r\n\t];', '', $stripped[$key]);
    }
    return $stripped['actual'] !== $stripped['expect'];
  }

  public function run() {
    echo "Generating {$this->name} as " . $this->getRelFileName() . "\n";

    if (empty($this->tables[$this->name]['base'])) {
      echo "No base defined for {$this->name}, skipping output generation\n";
      return;
    }

    $template = new CRM_Core_CodeGen_Util_Template('php');
    $template->assign('table', $this->tables[$this->name]);
    $template->assign('genCodeChecksum', $this->getTableChecksum());
    $template->run('dao.tpl', $this->getAbsFileName());
  }

  /**
   * Generate the raw PHP code for the DAO.
   *
   * @return string
   */
  public function getRaw() {
    if (!$this->raw) {
      $template = new CRM_Core_CodeGen_Util_Template('php');
      $template->assign('table', $this->tables[$this->name]);
      $template->assign('genCodeChecksum', 'NEW');
      $this->raw = $template->fetch('dao.tpl');
    }
    return $this->raw;
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

  /**
   * Get a unique signature for the table/schema.
   *
   * @return string
   */
  protected function getTableChecksum() {
    if (!$this->tableChecksum) {
      CRM_Utils_Array::flatten($this->tables[$this->name], $flat);
      ksort($flat);
      $this->tableChecksum = md5(json_encode($flat));
    }
    return $this->tableChecksum;
  }

}
