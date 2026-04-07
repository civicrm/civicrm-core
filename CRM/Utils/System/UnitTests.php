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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Helper authentication class for unit tests
 */
class CRM_Utils_System_UnitTests extends CRM_Utils_System_Base {

  /**
   */
  public function __construct() {
    $this->is_drupal = FALSE;
    $this->supports_form_extensions = FALSE;
  }

  public function initialize() {
    parent::initialize();
    $test = $GLOBALS['CIVICRM_TEST_CASE'] ?? NULL;
    if ($test && $test instanceof \Civi\Test\HeadlessInterface) {
      $listenerMap = \Civi\Core\Event\EventScanner::findListeners($test);
      \Civi::dispatcher()->addListenerMap($test, $listenerMap);
    }
    \Civi\Test::eventChecker()->addListeners();
  }

  /**
   * @internal
   * @return bool
   */
  public function isLoaded(): bool {
    return TRUE;
  }

  /**
   * Send an HTTP Response base on PSR HTTP RespnseInterface response.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   */
  public function sendResponse(\Psr\Http\Message\ResponseInterface $response) {
    // We'll if the simple version passes. If not, then we might need to enable `setHttpHeader()`.
    // foreach ($response->getHeaders() as $name => $values) {
    //   CRM_Utils_System::setHttpHeader($name, implode(', ', (array) $values));
    // }
    CRM_Utils_System::civiExit(0, ['response' => $response]);
  }

  /**
   * @param string $name
   * @param string $value
   */
  public function setHttpHeader($name, $value) {
    Civi::$statics[__CLASS__]['header'][] = ("$name: $value");
  }

  /**
   * @inheritDoc
   */
  public function authenticate($name, $password, $loadCMSBootstrap = FALSE, $realPath = NULL) {
    $retVal = [1, 1, 12345];
    return $retVal;
  }

  /**
   * Bootstrap the phony CMS.
   */
  public function loadBootStrap($params = [], $loadUser = TRUE, $throwError = TRUE, $realPath = NULL) {
    return TRUE;
  }

  public function cmsRootPath() {
    // There's no particularly sensible value here. We just want to avoid crashes in some tests.
    return sys_get_temp_dir() . '/UnitTests';
  }

  /**
   * @inheritdoc
   */
  public function getCiviSourceStorage(): array {
    global $civicrm_root;

    if (!defined('CIVICRM_UF_BASEURL')) {
      throw new RuntimeException('Undefined constant: CIVICRM_UF_BASEURL');
    }

    return [
      'url' => CRM_Utils_File::addTrailingSlash('', '/'),
      'path' => CRM_Utils_File::addTrailingSlash($civicrm_root),
    ];
  }

  /**
   * @inheritDoc
   */
  public function mapConfigToSSL() {
    global $base_url;
    $base_url = str_replace('http://', 'https://', (string) $base_url);
  }

  /**
   * @inheritDoc
   */
  public function postURL($action) {
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function url(
    $path = NULL,
    $query = NULL,
    $absolute = FALSE,
    $fragment = NULL,
    $frontend = FALSE,
    $forceBackend = FALSE
  ) {
    $config = CRM_Core_Config::singleton();
    static $script = 'index.php';

    if (isset($fragment)) {
      $fragment = '#' . $fragment;
    }

    if (!isset($config->useFrameworkRelativeBase)) {
      $base = parse_url($config->userFrameworkBaseURL);
      $config->useFrameworkRelativeBase = $base['path'];
    }
    $base = $absolute ? $config->userFrameworkBaseURL : $config->useFrameworkRelativeBase;

    if (!$config->cleanURL) {
      if ($path !== NULL && $path !== '' && $path !== FALSE) {
        if ($query !== NULL && $query !== '' && $query !== FALSE) {
          return $base . $script . '?q=' . $path . '&' . $query . $fragment;
        }
        else {
          return $base . $script . '?q=' . $path . $fragment;
        }
      }
      else {
        if ($query !== NULL && $query !== '' && $query !== FALSE) {
          return $base . $script . '?' . $query . $fragment;
        }
        else {
          return $base . $fragment;
        }
      }
    }
    else {
      if (isset($path)) {
        if (isset($query)) {
          return $base . $path . '?' . $query . $fragment;
        }
        else {
          return $base . $path . $fragment;
        }
      }
      else {
        if (isset($query)) {
          return $base . $script . '?' . $query . $fragment;
        }
        else {
          return $base . $fragment;
        }
      }
    }
  }

  /**
   * @param $user
   */
  public function getUserID($user) {
    //FIXME: look here a bit closer when testing UFMatch

    // this puts the appropriate values in the session, so
    // no need to return anything
    CRM_Core_BAO_UFMatch::synchronize($user, TRUE, 'Standalone', 'Individual');
  }

  /**
   * @inheritDoc
   */
  public function logout() {
    session_destroy();
    CRM_Utils_System::setHttpHeader("Location", "index.php");
  }

  /**
   * @inheritDoc
   */
  public function getLoginURL($destination = '') {
    throw new Exception("Method not implemented: getLoginURL");
  }

  /**
   * @inheritdoc
   */
  public function mailingWorkflowIsEnabled():bool {
    $enableWorkflow = Civi::settings()->get('civimail_workflow');
    return (bool) $enableWorkflow;
  }

  public function ipAddress(): ?string {
    // Placeholder address for unit testing
    return '127.0.0.1';
  }

  /**
   * Simulate JSON response to the client
   */
  public static function sendJSONResponse(array $response, int $httpResponseCode): void {
    throw new CRM_Core_Exception_PrematureExitException('sendJSONResponse', $response, $httpResponseCode);
  }

}
