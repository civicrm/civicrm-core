<?php
use CRM_Standaloneusers_ExtensionUtil as E;

class CRM_Standaloneusers_Page_Login extends CRM_Core_Page {

  public function run() {
    if (CRM_Core_Config::singleton()->userSystem->isUserLoggedIn()) {
      // Already logged in.
      CRM_Utils_System::redirect('/civicrm');
    }
    if (isset($_GET['justLoggedOut'])) {
      // When the user has just logged out their session is destroyed
      // so we are unable to use setStatus in that request. Here we
      // add the session message and redirect so the user doesn't keep getting
      // the message when they press Back.
      CRM_Core_Session::setStatus(
        ts('You have been logged out.'),
        ts('Successfully signed out.'),
        'success');
      CRM_Utils_System::redirect('/civicrm/login');
    }

    $this->assign('logoUrl', E::url('images/civicrm-logo.png'));
    $this->assign('pageTitle', '');
    $this->assign('forgottenPasswordURL', CRM_Utils_System::url('civicrm/login/password'));
    // Remove breadcrumb for login page.
    $this->assign('breadcrumb', NULL);

    // statusMessages are usually at top of page but in login forms they look much better
    // inside the main box.
    $this->assign('statusMessages', CRM_Core_Smarty::singleton()->fetch("CRM/common/status.tpl"));

    parent::run();
  }

  /**
   * Log out.
   */
  public static function logout() {
    CRM_Core_Config::singleton()->userSystem->logout();
    // Dump them back on the log-IN page.
    CRM_Utils_System::redirect('/civicrm/login?justLoggedOut');
  }

}
