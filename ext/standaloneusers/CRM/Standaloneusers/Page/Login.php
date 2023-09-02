<?php
use CRM_Standaloneusers_ExtensionUtil as E;
use Civi\Standalone\Security;

class CRM_Standaloneusers_Page_Login extends CRM_Core_Page {

  public function run() {
    // // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    // CRM_Utils_System::setTitle(E::ts('Login'));
    //
    // // Example: Assign a variable for use in a template
    // $this->assign('currentTime', date('Y-m-d H:i:s'));
    $this->assign('logoUrl', E::url('images/civicrm-logo.png'));
    // Remove breadcrumb for login page.
    $this->assign('breadcrumb', NULL);

    parent::run();
  }

  /**
   * Log out.
   */
  public static function logout() {
    Security::singleton()->logoutUser();
    // Dump them back on the log-IN page.
    CRM_Utils_System::redirect('/civicrm/login?justLoggedOut');
  }

}
