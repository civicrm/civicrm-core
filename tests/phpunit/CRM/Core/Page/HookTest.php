<?php

/**
 * Test that page hooks only get invoked once per page run.
 */
class CRM_Core_Page_HookTest extends CiviUnitTestCase {
  public $DBResetRequired = TRUE;

  /**
   * The list of classes extending CRM_Core_Page_Basic: the ones to try the
   * `run()` method on.
   *
   * @var array
   */
  public $basicPages = [];

  /**
   * A place to hold the counts of hook invocations.
   *
   * @var array
   */
  public $hookCount = [];

  /**
   * Classes that should be skipped
   *
   * The main reason is that they look for URL parameters that we don't know to
   * provide.
   *
   * TODO: track down what's needed (in a way that we can be confident for
   * testing) and quit skipping them.
   *
   * @var array
   */
  public $skip = [
    'CRM_Contact_Page_DedupeFind',
    'CRM_Mailing_Page_Report',
    'CRM_Financial_Page_BatchTransaction',
    'CRM_Admin_Page_PreferencesDate',
    'CRM_Admin_Page_Extensions',
    'CRM_Admin_Page_PaymentProcessor',
    'CRM_Admin_Page_LabelFormats',
    // This is a page with no corresponding form:
    'CRM_Admin_Page_EventTemplate',
  ];

  /**
   * Set up the list of pages to evaluate by going through the menu.
   */
  public function setUp() {
    // Get all of the menu items in CiviCRM.
    $items = CRM_Core_Menu::items(TRUE);
    // Check if they extend the class we care about; test if needed.
    foreach ($items as $item) {
      $class = is_array($item['page_callback']) ? $item['page_callback'][0] : $item['page_callback'];
      if (in_array($class, $this->skip)) {
        continue;
      }
      if (is_subclass_of($class, 'CRM_Core_Page_Basic')) {
        $this->basicPages[] = $class;
      }
    }
    parent::setUp();
  }

  /**
   * Make sure form hooks are only invoked once.
   */
  public function testFormsCallBuildFormOnce() {
    CRM_Utils_Hook_UnitTests::singleton()->setHook('civicrm_buildForm', [$this, 'onBuildForm']);
    CRM_Utils_Hook_UnitTests::singleton()->setHook('civicrm_preProcess', [$this, 'onPreProcess']);
    $_REQUEST = ['action' => 'add'];
    foreach ($this->basicPages as $pageName) {
      // Reset the counters
      $this->hookCount = [
        'buildForm' => [],
        'preProcess' => [],
      ];
      $page = new $pageName();
      ob_start();
      $page->run();
      ob_end_clean();
      $this->runTestAssert($pageName);
    }
  }

  /**
   * Go through the record of hook invocation and make sure that each hook has
   * run once and no more than once per class.
   *
   * @param string $pageName
   *   The page/form evaluated.
   */
  private function runTestAssert($pageName) {
    foreach ($this->hookCount as $hook => $hookUsage) {
      $ran = FALSE;
      foreach ($hookUsage as $class => $count) {
        $ran = TRUE;
        // The hook should have run once and only once.
        $this->assertEquals(1, $count, "Checking $pageName: $hook invoked multiple times with $class");
      }
      $this->assertTrue($ran, "$hook never invoked for $pageName");
    }
  }

  /**
   * Make sure pageRun hook is only invoked once.
   */
  public function testPagesCallPageRunOnce() {
    CRM_Utils_Hook_UnitTests::singleton()->setHook('civicrm_pageRun', [$this, 'onPageRun']);
    $_REQUEST = ['action' => 'browse'];
    foreach ($this->basicPages as $pageName) {
      // Reset the counters
      $this->hookCount = ['pageRun' => []];
      $page = new $pageName();
      ob_start();
      $page->run();
      ob_end_clean();
      $this->runTestAssert($pageName);
    }
  }

  /**
   * Implements hook_civicrm_buildForm().
   *
   * Increment the count of uses of this hook per formName.
   */
  public function onBuildForm($formName, &$form) {
    $this->incrementHookCount('buildForm', $formName);
  }

  public function onPreProcess($formName, &$form) {
    $this->incrementHookCount('preProcess', $formName);
  }

  /**
   * Implements hook_civicrm_pageRun().
   *
   * Increment the count of uses of this hook per page class.
   */
  public function onPageRun(&$page) {
    $this->incrementHookCount('pageRun', get_class($page));
  }

  /**
   * Increment the count of uses of a hook in a class.
   *
   * @param string $hook
   *   The hook being used.
   * @param string $class
   *   The class name of the page or form.
   */
  private function incrementHookCount($hook, $class) {
    if (empty($this->hookCount[$hook][$class])) {
      $this->hookCount[$hook][$class] = 0;
    }
    $this->hookCount[$hook][$class]++;
  }

}
