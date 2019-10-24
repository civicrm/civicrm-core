<?php

require_once 'CRM/Core/Page.php';

/**
 * Accept requests for "civicrm/dev/qunit/$ext/$suite"; locate the qunit
 * test-suite ($suite) in an extension ($ext) and render it.
 */
class CRM_Core_Page_QUnit extends CRM_Core_Page {
  protected $tplFile = NULL;

  /**
   * Run.
   *
   * @throws \CRM_Core_Exception
   */
  public function run() {
    list ($ext, $suite) = $this->getRequestExtAndSuite();
    if (empty($ext) || empty($suite)) {
      throw new CRM_Core_Exception("FIXME: Not implemented: QUnit browser");
    }

    if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $suite) || strpos($suite, '..') !== FALSE) {
      throw new CRM_Core_Exception("Malformed suite name");
    }

    $path = CRM_Extension_System::singleton()->getMapper()->keyToBasePath($ext);
    if (!is_dir("$path/tests/qunit/$suite")) {
      throw new CRM_Core_Exception("Failed to locate test suite");
    }

    // Load the test suite -- including any PHP, TPL, or JS content
    if (file_exists("$path/tests/qunit/$suite/test.php")) {
      // e.g. load resources
      require_once "$path/tests/qunit/$suite/test.php";
    }
    if (file_exists("$path/tests/qunit/$suite/test.tpl")) {
      // e.g. setup markup and/or load resources
      CRM_Core_Smarty::singleton()->addTemplateDir("$path/tests");
      $this->assign('qunitTpl', "qunit/$suite/test.tpl");
    }
    if (file_exists("$path/tests/qunit/$suite/test.js")) {
      CRM_Core_Resources::singleton()->addScriptFile($ext, "tests/qunit/$suite/test.js", 1000, 'html-header');
    }

    CRM_Utils_System::setTitle(ts('QUnit: %2 (%1)', [1 => $ext, 2 => $suite]));
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'bower_components/qunit/qunit/qunit.js', 1, 'html-header')
      ->addStyleFile('civicrm', 'bower_components/qunit/qunit/qunit.css', 1, 'html-header');
    parent::run();
  }

  /**
   * Extract the extension and suite from the request path.
   *
   * @return array
   */
  public function getRequestExtAndSuite() {
    $config = CRM_Core_Config::singleton();
    $arg = explode('/', $_GET[$config->userFrameworkURLVar]);

    if ($arg[1] == 'dev'
      && CRM_Utils_Array::value(2, $arg) == 'qunit'
      && isset($arg[3])
      && isset($arg[4])
    ) {
      return [
        trim(CRM_Utils_Type::escape($arg[3], 'String'), '/'),
        trim(CRM_Utils_Type::escape($arg[4], 'String'), '/'),
      ];
    }
    else {
      return [NULL, NULL];
    }
  }

}
