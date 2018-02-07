<?php
namespace Civi\Codeception;

/**
 * Trait CiviAcceptanceTesterTrait
 * Trait for common functions used for Codeception Testing framework
 * @package Civi\Codeception
 */
trait CiviAcceptanceTesterTrait {

  /**
   * Parse Parameters, and create generic Civi URL
   * @param $page
   *   The path and parameters of a CiviCRM page. Ex: "civicrm/dashboard?reset=1".
   */
  public function amOnRoute($page) {
    $params = explode('?', $page);
    if (empty($params[1])) {
      $newPage = \CRM_Utils_System::url($page, NULL, FALSE, NULL, FALSE);
    }
    else {
      $newPage = \CRM_Utils_System::url($params[0], $params[1], FALSE, NULL, FALSE);
    }
    return $this->amOnPage($newPage);
  }

  /**
   * Dispatcher for login to supported plattforms
   * @param $username
   *   CiviCRM username for the login
   * @param $password
   *   CiviCRM password for the login
   */
  public function login($username, $password) {
    $config = \CRM_Core_Config::singleton();
    $handler = array($this, 'loginTo' . $config->userFramework);
    if (is_callable($handler)) {
      call_user_func($handler, $username, $password);
    }
    else {
      throw new CRM_Core_Exception("Framework {$config->userFramework} is not supported. Implement loginTo{$config->userFramework}.");
    }
  }

  /**
   * Login to Drupal
   * @param $username
   *   CiviCRM username for the login
   * @param $password
   *   CiviCRM password for the login
   */
  public function loginToDrupal($username, $password) {
    $I = $this;
    $I->amOnPage('/user');
    $I->fillField("#edit-name", $username);
    $I->fillField("#edit-pass", $password);
    $I->click("#edit-submit");
    $I->see("CiviCRM Home");
  }

  /**
   * Login to Joomla
   * @param $username
   *   CiviCRM username for the login
   * @param $password
   *   CiviCRM password for the login
   */
  public function loginToJoomla($username, $password) {
    throw new CRM_Core_Exception("loginToJoomla is not implemented yet. Implement a corresponding login function.");
  }

  /**
   * Login to Wordpress
   * @param $username
   *   CiviCRM username for the login
   * @param $password
   *   CiviCRM password for the login
   */
  public function loginToWordpress($username, $password) {
    throw new CRM_Core_Exception("loginToWordpress is not implemented yet. Implement a corresponding login function.");
  }

  /**
   * Login to Backdrop
   * @param $username
   *   CiviCRM username for the login
   * @param $password
   *   CiviCRM password for the login
   */
  public function loginToBackdrop($username, $password) {
    throw new CRM_Core_Exception("loginToBackdrop is not implemented yet. Implement a corresponding login function.");
  }

  /**
   * Login as Admin User
   */
  public function loginAsAdmin() {
    global $_CV;
    $this->login($_CV['ADMIN_USER'], $_CV['ADMIN_PASS']);
  }

  /**
   * Login as Demo User
   */
  public function loginAsDemo() {
    global $_CV;
    $this->login($_CV['DEMO_USER'], $_CV['DEMO_PASS']);
  }

}
