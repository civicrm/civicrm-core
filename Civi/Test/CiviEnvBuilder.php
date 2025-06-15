<?php
namespace Civi\Test;

use Civi\Test\CiviEnvBuilder\CallbackStep;
use Civi\Test\CiviEnvBuilder\CoreSchemaStep;
use Civi\Test\CiviEnvBuilder\ExtensionsStep;
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

  public static ?CiviEnvBuilder $lastApplied = NULL;

  protected $name;

  /**
   *
   * @var bool
   */
  private $useOnce = FALSE;

  private $steps = [];

  /**
   * @var string|null
   *   A digest of the values in $steps.
   */
  private $targetSignature = NULL;

  /**
   * Identify which test/agent/process was responsible for creating this environment.
   *
   * @var string|null
   */
  private ?string $appliedBy = NULL;

  /**
   * A detailed snapshot of how the environment looked when it was first applied.
   *
   * @var array|null
   */
  private ?array $detailedSnapshot = NULL;

  public function __construct(string $name = 'CiviEnvBuilder') {
    $this->name = $name;
  }

  public function setName(string $name) {
    $this->name = $name;
    return $this;
  }

  public function addStep(StepInterface $step) {
    $this->targetSignature = NULL;
    $this->steps[] = $step;
    return $this;
  }

  /**
   * Mark this as a single-use environment.
   *
   * If enabled, then we will reinitialize the environment before and/or after
   * the test. Use this if you have a sloppy test that fails to clean up after itself.
   *
   * @param bool $useOnce
   * @return $this
   */
  public function useOnce(bool $useOnce = TRUE) {
    $this->useOnce = $useOnce;
    return $this;
  }

  public function callback($callback, $signature = NULL) {
    return $this->addStep(new CallbackStep($callback, $signature));
  }

  /**
   * Generate the core SQL tables.
   *
   * @return \Civi\Test\CiviEnvBuilder
   */
  public function coreSchema() {
    return $this->addStep(new CoreSchemaStep());
  }

  public function sql($sql) {
    return $this->addStep(new SqlStep($sql));
  }

  public function sqlFile($file) {
    return $this->addStep(new SqlFileStep($file));
  }

  /**
   * Require that an extension be installed.
   *
   * @param string|array $names
   *   One or more extension names. You may use a wildcard '*'.
   * @return CiviEnvBuilder
   */
  public function install($names) {
    return $this->addStep(new ExtensionsStep('install', $names));
  }

  /**
   * Require an extension be installed (identified by its directory).
   *
   * @param string $dir
   *   The current test directory. We'll search for info.xml to
   *   see what this extension is.
   * @return CiviEnvBuilder
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function installMe($dir) {
    return $this->addStep(new ExtensionsStep('install', $this->whoAmI($dir)));
  }

  /**
   * Require an extension be uninstalled.
   *
   * @param string|array $names
   *   One or more extension names. You may use a wildcard '*'.
   * @return CiviEnvBuilder
   */
  public function uninstall($names) {
    return $this->addStep(new ExtensionsStep('uninstall', $names));
  }

  /**
   * Require an extension be uninstalled (identified by its directory).
   *
   * @param string $dir
   *   The current test directory. We'll search for info.xml to
   *   see what this extension is.
   * @return CiviEnvBuilder
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function uninstallMe($dir) {
    return $this->addStep(new ExtensionsStep('uninstall', $this->whoAmI($dir)));
  }

  protected function assertValid() {
    foreach ($this->steps as $step) {
      if (!$step->isValid()) {
        throw new RuntimeException("Found invalid step: " . var_export($step, TRUE));
      }
    }
  }

  /**
   * @return string
   */
  protected function getTargetSignature() {
    if ($this->targetSignature === NULL) {
      if ($this->useOnce) {
        $buf = \random_bytes(24);
      }
      else {
        $buf = '';
        foreach ($this->steps as $step) {
          $buf .= $step->getSig();
        }
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
   * Determine if there's been a change in the preferred configuration.
   * If the preferred-configuration matches the last test, keep it. Otherwise,
   * destroy and recreate.
   *
   * @param bool $force
   *   Forcibly execute the build, even if the configuration hasn't changed.
   *   This will slow-down the tests, but it may be appropriate for some very sloppy
   *   tests.
   * @return CiviEnvBuilder
   */
  public function apply($force = FALSE) {
    return \Civi\Test::asPreInstall(function() use ($force) {
      $dbName = \Civi\Test::dsn('database');
      $query = "USE {$dbName};"
        . "CREATE TABLE IF NOT EXISTS civitest_revs (name VARCHAR(64) PRIMARY KEY, rev VARCHAR(64));";

      if (\Civi\Test::execute($query) === FALSE) {
        throw new \RuntimeException("Failed to flag schema version: $query");
      }

      if (empty($GLOBALS['CIVICRM_TEST_CASE'])) {
        $this->appliedBy = 'Unknown';
      }
      else {
        $test = $GLOBALS['CIVICRM_TEST_CASE'];
        $this->appliedBy = get_class($test) . '::';
        $this->appliedBy .= (is_callable([$test, 'name']) ? $test->name() : $test->getName());
      }

      $this->assertValid();

      if (SloppyTestChecker::isActive() && static::$lastApplied && !static::$lastApplied->useOnce) {
        $currentSnapshot = SloppyTestChecker::createSnapshot();
        SloppyTestChecker::doComparison(static::$lastApplied->detailedSnapshot, $currentSnapshot, static::$lastApplied->appliedBy, $this->appliedBy);
      }

      if (!$force && $this->getSavedSignature() === $this->getTargetSignature()) {
        $this->finalizeApply();
        return $this;
      }

      fprintf(STDERR, "\nInitializing \"%s\" (%s) in \"%s\"\n", $this->name, $this->getTargetSignature(), $dbName);

      foreach ($this->steps as $step) {
        $step->run($this);
      }
      $this->setSavedSignature($this->getTargetSignature());
      $this->finalizeApply();

      return $this;
    });
  }

  private function finalizeApply(): void {
    if (SloppyTestChecker::isActive()) {
      $this->detailedSnapshot = SloppyTestChecker::createSnapshot();
    }
    static::$lastApplied = $this;
  }

  /**
   * @param $dir
   * @return null
   * @throws \CRM_Extension_Exception_ParseException
   */
  protected function whoAmI($dir) {
    while ($dir && dirname($dir) !== $dir && !file_exists("$dir/info.xml")) {
      $dir = dirname($dir);
    }
    if (file_exists("$dir/info.xml")) {
      $info = \CRM_Extension_Info::loadFromFile("$dir/info.xml");
      $name = $info->key;
      return $name;
    }
    return $name;
  }

}
