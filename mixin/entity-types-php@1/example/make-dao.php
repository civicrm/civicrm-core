<?php

// Generate the example DAO for use with mixin testing.
//
// Doesn't use GenCode.php because it's not really core.
// Doesn't use generate:entity-boilerplate because it's not a full extension.
// Roll a hard dice.

if (PHP_SAPI !== 'cli') {
  die("This script can only be run from command line.");
}

$includes = [dirname(__DIR__, 3), dirname(__DIR__, 3) . '/packages'];
ini_set('include_path', implode(PATH_SEPARATOR, $includes));
date_default_timezone_set('UTC');
define('CIVICRM_UF', 'Drupal');
define('CIVICRM_UF_BASEURL', '/');
define('CIVICRM_L10N_BASEDIR', getenv('CIVICRM_L10N_BASEDIR') ? getenv('CIVICRM_L10N_BASEDIR') : __DIR__ . '/../l10n');
$GLOBALS['civicrm_paths']['cms.root']['url'] = 'http://gencode.example.com/do-not-use';

require_once 'CRM/Core/ClassLoader.php';
CRM_Core_ClassLoader::singleton()->register();

makeDAOs(__DIR__, __DIR__ . "/xml/schema/CRM/Shimmy/*.xml");

/**
 * @param string $basedir
 *   Where to find XML's and put DAO's.
 * @param string $xmlSchemasGlob
 *   Expression to find XML's.
 */
function makeDAOs(string $basedir, string $xmlSchemasGlob): void {
  $specification = new \CRM_Core_CodeGen_Specification();
  $specification->buildVersion = \CRM_Utils_System::majorVersion();
  $config = new \stdClass();
  $config->phpCodePath = $basedir . '/';
  $config->sqlCodePath = $basedir . '/sql/';
  $config->database = [
    'name' => '',
    'attributes' => '',
    'tableAttributes_modern' => 'ENGINE=InnoDB',
    'tableAttributes_simple' => 'ENGINE=InnoDB',
    'comment' => '',
  ];
  $config->tables = [];

  foreach (glob($xmlSchemasGlob) as $xmlSchema) {
    $dom = new \DomDocument();
    $dom->loadXML(file_get_contents($xmlSchema));
    $xml = simplexml_import_dom($dom);
    if (!$xml) {
      throw new \RuntimeException("There is an error in the XML for $xmlSchema");
    }
    $specification->getTable($xml, $config->database, $config->tables);
    $name = (string) $xml->name;
    $config->tables[$name]['name'] = $name;
    $config->tables[$name]['sourceFile'] = \CRM_Utils_File::relativize($xmlSchema, $basedir);
  }

  foreach ($config->tables as $table) {
    $dao = new \CRM_Core_CodeGen_DAO($config, (string) $table['name'], 'ts');
    ob_start();
    $dao->run();
    ob_end_clean();
    echo "Write " . $dao->getAbsFileName() . "\n";
  }
}
