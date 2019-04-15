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

  /**
   * @var string
   * translate function name
   */
  private $tsFunctionName;

  /**
   * CRM_Core_CodeGen_DAO constructor.
   *
   * @param \CRM_Core_CodeGen_Main $config
   * @param string $name
   * @param string $tsFunctionName
   */
  public function __construct($config, $name, $tsFunctionName = 'ts') {
    parent::__construct($config);
    $this->name = $name;
    $this->tsFunctionName = $tsFunctionName;
  }

  /**
   * @return bool
   *   TRUE if an update is needed.
   */
  public function needsUpdate() {
    if (!file_exists($this->getAbsFileName())) {
      return TRUE;
    }

    if ($this->getTableChecksum() !== self::extractRegex($this->getAbsFileName(), ';\(GenCodeChecksum:([a-zA-Z0-9]+)\);')) {
      return TRUE;
    }

    return !$this->isApproxPhpMatch(
      file_get_contents($this->getAbsFileName()),
      $this->getRaw());
  }

  /**
   * Run generator.
   */
  public function run() {
    echo "Generating {$this->name} as " . $this->getRelFileName() . "\n";

    if (empty($this->tables[$this->name]['base'])) {
      echo "No base defined for {$this->name}, skipping output generation\n";
      return;
    }

    $template = new CRM_Core_CodeGen_Util_Template('php');
    $template->assign('table', $this->tables[$this->name]);
    if (empty($this->tables[$this->name]['index'])) {
      $template->assign('indicesPhp', var_export([], 1));
    }
    else {
      $template->assign('indicesPhp', var_export($this->tables[$this->name]['index'], 1));
    }
    $template->assign('genCodeChecksum', $this->getTableChecksum());
    $template->assign('tsFunctionName', $this->tsFunctionName);
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
      if (empty($this->tables[$this->name]['index'])) {
        $template->assign('indicesPhp', var_export([], 1));
      }
      else {
        $template->assign('indicesPhp', var_export($this->tables[$this->name]['index'], 1));
      }
      $template->assign('genCodeChecksum', 'NEW');
      $template->assign('tsFunctionName', $this->tsFunctionName);
      $this->raw = $template->fetch('dao.tpl');
    }
    return $this->raw;
  }

  /**
   * Get relative file name.
   *
   * @return string
   */
  public function getRelFileName() {
    return $this->tables[$this->name]['fileName'];
  }

  /**
   * Get the absolute file name.
   *
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
      $flat = [];
      CRM_Utils_Array::flatten($this->tables[$this->name], $flat);
      ksort($flat);
      $this->tableChecksum = md5(json_encode($flat));
    }
    return $this->tableChecksum;
  }

}
