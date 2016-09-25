<?php
namespace Civi\Test\CiviEnvBuilder;

class SqlFileStep implements StepInterface {
  private $file;

  /**
   * SqlFileStep constructor.
   * @param string $file
   */
  public function __construct($file) {
    $this->file = $file;
  }


  public function getSig() {
    return implode(' ', array(
      $this->file,
      filemtime($this->file),
      filectime($this->file),
    ));
  }

  public function isValid() {
    return is_file($this->file) && is_readable($this->file);
  }

  public function run($ctx) {
    /** @var $ctx \CiviEnvBuilder */
    if (\Civi\Test::execute(@file_get_contents($this->file)) === FALSE) {
      throw new \RuntimeException("Cannot load {$this->file}. Aborting.");
    }
  }

}
