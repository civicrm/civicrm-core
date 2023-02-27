<?php
namespace Civi\Test\CiviEnvBuilder;

class SqlStep implements StepInterface {
  private $sql;

  /**
   * SqlFileStep constructor.
   * @param string $sql
   */
  public function __construct($sql) {
    $this->sql = $sql;
  }

  public function getSig() {
    return md5($this->sql);
  }

  public function isValid() {
    return TRUE;
  }

  /**
   * @param \CiviEnvBuilder $ctx
   * @throws \RuntimeException
   */
  public function run($ctx) {
    if (\Civi\Test::execute($this->sql) === FALSE) {
      throw new \RuntimeException("Cannot execute: {$this->sql}");
    }
  }

}
