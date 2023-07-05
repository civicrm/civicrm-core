<?php
use CRM_Standaloneusers_ExtensionUtil as E;

class CRM_Standaloneusers_Page_Login extends CRM_Core_Page {

  public function run() {
    // // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    // CRM_Utils_System::setTitle(E::ts('Login'));
    //
    // // Example: Assign a variable for use in a template
    // $this->assign('currentTime', date('Y-m-d H:i:s'));
    $this->assign('logoUrl', E::url('images/civicrm-logo.png'));

    parent::run();
  }

  /**
   * Log out.
   */
  public function logout() {
    // Same as CRM_Authx_Page_AJAX::logout()
    _authx_uf()->logoutSession();
    // Dump them back on the log-IN page.
    CRM_Utils_System::redirect('/civicrm/login?justLoggedOut');
  }

}
