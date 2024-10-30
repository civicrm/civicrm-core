<?php
namespace Civi\API;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Class ExternalBatch
 * @package Civi\API
 *
 * Perform a series of external, asynchronous, concurrent API call.
 */
class ExternalBatch {
  /**
   * The time to wait when polling for process status (microseconds).
   */
  const POLL_INTERVAL = 10000;

  /**
   * @var array
   *   Array(int $idx => array $apiCall).
   */
  protected $apiCalls;

  protected $defaultParams;

  protected $root;

  protected $settingsPath;

  protected $env;

  /**
   * @var array
   *   Array(int $idx => Process $process).
   */
  protected $processes;

  /**
   * @var array
   *   Array(int $idx => array $apiResult).
   */
  protected $apiResults;

  /**
   * @param array $defaultParams
   *   Default values to merge into any API calls.
   */
  public function __construct($defaultParams = []) {
    global $civicrm_root;
    $this->root = $civicrm_root;
    $this->settingsPath = defined('CIVICRM_SETTINGS_PATH') ? CIVICRM_SETTINGS_PATH : NULL;
    $this->defaultParams = $defaultParams;
    $this->env = $_ENV;
    if (empty($_ENV['PATH'])) {
      // FIXME: If we upgrade to newer Symfony\Process and use the newer
      // inheritEnv feature, then this becomes unnecessary.
      throw new \CRM_Core_Exception('ExternalBatch cannot detect environment: $_ENV is missing. (Tip: Set variables_order=EGPCS in php.ini.)');
    }
  }

  /**
   * @param string $entity
   * @param string $action
   * @param array $params
   * @return ExternalBatch
   */
  public function addCall($entity, $action, $params = []) {
    $params = array_merge($this->defaultParams, $params);

    $this->apiCalls[] = [
      'entity' => $entity,
      'action' => $action,
      'params' => $params,
    ];
    return $this;
  }

  /**
   * @param array $env
   *   List of environment variables to add.
   * @return static
   */
  public function addEnv($env) {
    $this->env = array_merge($this->env, $env);
    return $this;
  }

  /**
   * Run all the API calls concurrently.
   *
   * @return static
   * @throws \CRM_Core_Exception
   */
  public function start() {
    foreach ($this->apiCalls as $idx => $apiCall) {
      $process = $this->createProcess($apiCall);
      $process->start();
      $this->processes[$idx] = $process;
    }
    return $this;
  }

  /**
   * @return int
   *   The number of running processes.
   */
  public function getRunningCount() {
    $count = 0;
    foreach ($this->processes as $process) {
      if ($process->isRunning()) {
        $count++;
      }
    }
    return $count;
  }

  public function wait() {
    while (!empty($this->processes)) {
      usleep(self::POLL_INTERVAL);
      foreach (array_keys($this->processes) as $idx) {
        /** @var \Symfony\Component\Process\Process $process */
        $process = $this->processes[$idx];
        if (!$process->isRunning()) {
          $parsed = json_decode($process->getOutput(), TRUE);
          if ($process->getExitCode() || $parsed === NULL) {
            $this->apiResults[] = [
              'is_error' => 1,
              'error_message' => 'External API returned malformed response.',
              'trace' => [
                'code' => $process->getExitCode(),
                'stdout' => $process->getOutput(),
                'stderr' => $process->getErrorOutput(),
              ],
            ];
          }
          else {
            $this->apiResults[] = $parsed;
          }
          unset($this->processes[$idx]);
        }
      }
    }
    return $this;
  }

  /**
   * @return array
   */
  public function getResults() {
    return $this->apiResults;
  }

  /**
   * @param int $idx
   * @return array
   */
  public function getResult($idx = 0) {
    return $this->apiResults[$idx];
  }

  /**
   * Determine if the local environment supports running API calls
   * externally.
   *
   * @return bool
   */
  public function isSupported() {
    // If you try in Windows, feel free to change this...
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' || !function_exists('proc_open')) {
      return FALSE;
    }
    if (!file_exists($this->root . '/bin/cli.php') || !file_exists($this->settingsPath)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @param array $apiCall
   *   Array with keys: entity, action, params.
   * @return \Symfony\Component\Process\Process
   * @throws \CRM_Core_Exception
   */
  public function createProcess($apiCall) {
    $parts = [];

    if (defined('CIVICRM_TEST') && CIVICRM_TEST) {
      // When testing, civicrm.settings.php may rely on $_CV, which is only
      // populated/propagated if we execute through `cv`.
      $parts[] = 'cv api';
      $parts[] = escapeshellarg($apiCall['entity'] . '.' . $apiCall['action']);
      $parts[] = "--out=json-strict";
      foreach ($apiCall['params'] as $key => $value) {
        $parts[] = escapeshellarg("$key=$value");
      }
    }
    else {
      // But in production, we may not have `cv` installed.
      $executableFinder = new PhpExecutableFinder();
      $php = $executableFinder->find();
      if (!$php) {
        throw new \CRM_Core_Exception("Failed to locate PHP interpreter.");
      }
      $parts[] = $php;
      $parts[] = escapeshellarg($this->root . '/bin/cli.php');
      $parts[] = escapeshellarg("-e=" . $apiCall['entity']);
      $parts[] = escapeshellarg("-a=" . $apiCall['action']);
      $parts[] = "--json";
      $parts[] = escapeshellarg("-u=dummyuser");
      foreach ($apiCall['params'] as $key => $value) {
        $parts[] = escapeshellarg("--$key=$value");
      }
    }

    $command = implode(" ", $parts);
    $env = array_merge($this->env, [
      'CIVICRM_SETTINGS' => $this->settingsPath,
    ]);
    return Process::fromShellCommandline($command, $this->root, $env);
  }

  /**
   * @return string
   */
  public function getRoot() {
    return $this->root;
  }

  /**
   * @param string $root
   */
  public function setRoot($root) {
    $this->root = $root;
  }

  /**
   * @return string
   */
  public function getSettingsPath() {
    return $this->settingsPath;
  }

  /**
   * @param string $settingsPath
   */
  public function setSettingsPath($settingsPath) {
    $this->settingsPath = $settingsPath;
  }

}
