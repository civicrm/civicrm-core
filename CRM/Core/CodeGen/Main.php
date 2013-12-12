<?php

class CRM_Core_CodeGen_Main {
  var $buildVersion;
  var $db_version;
  var $cms; // drupal, joomla, wordpress

  var $CoreDAOCodePath;
  var $sqlCodePath;
  var $phpCodePath;
  var $tplCodePath;
  var $schemaPath; // ex: schema/Schema.xml

  function __construct($CoreDAOCodePath, $sqlCodePath, $phpCodePath, $tplCodePath, $smartyPluginDirs, $argCms, $argVersion, $schemaPath) {
    $this->CoreDAOCodePath = $CoreDAOCodePath;
    $this->sqlCodePath = $sqlCodePath;
    $this->phpCodePath = $phpCodePath;
    $this->tplCodePath = $tplCodePath;

    // default cms is 'drupal', if not specified
    $this->cms = isset($argCms) ? strtolower($argCms) : 'drupal';

    CRM_Core_CodeGen_Util_Template::$smartyPluginDirs = $smartyPluginDirs;

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

    if (defined('CIVICRM_GEN_ENTITY') && CIVICRM_GEN_ENTITY) {
      $specification = new CRM_Core_CodeGen_EntitySpecification();
    }
    else {
      $specification = new CRM_Core_CodeGen_Specification();
    }

    $specification->parse($this->schemaPath, $this->buildVersion);
    # cheese:
    $this->database = $specification->database;
    $this->tables = $specification->tables;

    $this->runAllTasks();
  }

  function runAllTasks() {
    // TODO: This configuration can be manipulated dynamically.
    $components = array(
      'CRM_Core_CodeGen_Config',
      'CRM_Core_CodeGen_Reflection',
      'CRM_Core_CodeGen_Schema',
      'CRM_Core_CodeGen_DAO',
      'CRM_Core_CodeGen_Test',
      'CRM_Core_CodeGen_I18n',
    );

    if (defined('CIVICRM_GEN_ENTITY') && CIVICRM_GEN_ENTITY) {
      $components[3] = 'CRM_Core_CodeGen_Entity';
    }

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
}
