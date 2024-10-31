<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Civi\Test;

class PageWrapper {

  /**
   * @var \CRM_Core_Page
   */
  private \CRM_Core_Page $page;

  private array $statusMessage;

  /**
   * @var false|string
   */
  private string $output;

  /**
   * @param string $class
   * @param array $urlParameters
   */
  public function __construct(string $class, array $urlParameters = []) {
    $_GET = $urlParameters;
    $this->page = new $class();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_REQUEST = array_merge($_REQUEST, $urlParameters);
  }

  public function run(): void {
    // Ensure the system checks do not run in this context.
    // The `checkAngularModuleSettings()` check is specifically likely
    // to cause crashes but also there are other tests for the checks.
    \CRM_Core_Session::singleton()->timer('check_CRM_Utils_Check', 86400);
    ob_start();
    $this->page->run();
    $this->output = ob_get_clean();
  }

  public function getOutput(): string {
    return $this->output;
  }

}
