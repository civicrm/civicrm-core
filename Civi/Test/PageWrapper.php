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

namespace Civi\Test;

class PageWrapper {

  /**
   * @var \CRM_Core_Page
   */
  private \CRM_Core_Page $page;

  /**
   * Status messages set while the page ran.
   *
   * @var array
   */
  private array $statusMessages = [];

  /**
   * Variables assigned to the template while the page ran.
   *
   * @var array
   */
  private array $templateVariables = [];

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
    $userId = \CRM_Core_Session::getLoggedInContactID();
    \Civi::cache('checks')->set('status_checked_for_user_' . $userId, TRUE, 86400);
    ob_start();
    $this->page->run();
    $this->output = ob_get_clean();
    $this->templateVariables = \CRM_Core_Smarty::singleton()->getTemplateVars();
    $this->statusMessages = \CRM_Core_Session::singleton()->getStatus(TRUE);
  }

  public function getOutput(): string {
    return $this->output;
  }

  /**
   * Get all variables assigned to the template.
   *
   * @return array
   */
  public function getTemplateVariables(): array {
    return $this->templateVariables;
  }

  /**
   * Get a variable assigned to the template.
   *
   * @param string $name
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  public function getTemplateVariable(string $name): mixed {
    if (!array_key_exists($name, $this->templateVariables)) {
      throw new \CRM_Core_Exception("Template variable $name was not assigned");
    }
    return $this->templateVariables[$name];
  }

  /**
   * Get the status messages set while the page ran.
   *
   * @return array
   *   Each message has keys `text`, `title`, `type` and `options`.
   */
  public function getStatusMessages(): array {
    return $this->statusMessages;
  }

}
