#!/usr/bin/env php
<?php

$civicrm_source_dir_path = dirname(__DIR__);
$autoloader_path = implode(DIRECTORY_SEPARATOR, array('CRM', 'Core', 'ClassLoader.php'));
require_once($autoloader_path);
CRM_Core_ClassLoader::singleton()->register();
require_once(CRM_Utils_Path::join($civicrm_source_dir_path, "packages", "optionparser", "lib", "OptionParser.php"));

$option_parser = new OptionParser();
$option_parser->addRule('h|help', 'Display this help message');
$option_parser->addRule('m|mysql-ram-server', 'Start a MySQL daemon backed by a RAM disk that will be used for the tests');
$option_parser->addRule('s|no-selenium', 'Don\'t try and use Selenium.');
$option_parser->addRule('p|php-unit::', 'Arguments to be passed to phpunit. If there are any spaces, enclose this whole option value in single quotes.');
$option_parser->addRule('c|clean', 'Clean up any daemons and files that running the tests created.');

function usage($option_parser) {
  $stderr = fopen('php://stderr', 'w');
  fwrite($stderr, "Usage: " . $option_parser->getProgramName() . " [options]\n");
  fwrite($stderr, $option_parser->getUsage());
}

try {
  $option_parser->parse();
  $options = $option_parser->getAllOptions();
  $options['base_path'] = $civicrm_source_dir_path;
  $tests = new CRM_Tests_Runner($options);
  if ($option_parser->help) {
    usage($option_parser);
  } elseif ($option_parser->clean) {
    $tests->clean();
  } else {
    $tests->run();
  }
} catch (InvalidOption $e) {
  $stderr = fopen('php://stderr', 'w');
  fwrite($stderr, $e->getMessage() . "\n");
  fwrite($stderr, "\n");
  usage($option_parser);
}

