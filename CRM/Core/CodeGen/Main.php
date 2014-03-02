<?php

class CRM_Core_CodeGen_Main {
  public $civicrm_root_path;
  public $doctrine;
  public $specification;
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
  public $xmlTemplatePath;

  function __construct($options = array()) {
    date_default_timezone_set('UTC');
    $this->civicrm_root_path = dirname(dirname(dirname(__DIR__)));
    $this->CoreDAOCodePath = CRM_Utils_Path::join($this->civicrm_root_path, 'CRM', 'Core', 'DAO');
    $this->sqlCodePath = CRM_Utils_Path::join($this->civicrm_root_path, 'sql');
    $this->phpCodePath = $this->civicrm_root_path;
    $this->tplCodePath = CRM_Utils_Path::join($this->civicrm_root_path, 'templates');
    $this->digestPath = CRM_Utils_Array::value('digest-path', $options);
    $this->digest = NULL;
    $this->xmlTemplatePath = CRM_Utils_Path::join($this->civicrm_root_path, 'xml', 'templates');
    $this->cms = CRM_Utils_Array::value('cms', $options, 'Drupal');
    $this->schemaPath = CRM_Utils_Path::join($this->civicrm_root_path, 'xml', 'schema', 'Schema.xml');
    $this->schemaPath = CRM_Utils_Array::value('schema-path', $options, $this->schemaPath);
    define('CIVICRM_UF', $this->cms);
    CRM_Core_CodeGen_Util_Template::$smartyPluginDirs = array(
      CRM_Utils_Path::join($this->civicrm_root_path, 'packages', 'Smarty', 'plugins'),
      CRM_Utils_Path::join($this->civicrm_root_path, 'CRM', 'Core', 'Smarty', 'plugins'),
    );
    if (array_key_exists('civi-version', $options)) {
      $this->db_version = $options['civi-version'];
    } else {
      $version_file_path = CRM_Utils_Path::join($this->civicrm_root_path, 'xml', "version.xml");
      $versionXML = CRM_Core_CodeGen_Util_Xml::parse($version_file_path);
      $this->db_version = $versionXML->version_no;
    }
    $this->buildVersion = preg_replace('/^(\d{1,2}\.\d{1,2})\.(\d{1,2}|\w{4,7})$/i', '$1', $this->db_version);
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

    $this->specification = new CRM_Core_CodeGen_Specification();
    $this->specification->parse($this->schemaPath, $this->buildVersion);
    $this->doctrine = new CRM_Core_CodeGen_Doctrine();
    $this->doctrine->load();

    $this->runAllTasks();

    if (!empty($this->digestPath)) {
      file_put_contents($this->digestPath, $this->getDigest());
    }
  }

  function runAllTasks() {
    // TODO: This configuration can be manipulated dynamically.
    $components = $this->getTasks();
    foreach ($components as $component) {

      // special handling for entity generation
      if ($component == 'CRM_Core_CodeGen_Entity') {
        $specification = new CRM_Core_CodeGen_EntitySpecification();
        $specification->parse($this->schemaPath, $this->buildVersion);
        $this->database = $specification->database;
        $this->tables = $specification->tables;
      }

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
      'CRM_Core_CodeGen_Test',
      'CRM_Core_CodeGen_I18n',
      //'CRM_Core_CodeGen_Entity', // need to uncomment this for entity generation
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

  function getExpectedFiles() {
    return array(
      $this->sqlCodePath . '/civicrm.mysql',
      $this->phpCodePath . '/CRM/Contact/DAO/Contact.php',
    );
  }

  function hasExpectedFiles() {
    foreach ($this->getExpectedFiles() as $file) {
      if (!file_exists($file)) {
        return FALSE;
      }
    }
    return TRUE;
  }
}
