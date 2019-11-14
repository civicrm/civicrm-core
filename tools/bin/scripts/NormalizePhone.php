<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This code is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*/

/**
 * A PHP cron script to format all the addresses in the database. Currently
 * it only does geocoding if the geocode values are not set. At a later
 * stage we will also handle USPS address cleanup and other formatting
 * issues
 *
 */

define('THROTTLE_REQUESTS', 0);
function run() {
  session_start();

  require_once '../civicrm.config.php';
  require_once 'CRM/Core/Config.php';

  $config = CRM_Core_Config::singleton();

  require_once 'Console/Getopt.php';
  $shortOptions = "n:p:k:pre";
  $longOptions = ['name=', 'pass=', 'key=', 'prefix='];

  $getopt = new Console_Getopt();
  $args = $getopt->readPHPArgv();

  array_shift($args);
  list($valid, $dontCare) = $getopt->getopt2($args, $shortOptions, $longOptions);

  $vars = [
    'name' => 'n',
    'pass' => 'p',
    'key' => 'k',
    'prefix' => 'pre',
  ];

  foreach ($vars as $var => $short) {
    $$var = NULL;
    foreach ($valid as $v) {
      if ($v[0] == $short || $v[0] == "--$var") {
        $$var = $v[1];
        break;
      }
    }
    if (!$$var) {
      $$var = CRM_Utils_Array::value($var, $_REQUEST);
    }
    $_REQUEST[$var] = $$var;
  }

  // this does not return on failure
  // require_once 'CRM/Utils/System.php';
  CRM_Utils_System::authenticateScript(TRUE, $name, $pass);

  //log the execution of script
  CRM_Core_Error::debug_log_message('NormalizePhone.php');

  // process all phones
  processPhones($config, $prefix);
}

/**
 * @param $config
 * @param null $prefix
 */
function processPhones(&$config, $prefix = NULL) {
  // ignore null phones and phones that already match what we are doing
  $query = "
SELECT     id, phone
FROM       civicrm_phone
WHERE      phone IS NOT NULL
AND        phone NOT REGEXP '^[[:digit:]]{3}-[[:digit:]]{3}-[[:digit:]]{4}$'
";

  $dao = &CRM_Core_DAO::executeQuery($query);

  $updateQuery = "UPDATE civicrm_phone SET phone = %1 where id = %2";
  $params = [
    1 => ['', 'String'],
    2 => [0, 'Integer'],
  ];
  $totalPhone = $validPhone = $nonPrefixedPhone = 0;
  while ($dao->fetch()) {
    $newPhone = processPhone($dao->phone, $prefix);
    echo "$newPhone, {$dao->phone}\n";
    if ($newPhone !== FALSE) {
      $params[1][0] = $newPhone;
      $params[2][0] = $dao->id;
      CRM_Core_DAO::executeQuery($updateQuery, $params);
      echo "{$dao->phone}, $newPhone\n";
    }
    $totalPhone++;
  }
}

/**
 * @param $phone
 * @param null $prefix
 *
 * @return bool|string
 */
function processPhone($phone, $prefix = NULL) {
  // eliminate all white space and non numeric charaters
  $cleanPhone = preg_replace('/[^\d]+/s', '', $phone);

  $len = strlen($cleanPhone);
  if ($prefix &&
    $len == 7
  ) {
    $cleanPhone = $prefix . $cleanPhone;
  }
  elseif ($len != 10) {
    return FALSE;
  }

  // now we have a 10 character string, lets return it as
  // ABC-DEF-GHIJ
  $cleanPhone = substr($cleanPhone, 0, 3) . "-" . substr($cleanPhone, 3, 3) . "-" . substr($cleanPhone, 6, 4);

  return ($cleanPhone == $phone) ? FALSE : $cleanPhone;
}

run();

