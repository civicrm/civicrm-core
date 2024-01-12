<?php

require_once dirname(dirname(__DIR__)) . '/flexmailer.php';

ini_set('memory_limit', '2G');

// We need to allow CiviUnitTestCase... but may break E2E support....

// eval(cv('php:boot --level=classloader', 'phpcode'));
define('CIVICRM_CONTAINER_CACHE', 'never');
define('CIVICRM_TEST', 1);
putenv('CIVICRM_UF=' . ($_ENV['CIVICRM_UF'] = 'UnitTests'));
// phpcs:disable
eval(cv('php:boot --level=settings', 'phpcode'));
// phpcs:enable

if (CIVICRM_UF === 'UnitTests') {
  Civi\Test::headless()->apply();
}

/**
 * Call the "cv" command.
 *
 * @param string $cmd
 *   The rest of the command to send.
 * @param string $decode
 *   Ex: 'json' or 'phpcode'.
 * @return string
 *   Response output (if the command executed normally).
 * @throws \RuntimeException
 *   If the command terminates abnormally.
 */
function cv($cmd, $decode = 'json') {
  $cmd = 'cv ' . $cmd;
  $descriptorSpec = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => STDERR];
  $oldOutput = getenv('CV_OUTPUT');
  putenv("CV_OUTPUT=json");
  $process = proc_open($cmd, $descriptorSpec, $pipes, __DIR__);
  putenv("CV_OUTPUT=$oldOutput");
  fclose($pipes[0]);
  $result = stream_get_contents($pipes[1]);
  fclose($pipes[1]);
  if (proc_close($process) !== 0) {
    throw new RuntimeException("Command failed ($cmd):\n$result");
  }
  switch ($decode) {
    case 'raw':
      return $result;

    case 'phpcode':
      // If the last output is /*PHPCODE*/, then we managed to complete execution.
      if (substr(trim($result), 0, 12) !== "/*BEGINPHP*/" || substr(trim($result), -10) !== "/*ENDPHP*/") {
        throw new \RuntimeException("Command failed ($cmd):\n$result");
      }
      return $result;

    case 'json':
      return json_decode($result, 1);

    default:
      throw new RuntimeException("Bad decoder format ($decode)");
  }
}
