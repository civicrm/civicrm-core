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
   * @var string|NULL path in which to store a marker that indicates the last execution of
   * GenCode. If a matching marker already exists, GenCode doesn't run.
   */
  var $digestPath;

  /**
   * @var string|NULL a digest of the inputs to the code-generator (eg the properties and source files)
   */
  var $digest;

  /**
   * @param $CoreDAOCodePath
   * @param $sqlCodePath
   * @param $phpCodePath
   * @param $tplCodePath
   * @param $smartyPluginDirs
   * @param $argCms
   * @param $argVersion
   * @param $schemaPath
   * @param $digestPath
   */
  function __construct($CoreDAOCodePath, $sqlCodePath, $phpCodePath, $tplCodePath, $smartyPluginDirs, $argCms, $argVersion, $schemaPath, $digestPath) {
    $this->CoreDAOCodePath = $CoreDAOCodePath;
    $this->sqlCodePath = $sqlCodePath;
    $this->phpCodePath = $phpCodePath;
    $this->tplCodePath = $tplCodePath;
    $this->digestPath = $digestPath;
    $this->digest = NULL;

    // default cms is 'drupal', if not specified
    $this->cms = isset($argCms) ? strtolower($argCms) : 'drupal';

    CRM_Core_CodeGen_Util_Smarty::singleton()->setPluginDirs($smartyPluginDirs);

    $versionFile        = "version.xml";
    $versionXML         = CRM_Core_CodeGen_Util_Xml::parse($versionFile);
    $this->db_version         = $versionXML->version_no;
    $this->buildVersion = preg_replace('/^(\d{1,2}\.\d{1,2})\.(\d{1,2}|\w{4,7})$/i', '$1', $this->db_version);
    if (isset($argVersion)) {
      // change the version to that explicitly passed, if any
      $this->db_version = $argVersion;
    }

    $this->schemaPath = $schemaPath;
  }

  /**
   * Automatically generate a variety of files
   *
   */
  function main() {
    if (!empty($this->digestPath) && file_exists($this->digestPath) && $this->hasExpectedFiles()) {
      if ($this->getDigest() === file_get_contents($this->digestPath)) {
        echo "GenCode has previously executed. To force execution, please (a) omit CIVICRM_GENCODE_DIGEST\n";
        echo "or (b) remove {$this->digestPath} or (c) call GenCode with new parameters.\n";
        exit();
      }
      // Once we start GenCode, the old build is invalid
      unlink($this->digestPath);
    }

    echo "\ncivicrm_domain.version := ". $this->db_version . "\n\n";
    if ($this->buildVersion < 1.1) {
      echo "The Database is not compatible for this version";
      exit();
    }

    if (substr(phpversion(), 0, 1) != 5) {
      echo phpversion() . ', ' . substr(phpversion(), 0, 1) . "\n";
      echo "
CiviCRM requires a PHP Version >= 5
Please upgrade your php / webserver configuration
Alternatively you can get a version of CiviCRM that matches your PHP version
";
      exit();
    }

    $specification = new CRM_Core_CodeGen_Specification();
    $specification->parse($this->schemaPath, $this->buildVersion);
    # cheese:
    $this->database = $specification->database;
    $this->tables = $specification->tables;

    $this->runAllTasks();

    if (!empty($this->digestPath)) {
      file_put_contents($this->digestPath, $this->getDigest());
    }
  }

  function runAllTasks() {
    // TODO: This configuration can be manipulated dynamically.
    $components = $this->getTasks();
    foreach ($components as $component) {
      $task = new $component($this);

      if (is_a($task, 'CRM_Core_CodeGen_ITask')) {
        $task->setConfig($this);
        $task->run();
      } else {
        echo "Bad news: we tried to run a codegen task of an unrecognized type: {$component}\n";
        exit();
      }
    }
  }

  /**
   * @return array of class names; each class implements CRM_Core_CodeGen_ITask
   */
  public function getTasks() {
    $components = array(
      'CRM_Core_CodeGen_Config',
      'CRM_Core_CodeGen_Reflection',
      'CRM_Core_CodeGen_Schema',
      'CRM_Core_CodeGen_DAO',
      //'CRM_Core_CodeGen_Test',
      'CRM_Core_CodeGen_I18n',
    );
    return $components;
  }

  /**
   * Compute a digest based on the inputs to the code-generator (ie the properties
   * of the codegen and the source files loaded by the codegen).
   *
   * @return string
   */
  function getDigest() {
    if ($this->digest === NULL) {
      $srcDir = CRM_Core_CodeGen_Util_File::findCoreSourceDir();
      $files = CRM_Core_CodeGen_Util_File::findManyFiles(array(
        array("$srcDir/CRM/Core/CodeGen", '*.php'),
        array("$srcDir/xml", "*.php"),
        array("$srcDir/xml", "*.tpl"),
        array("$srcDir/xml", "*.xml"),
      ));

      $properties = var_export(array(
        CRM_Core_CodeGen_Util_File::digestAll($files),
        $this->buildVersion,
        $this->db_version,
        $this->cms,
        $this->CoreDAOCodePath,
        $this->sqlCodePath,
        $this->phpCodePath,
        $this->tplCodePath,
        $this->schemaPath,
        $this->getTasks(),
      ), TRUE);

      $this->digest = md5($properties);
    }
    return $this->digest;
  }

  /**
   * @return array
   */
  function getExpectedFiles() {
    return array(
      $this->sqlCodePath . '/civicrm.mysql',
      $this->phpCodePath . '/CRM/Contact/DAO/Contact.php',
    );
  }

  /**
   * @return bool
   */
  function hasExpectedFiles() {
    foreach ($this->getExpectedFiles() as $file) {
      if (!file_exists($file)) {
        return FALSE;
      }
    }
    return TRUE;
  }
}
