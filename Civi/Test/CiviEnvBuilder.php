<?php
namespace Civi\Test;

use Civi\Test\CiviEnvBuilder\CallbackStep;
use Civi\Test\CiviEnvBuilder\ExtensionStep;
use Civi\Test\CiviEnvBuilder\SqlFileStep;
use Civi\Test\CiviEnvBuilder\SqlStep;
use Civi\Test\CiviEnvBuilder\StepInterface;
use RuntimeException;

/**
 * Class CiviEnvBuilder
 *
 * Provides a fluent interface for tracking a set of steps.
 * By computing and storing a signature for the list steps, we can
 * determine whether to (a) do nothing with the list or (b)
 * reapply all the steps.
 */
class CiviEnvBuilder {
  protected $name;

  private $steps = array();

  /**
   * @var string|NULL
   *   A digest of the values in $steps.
   */
  private $targetSignature = NULL;

  public function __construct($name) {
    $this->name = $name;
  }

  public function addStep(StepInterface $step) {
    $this->targetSignature = NULL;
    $this->steps[] = $step;
    return $this;
  }

  public function callback($callback, $signature = NULL) {
    return $this->addStep(new CallbackStep($callback, $signature));
  }

  public function sql($sql) {
    return $this->addStep(new SqlStep($sql));
  }

  public function sqlFile($file) {
    return $this->addStep(new SqlFileStep($file));
  }

  /**
   * Require an extension (based on its name).
   *
   * @param string $name
   * @return \CiviEnvBuilder
   */
  public function ext($name) {
    return $this->addStep(new ExtensionStep($name));
  }

  /**
   * Require an extension (based on its directory).
   *
   * @param $dir
   * @return \CiviEnvBuilder
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function extDir($dir) {
    while ($dir && dirname($dir) !== $dir && !file_exists("$dir/info.xml")) {
      $dir = dirname($dir);
    }
    if (file_exists("$dir/info.xml")) {
      $info = \CRM_Extension_Info::loadFromFile("$dir/info.xml");
      $name = $info->key;
    }
    return $this->addStep(new ExtensionStep($name));
  }

  protected function assertValid() {
    foreach ($this->steps as $step) {
      if (!$step->isValid()) {
        throw new RuntimeException("Found invalid step: " . var_dump($step, 1));
      }
    }
  }

  /**
   * @return string
   */
  protected function getTargetSignature() {
    if ($this->targetSignature === NULL) {
      $buf = '';
      foreach ($this->steps as $step) {
        $buf .= $step->getSig();
      }
      $this->targetSignature = md5($buf);
    }

    return $this->targetSignature;
  }

  /**
   * @return string
   */
  protected function getSavedSignature() {
    $liveSchemaRev = NULL;
    $pdo = \Civi\Test::pdo();
    $pdoStmt = $pdo->query(sprintf(
      "SELECT rev FROM %s.civitest_revs WHERE name = %s",
      \Civi\Test::dsn('database'),
      $pdo->quote($this->name)
    ));
    foreach ($pdoStmt as $row) {
      $liveSchemaRev = $row['rev'];
    }
    return $liveSchemaRev;
  }

  /**
   * @param $newSignature
   */
  protected function setSavedSignature($newSignature) {
    $pdo = \Civi\Test::pdo();
    $query = sprintf(
      'INSERT INTO %s.civitest_revs (name,rev) VALUES (%s,%s) '
      . 'ON DUPLICATE KEY UPDATE rev = %s;',
      \Civi\Test::dsn('database'),
      $pdo->quote($this->name),
      $pdo->quote($newSignature),
      $pdo->quote($newSignature)
    );

    if (\Civi\Test::execute($query) === FALSE) {
      throw new RuntimeException("Failed to flag schema version: $query");
    }
  }

  /**
   * Determine if the schema is correct. If necessary, destroy and recreate.
   *
   * @param bool $force
   * @return $this
   */
  public function apply($force = FALSE) {
    $dbName = \Civi\Test::dsn('database');
    $query = "USE {$dbName};"
      . "CREATE TABLE IF NOT EXISTS civitest_revs (name VARCHAR(64) PRIMARY KEY, rev VARCHAR(64));";

    if (\Civi\Test::execute($query) === FALSE) {
      throw new \RuntimeException("Failed to flag schema version: $query");
    }

    $this->assertValid();

    if (!$force && $this->getSavedSignature() === $this->getTargetSignature()) {
      return $this;
    }
    foreach ($this->steps as $step) {
      $step->run($this);
    }
    $this->setSavedSignature($this->getTargetSignature());
    return $this;
  }

}
