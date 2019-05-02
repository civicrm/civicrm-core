<?php
$options = getopt('bc:ht:'); if (isset($options['h'])) {
  print ("\nUsage: php civimail-spooler.php [-bh] [-c <config>] [-t <period>]\n");
  print ("   -b  Run this process continuously\n");
  print ("   -c  Path to CiviCRM civicrm.settings.php\n");
  print ("   -h  Print this help message\n");
  print ("   -t  In continuous mode, the period (in seconds) to wait between queue events\n\n");
  exit();
}

if (isset($options['c'])) {
  $config_file = $options['c'];
}

eval('
require_once "$config_file";
require_once "CRM/Core/Config.php";
');

$config = CRM_Core_Config::singleton();

/* Temporary permissioning hack for now */


CRM_Utils_System_Soap::swapUF();

if (isset($options['t']) && is_int($options['t']) && $options['t'] > 0) {
  $config->mailerPeriod = $options['t'];
}

if (isset($options['b'])) {
  while (TRUE) {
    /* TODO: put some syslog calls in here.  Also, we may want to fork the
         * process into the background and provide init.d scripts */



    CRM_Mailing_BAO_MailingJob::runJobs();
    sleep($config->mailerPeriod);
  }
}
else {
  CRM_Mailing_BAO_MailingJob::runJobs();
}

