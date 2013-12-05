#!/usr/bin/env php
<?php

$civicrm_source_dir_path = dirname(__DIR__);
$autoloader_path = implode(DIRECTORY_SEPARATOR, array('CRM', 'Core', 'ClassLoader.php'));
require_once($autoloader_path);
CRM_Core_ClassLoader::singleton()->register();
$packages_path = CRM_Utils_Path::join($civicrm_source_dir_path, 'packages');
if (!file_exists($packages_path)) {
  system("git clone https://github.com/giant-rabbit/civicrm-packages.git packages");
}
set_include_path($packages_path . PATH_SEPARATOR . get_include_path());
require_once(CRM_Utils_Path::join($civicrm_source_dir_path, "packages", "Console", "Getopt.php"));

$option_parser = new CRM_Utils_CommandLineParser();
$option_parser->addOption('h', 'help', 'n', 'Display this help message');
$option_parser->addOption('m', 'mysql-ram-server', 'n', 'Start a MySQL daemon backed by a RAM disk that will be used for the tests');
$option_parser->addOption('s', 'no-selenium', 'n', "Don't try and use Selenium.");
$option_parser->addOption('p', 'php-unit', 'r', 'Arguments to be passed to phpunit. If there are any spaces, enclose ARG in single quotes.');
$option_parser->addOption('c', 'clean', 'n', 'Clean up any daemons and files that running the tests created.');

try {
  $option_parser->parse($argv);
  $options = $option_parser->options;
  $options['base_path'] = $civicrm_source_dir_path;
  $tests = new CRM_Tests_Runner($options);
  if (CRM_Utils_Array::fetch('help', $options, FALSE)) {
    $option_parser->printUsage();
  } elseif (CRM_Utils_Array::fetch('clean', $options, FALSE)) {
    $tests->clean();
  } else {
    $tests->run();
  }
} catch (CRM_Utils_CommandLineParser_ParseException $e) {
  $option_parser->printException($e);
  $option_parser->printUsage();
}

