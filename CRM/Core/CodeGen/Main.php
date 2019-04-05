<?php

/**
 * Class CRM_Core_CodeGen_Main
 */
class CRM_Core_CodeGen_Main {
  var $buildVersion;
  var $db_version;
  var $cms; // drupal, joomla, wordpress

  var $CoreDAOCodePath;
  var $sqlCodePath;
  var $phpCodePath;
  var $tplCodePath;
  var $schemaPath; // ex: schema/Schema.xml

  /**
   * Definitions of all tables.
   *
   * @var array
   *   Ex: $tables['civicrm_address_format']['className'] = 'CRM_Core_DAO_AddressFormat';
   */
  var $tables;

  /**
   * @var array
   *   Ex: $database['tableAttributes_modern'] = "ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";
   */
  var $database;

  /**
   * @var string|NULL path in which to store a marker that indicates the last execution of
   * GenCode. If a matching marker already exists, GenCode doesn't run.
   */
  var $digestPath;

  /**
   * @var string|NULL a digest of the inputs to the code-generator (eg the properties and source files)
   */
  var $sourceDigest;

  /**
   * @param $CoreDAOCodePath
   * @param $sqlCodePath
   * @param $phpCodePath
   * @param $tplCodePath
   * @param $IGNORE
   * @param $argCms
   * @param $argVersion
   * @param $schemaPath
   * @param $digestPath
   */
  public function __construct($CoreDAOCodePath, $sqlCodePath, $phpCodePath, $tplCodePath, $IGNORE, $argCms, $argVersion, $schemaPath, $digestPath) {
    $this->CoreDAOCodePath = $CoreDAOCodePath;
    $this->sqlCodePath = $sqlCodePath;
    $this->phpCodePath = $phpCodePath;
    $this->tplCodePath = $tplCodePath;
    $this->digestPath = $digestPath;
    $this->sourceDigest = NULL;

    // default cms is 'drupal', if not specified
    $this->cms = isset($argCms) ? strtolower($argCms) : 'drupal';

    $versionFile = $this->phpCodePath . "/xml/version.xml";
    $versionXML = CRM_Core_CodeGen_Util_Xml::parse($versionFile);
    $this->db_version = $versionXML->version_no;
    $this->buildVersion = preg_replace('/^(\d{1,2}\.\d{1,2})\.(\d{1,2}|\w{4,7})$/i', '$1', $this->db_version);
    if (isset($argVersion)) {
      // change the version to that explicitly passed, if any
      $this->db_version = $argVersion;
    }

    $this->schemaPath = $schemaPath;
  }

  /**
   * Automatically generate a variety of files.
   */
  public function main() {
    echo "\ncivicrm_domain.version := " . $this->db_version . "\n\n";
    if ($this->buildVersion < 1.1) {
      echo "The Database is not compatible for this version";
      exit();
    }

    if (substr(phpversion(), 0, 1) < 5) {
      echo phpversion() . ', ' . substr(phpversion(), 0, 1) . "\n";
      echo "
CiviCRM requires a PHP Version >= 5
Please upgrade your php / webserver configuration
Alternatively you can get a version of CiviCRM that matches your PHP version
";
      exit();
    }

    foreach ($this->getTasks() as $task) {
      if (getenv('GENCODE_FORCE') || $task->needsUpdate()) {
        $task->run();
      }
    }
  }

  /**
   * @return array
   *   Array<CRM_Core_CodeGen_ITask>.
   * @throws \Exception
   */
  public function getTasks() {
    $this->init();

    $tasks = [];
    $tasks[] = new CRM_Core_CodeGen_Config($this);
    $tasks[] = new CRM_Core_CodeGen_Reflection($this);
    $tasks[] = new CRM_Core_CodeGen_Schema($this);
    foreach (array_keys($this->tables) as $name) {
      $tasks[] = new CRM_Core_CodeGen_DAO($this, $name);
    }
    $tasks[] = new CRM_Core_CodeGen_I18n($this);
    return $tasks;
  }

  /**
   * Compute a digest based on the GenCode logic (PHP/tpl).
   *
   * @return string
   */
  public function getSourceDigest() {
    if ($this->sourceDigest === NULL) {
      $srcDir = CRM_Core_CodeGen_Util_File::findCoreSourceDir();
      $files = CRM_Core_CodeGen_Util_File::findManyFiles([
        ["$srcDir/CRM/Core/CodeGen", '*.php'],
        ["$srcDir/xml", "*.php"],
        ["$srcDir/xml", "*.tpl"],
      ]);

      $this->sourceDigest = CRM_Core_CodeGen_Util_File::digestAll($files);
    }
    return $this->sourceDigest;
  }

  protected function init() {
    if (!$this->database || !$this->tables) {
      $specification = new CRM_Core_CodeGen_Specification();
      $specification->parse($this->schemaPath, $this->buildVersion);
      # cheese:
      $this->database = $specification->database;
      $this->tables = $specification->tables;
    }
  }

}
