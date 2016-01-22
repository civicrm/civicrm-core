<?php
// ADAPTED FROM tools/scripts/phpunit

ini_set('safe_mode', 0);
ini_set('include_path', dirname(__DIR__) . PATH_SEPARATOR . ini_get('include_path'));

#  Relying on system timezone setting produces a warning,
#  doing the following prevents the warning message
if (file_exists('/etc/timezone')) {
  $timezone = trim(file_get_contents('/etc/timezone'));
  if (ini_set('date.timezone', $timezone) === FALSE) {
    echo "ini_set( 'date.timezone', '$timezone' ) failed\n";
  }
}

# Crank up the memory
ini_set('memory_limit', '2G');

eval(cv('php:boot --test', 1));

// This is exists to support CiviUnitTestCase::populateDB(). That doesn't make it a good idea.
require_once "DB.php";
$dsninfo = DB::parseDSN(CIVICRM_DSN);
$GLOBALS['mysql_host'] = $dsninfo['hostspec'];
$GLOBALS['mysql_port'] = @$dsninfo['port'];
$GLOBALS['mysql_user'] = $dsninfo['username'];
$GLOBALS['mysql_pass'] = $dsninfo['password'];
$GLOBALS['mysql_db'] = $dsninfo['database'];

// ------------------------------------------------------------------------------

/**
 * Call the "cv" command.
 *
 * @param string $cmd
 *   The rest of the command to send.
 * @param bool $raw
 *   If TRUE, return the raw output. If FALSE, parse JSON output.
 * @return string
 *   Response output (if the command executed normally).
 * @throws \RuntimeException
 *   If the command terminates abnormally.
 */
function cv($cmd, $raw = FALSE) {
  $cmd = 'cv ' . $cmd;
  $descriptorSpec = array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => STDERR);
  $env = $_ENV + array('CV_OUTPUT' => 'json');
  $process = proc_open($cmd, $descriptorSpec, $pipes, __DIR__, $env);
  fclose($pipes[0]);
  $bootCode = stream_get_contents($pipes[1]);
  fclose($pipes[1]);
  if (proc_close($process) !== 0) {
    throw new RuntimeException("Command failed ($cmd)");
  }
  return $raw ? $bootCode : json_decode($bootCode, 1);
}
